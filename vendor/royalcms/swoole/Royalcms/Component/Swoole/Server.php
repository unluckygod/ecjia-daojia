<?php

namespace Royalcms\Component\Swoole;

use Royalcms\Component\Swoole\Socket\PortInterface;
use Royalcms\Component\Swoole\Task\Event;
use Royalcms\Component\Swoole\Task\Listener;
use Royalcms\Component\Swoole\Task\Task;
use Royalcms\Component\Swoole\Traits\LogTrait;
use Royalcms\Component\Swoole\Traits\ProcessTitleTrait;
use Swoole\WebSocket\Server as WebSocketServer;
use Swoole\Server\Port;
use Swoole\Http\Server as HttpServer;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;

class Server
{
    use LogTrait;
    use ProcessTitleTrait;

    protected $conf;

    /**
     * @var \Swoole\Http\Server|\Swoole\Websocket\Server
     */
    protected $swoole;

    protected $enableWebSocket = false;

    protected $attachedSockets = [];

    protected function __construct(array $conf)
    {
        $this->conf = $conf;
        $this->enableWebSocket = !empty($this->conf['websocket']['enable']);
        $this->attachedSockets = empty($this->conf['sockets']) ? [] : $this->conf['sockets'];

        $ip = isset($conf['listen_ip']) ? $conf['listen_ip'] : '127.0.0.1';
        $port = isset($conf['listen_port']) ? $conf['listen_port'] : 5200;
        $socketType = isset($conf['socket_type']) ? $conf['socket_type'] : \SWOOLE_SOCK_TCP;

        if ($socketType === \SWOOLE_SOCK_UNIX_STREAM) {
            $socketDir = dirname($ip);
            if (!file_exists($socketDir)) {
                mkdir($socketDir);
            }
        }

        $settings = isset($conf['swoole']) ? $conf['swoole'] : [];
        $settings['enable_static_handler'] = !empty($conf['handle_static']);

        $serverClass = $this->enableWebSocket ? '\Swoole\Websocket\Server' : '\Swoole\Http\Server';
        if (isset($settings['ssl_cert_file'], $settings['ssl_key_file'])) {
            $this->swoole = new $serverClass($ip, $port, \SWOOLE_PROCESS, $socketType | \SWOOLE_SSL);
        } else {
            $this->swoole = new $serverClass($ip, $port, \SWOOLE_PROCESS, $socketType);
        }
        
        $this->swoole->set($settings);

        $this->bindBaseEvent();
        $this->bindHttpEvent();
        $this->bindTaskEvent();
        $this->bindWebSocketEvent();
        $this->bindAttachedSockets();
        $this->bindSwooleTables();
    }

    protected function bindBaseEvent()
    {
        $this->swoole->on('Start', [$this, 'onStart']);
        $this->swoole->on('Shutdown', [$this, 'onShutdown']);
        $this->swoole->on('ManagerStart', [$this, 'onManagerStart']);
        $this->swoole->on('ManagerStop', [$this, 'onManagerStop']);
        $this->swoole->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->swoole->on('WorkerStop', [$this, 'onWorkerStop']);
        $this->swoole->on('WorkerError', [$this, 'onWorkerError']);
    }

    protected function bindHttpEvent()
    {
        $this->swoole->on('Request', [$this, 'onRequest']);
    }

    protected function bindTaskEvent()
    {
        if (!empty($this->conf['swoole']['task_worker_num'])) {
            $this->swoole->on('Task', [$this, 'onTask']);
            $this->swoole->on('Finish', [$this, 'onFinish']);
        }
    }

    protected function bindWebSocketEvent()
    {
        if ($this->enableWebSocket) {
            $eventHandler = function ($method, array $params) {
                try {
                    call_user_func_array([$this->getWebSocketHandler(), $method], $params);
                } catch (\Exception $e) {
                    $this->logException($e);
                }
            };

            $this->swoole->on('Open', function () use ($eventHandler) {
                $eventHandler('onOpen', func_get_args());
            });

            $this->swoole->on('Message', function () use ($eventHandler) {
                $eventHandler('onMessage', func_get_args());
            });

            $this->swoole->on('Close', function (WebSocketServer $server, $fd, $reactorId) use ($eventHandler) {
                $clientInfo = $server->getClientInfo($fd);
                if (isset($clientInfo['websocket_status']) && $clientInfo['websocket_status'] === \WEBSOCKET_STATUS_FRAME) {
                    $eventHandler('onClose', func_get_args());
                }
                // else ignore the close event for http server
            });
        }
    }

    protected function bindAttachedSockets()
    {
        foreach ($this->attachedSockets as $socket) {
            $port = $this->swoole->addListener($socket['host'], $socket['port'], $socket['type']);
            if (!($port instanceof Port)) {
                $errno = method_exists($this->swoole, 'getLastError') ? $this->swoole->getLastError() : 'unknown';
                $errstr = sprintf('listen %s:%s failed: errno=%s', $socket['host'], $socket['port'], $errno);
                $this->log($errstr, 'ERROR');
                continue;
            }

            $port->set(empty($socket['settings']) ? [] : $socket['settings']);

            $handlerClass = $socket['handler'];
            $eventHandler = function ($method, array $params) use ($port, $handlerClass) {
                $handler = $this->getSocketHandler($port, $handlerClass);
                if (method_exists($handler, $method)) {
                    try {
                        call_user_func_array([$handler, $method], $params);
                    } catch (\Exception $e) {
                        $this->logException($e);
                    }
                }
            };
            static $events = [
                'Open',
                'Request',
                'Message',
                'Connect',
                'Close',
                'Receive',
                'Packet',
                'BufferFull',
                'BufferEmpty',
            ];
            foreach ($events as $event) {
                $port->on($event, function () use ($event, $eventHandler) {
                    $eventHandler('on' . $event, func_get_args());
                });
            }
        }
    }

    protected function getWebSocketHandler()
    {
        static $handler = null;
        if ($handler !== null) {
            return $handler;
        }

        $handlerClass = $this->conf['websocket']['handler'];
        $t = new $handlerClass();
        if (!($t instanceof WebSocketHandlerInterface)) {
            throw new \Exception(sprintf('%s must implement the interface %s', get_class($t), 'Royalcms\Component\Swoole\WebSocketHandlerInterface'));
        }
        $handler = $t;
        return $handler;
    }

    protected function getSocketHandler(Port $port, $handlerClass)
    {
        static $handlers = [];
        $portHash = spl_object_hash($port);
        if (isset($handlers[$portHash])) {
            return $handlers[$portHash];
        }
        $t = new $handlerClass($port);
        if (!($t instanceof PortInterface)) {
            throw new \Exception(sprintf('%s must extend the abstract class TcpSocket/UdpSocket', get_class($t)));
        }
        $handlers[$portHash] = $t;
        return $handlers[$portHash];
    }

    protected function bindSwooleTables()
    {
        $tables = isset($this->conf['swoole_tables']) ? (array)$this->conf['swoole_tables'] : [];
        foreach ($tables as $name => $table) {
            $t = new \swoole_table($table['size']);
            foreach ($table['column'] as $column) {
                if (isset($column['size'])) {
                    $t->column($column['name'], $column['type'], $column['size']);
                } else {
                    $t->column($column['name'], $column['type']);
                }
            }
            $t->create();
            $name .= 'Table'; // Avoid naming conflicts
            $this->swoole->$name = $t;
        }
    }

    public function onStart(HttpServer $server)
    {
        foreach (spl_autoload_functions() as $function) {
            spl_autoload_unregister($function);
        }
        
        $this->setProcessTitle(sprintf('%s royalcms: master process', $this->conf['process_prefix']));
        
        if (version_compare(\swoole_version(), '1.9.5', '<')) {
            file_put_contents($this->conf['swoole']['pid_file'], $server->master_pid);
        }
    }

    public function onShutdown(HttpServer $server)
    {

    }

    public function onManagerStart(HttpServer $server)
    {
        $this->setProcessTitle(sprintf('%s royalcms: manager process', $this->conf['process_prefix']));
    }

    public function onManagerStop(HttpServer $server)
    {

    }

    public function onWorkerStart(HttpServer $server, $workerId)
    {
        if ($workerId >= $server->setting['worker_num']) {
            $process = 'task worker';
        } else {
            $process = 'worker';
        }
        $this->setProcessTitle(sprintf('%s royalcms: %s process %d', $this->conf['process_prefix'], $process, $workerId));

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        if (function_exists('apc_clear_cache')) {
            apc_clear_cache();
        }

        clearstatcache();
    }

    public function onWorkerStop(HttpServer $server, $workerId)
    {

    }

    public function onWorkerError(HttpServer $server, $workerId, $workerPId, $exitCode, $signal)
    {
        $this->log(sprintf('worker[%d] error: exitCode=%s, signal=%s', $workerId, $exitCode, $signal), 'ERROR');
    }

    public function onRequest(SwooleRequest $request, SwooleResponse $response)
    {

    }

    public function onTask(HttpServer $server, $taskId, $srcWorkerId, $data)
    {
        if ($data instanceof Event) {
            $this->handleEvent($data);
        } elseif ($data instanceof Task) {
            $this->handleTask($data);
            if (method_exists($data, 'finish')) {
                return $data;
            }
        }
    }

    public function onFinish(HttpServer $server, $taskId, $data)
    {
        if ($data instanceof Task) {
            $data->/** @scrutinizer ignore-call */
            finish();
        }
    }

    protected function handleEvent(Event $event)
    {
        $eventClass = get_class($event);
        if (!isset($this->conf['events'][$eventClass])) {
            return;
        }

        $listenerClasses = $this->conf['events'][$eventClass];
        if (!is_array($listenerClasses)) {
            $listenerClasses = (array)$listenerClasses;
        }
        foreach ($listenerClasses as $listenerClass) {
            /**
             * @var Listener $listener
             */
            $listener = new $listenerClass();
            if (!($listener instanceof Listener)) {
                throw new \Exception(sprintf('%s must extend the abstract class %s', $listenerClass, 'Royalcms\Component\Swoole\Task\Listener'));
            }
            try {
                $listener->handle($event);
            } catch (\Exception $e) {
                $this->logException($e);
            }
        }
    }

    protected function handleTask(Task $task)
    {
        try {
            $task->handle();
        } catch (\Exception $e) {
            $this->logException($e);
        }
    }

    public function run()
    {
        $this->swoole->start();
    }
}
