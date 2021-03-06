<?php defined('IN_ECJIA') or exit('No permission resources.');?>
<!-- {extends file="ecjia.dwt.php"} -->

<!-- {block name="footer"} -->
<script type="text/javascript">
	ecjia.admin.push_action.init();
</script>
<!-- {/block} -->

<!-- {block name="main_content"} -->
<div>
	<h3 class="heading">
		<!-- {if $ur_here}{$ur_here}{/if} -->
		<a href="{$action_link.href}" class="btn plus_or_reply data-pjax"><i class="fontello-icon-reply"></i>{$action_link.text}</a>
	</h3>
</div>

<div class="row-fluid push_list ">
	<div class="span12">
		<form id="form-privilege" class="form-horizontal" name="theForm" action="{$form_action}" method="post">
			<fieldset>
				<div class="row-fluid edit-page">
					<div class="control-group formSep">
						<label class="control-label">{t domain="push"}消息主题：{/t}</label>
						<div class="controls">
							<input type="text" name="title" class="span6" value="{$push.title}"/>
							<span class="input-must">*</span>
							<span class="help-block">{t domain="push"}用于标识消息，方便查找和管理{/t}</span>
						</div>
					</div>
					<div class="control-group">
						<label class="control-label">{t domain="push"}消息内容：{/t}</label>
						<div class="controls">
							<textarea class="span8" name="content">{$push.content}</textarea>
							<span class="input-must">*</span>
							<span class="help-block">{t domain="push"}这里是要推送的消息内容{/t}</span>
						</div>
					</div>
											
					
					<h3 class="heading">{t domain="push"}推送产品{/t}</h3>
					{if $product_device_list}
						<!-- {foreach from=$product_device_list item=list} -->
							<div class="outline" >
								<div class="push_radio">
									<input type="radio" class="uni_style" name="push_product_device" value="{$list.device_code}" {if $list.device_code eq $push.device_code}checked="true"{/if}/>
								</div>
								<div class="outline-left"><img src="{$list.icon}" /></div>
								<div class="outline-right">
									<h4>{$list.app_name}</h4>
									<span>{$list.client_name}</span>
								</div>
							</div>
						<!-- {/foreach} -->
						
						<h3 class="heading">{t domain="push"}推送行为{/t}</h3>
						<div class="control-group open_action" >
							<label class="control-label">{t domain="push"}打开动作：{/t}</label>
							<div class="controls">
								<select class="span6 object_html" id="object_value" name="object_value">
			                        <option value='0'>{t domain="push"}请选择{/t}</option>
			                        <!-- {html_options options=$action_list selected=$open_type} -->
								</select>
								<span class="help-block">{t domain="push"}选择该消息需要打开的页面，不选择时，默认打开主页{/t}</span>
							</div>
						</div>
						
						<div class="custom-div">
							<!-- {foreach from=$args_list item=args} -->
								<div class="control-group m_t10">
				                	<label class="control-label">{$args.name}</label>
				                	<div class="controls">
					                	<input type="text" class="span6" name="{$args.code}" value="{$args.value}"/>
					                	{if $args.description} 
					                	<span class="help-block">{$args.description}</span>
					                	{/if}
					                </div>
				                </div>
							<!-- {/foreach} -->
						</div>
	
						<h3 class="heading">{t domain="push"}推送对象{/t}</h3>
						<div class="control-group" >
							<label class="control-label">{t domain="push"}推送给：{/t}</label>
							<div class="controls chk_radio">
								<input type="radio" class="uni_style" name="target" value="0" {if $push.device_token eq 'broadcast'}checked="checked"{/if}/><span>{t domain="push"}所有设备{/t}</span>
								<input type="radio" class="uni_style" name="target" value="1" {if $push.device_token neq 'broadcast'}checked="checked"{/if}/><span>{t domain="push"}单播{/t}</span>
								<input type="radio" class="uni_style" name="target" value="user" /><span>{t domain="push"}用户{/t}</span>
								<input type="radio" class="uni_style" name="target" value="merchant" /><span>{t domain="push"}商家用户{/t}</span>
								<input type="radio" class="uni_style" name="target" value="admin" /><span>{t domain="push"}管理员{/t}</span>
							</div>
						</div>
					
						<div id="onediv" class="push_object control-group {if $push.device_token eq 'broadcast'}hide{/if}">
							<label class="control-label">Device Token：</label>
							<div class="controls">
								<input type="text" id="device_token" name="device_token" class="span8" value="{if $push.device_token neq 'broadcast'}{$push.device_token}{/if}"/>
							</div>
						</div>
						
						
						<div id="admindiv" class="push_object hide">
							<div class="control-group">
								<label class="control-label">{t domain="push"}管理员名称：{/t}</label>
								<div class="controls">
									<input type="text" name="admin_keywords" />
									<button type="button" class="btn searchAadmin" data-url='{url path="push/admin/search_admin_list"}'>{t domain="push"}搜索{/t}</button>
								</div>
							</div>
							
							<div class="control-group">
								<label class="control-label">{t domain="push"}选择要推送的管理员：{/t}</label>
								<div class="controls">
									<select name="admin_id" class='admin_list'></select>
									<span class="help-block">{t domain="push"}需要先搜索管理员，然后再选择。{/t}</span>
								</div>
							</div>
						</div>
						
						<div id="userdiv" class="push_object hide">
							<div class="control-group">
								<label class="control-label">{t domain="push"}会员手机号：{/t}</label>
								<div class="controls">
									<input type="text" name="user_keywords" />
									<button type="button" class="btn searchUser" data-url='{url path="push/admin/search_user_list"}'>{t domain="push"}搜索{/t}</button>
								</div>
							</div>
							
							<div class="control-group">
								<label class="control-label">{t domain="push"}选择要推送的会员：{/t}</label>
								<div class="controls">
									<select name="user_id" class='user_list'></select>
									<span class="help-block">{t domain="push"}需要先搜索会员，然后再选择。{/t}</span>
								</div>
							</div>
						</div>
						
						<div id="merdiv" class="push_object hide">
							<div class="control-group">
								<label class="control-label">{t domain="push"}商家会员手机号：{/t}</label>
								<div class="controls">
									<input type="text" name="mer_keywords" />
									<button type="button" class="btn searchMer" data-url='{url path="push/admin/search_merchant_list"}'>{t domain="push"}搜索{/t}</button>
								</div>
							</div>
							
							<div class="control-group">
								<label class="control-label">{t domain="push"}选择要推送的商家会员：{/t}</label>
								<div class="controls">
									<select name="merchant_user_id" class='merchant_user_list'></select>
									<span class="help-block">{t domain="push"}需要先搜索商家会员，然后再选择。{/t}</span>
								</div>
							</div>
						</div>
						
						<h3 class="heading">{t domain="push"}推送时机{/t}</h3>
						<div class="control-group formSep">
							<label class="control-label">{t domain="push"}发送时间：{/t}</label>
							<div class="controls chk_radio">
								<input type="radio" name="priority" value="1" checked="checked" />{t domain="push"}立即发送{/t}&nbsp;&nbsp;
								<input type="radio" name="priority" value="0" />{t domain="push"}稍后发送{/t}
							</div>
						</div>
					{else}
						<div class="control-group open_action formSep" >
							<label class="control-label">{t domain="push"}温馨提示：{/t}</label>
							<div class="controls l_h30">
								{t domain="push"}暂无产品进行推送，请前往顶部菜单 -> 工具 -> 移动应用 -> 客户端管理  进行配置信息即可。{/t}
							</div>
						</div>
					{/if}
					
					<div class="control-group">
						<div class="controls m_t10">
						    <input type="hidden" value="{url path='push/admin/ajax_admin_list'}" id="data-href-admin_list"/>
							<input type="hidden" value="{url path='push/admin/ajax_event_select'}" id="data-href-delect"/>
							<input type="hidden" value="{url path='push/admin/ajax_event_input'}" id="data-href-input"/>
							<input class="btn btn-gebo" type="submit" value='{t domain="push"}确定{/t}'>&nbsp;&nbsp;&nbsp;
						</div>
					</div>
				</div>
			</fieldset>
		</form>
	</div>
</div>
<!-- {/block} -->