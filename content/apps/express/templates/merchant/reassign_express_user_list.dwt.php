<?php defined('IN_ECJIA') or exit('No permission resources.');?>

<script type="text/javascript">
	ecjia.merchant.serach_user_list.init();
</script>

<div class="right-bar move-mod">
	<input type="hidden" name="home_url" value="{RC_Uri::home_url()}"/>
	<div class="foldable-list move-mod-group">
		<div class="accordion-group">
			<div class="accordion-heading">
				<a class="accordion-toggle move-mod-head"><strong>{t domain="express"}配送员列表{/t}</strong></a>
			</div>
			<div class="accordion-body">
				<div class="accordion-inner right-scroll">
					<div class="control-group control-group-small">
						<div class="margin-label">
						     <form id="form-privilege" class="form-horizontal" name="express_searchForm" action="{$search_action}" method="post" >
								 <div class="col-lg-10">
						            <input name="keywords" class="form-control express-search-input" type="text" placeholder='{t domain="express"}请输入配送员名称{/t}' value="{$smarty.get.keywords}" />
						         </div>
						         <button class="btn btn-primary express-search-btn" type="button">{t domain="express"}搜索{/t}</button>
							 </form>
						</div>
					</div>
					
					{if $express_count.online or $express_count.offline}
						{if $express_count.online}
						<div class="control-group control-group-small press-list" style="margin-bottom:0px;">
							<div class="margin-label online-list" style="margin-top:5px;margin-bottom: 5px;">{t domain="express"}在线{/t} （{$express_count.online}）<a class="acc-in move-mod-head online-click" data-toggle="collapse" data-target="#reassign-online"><b class="triangle on-tri"></b></a></div>
							<div class="online open">
								<div class="express-user-list-on accordion-body in collapse" id="reassign-online">
									<!-- {foreach from=$express_user_list.list item=list} -->
									{if $list.online_status eq '1'}
										<div class="express-user-info ex-user-div{$list.user_id}" staff_user_id="{$list.user_id}" online_status ="{$list.online_status}">
											<div class="reassign_exuser_div" longitude="{$list.longitude}" latitude="{$list.latitude}" name="{$list.name}" mobile="{$list.mobile}">
												<div class="imginfo-div">
		        		                			<div class="express-img">{if $list.avatar}<img src="{$list.avatar}">{else}<img src="{$app_url}/touxiang.png">{/if}</div>
		        		                			<div class="expressinfo">{$list.name}</div>
												</div>
												<div class="express-order-div">
													<div class="waitfor-pickup">
                                                        {t domain="express"}待取货{/t}<span class="ecjia-red">{if $list.wait_pickup_count}{$list.wait_pickup_count}{else}0{/if}{t domain="express"}单{/t}</span>
													</div>
													<div class="wait-sending">
                                                        {t domain="express"}待配送{/t}<span class="ecjia-red">{if $list.sending_count}{$list.sending_count}{else}0{/if}单</span>
													</div>
												</div>
											</div>
											<div class="assign-div">
												<a class="re-assign btn btn-warning"  type="button" notice='{t domain="express" 1={$list.name}}是否确定让  【%1】  去配送？{/t}' assign-url='{url path="express/merchant/assign_express_order" args="staff_id={$list.user_id}&type={$type}"}'>
                                                    {t domain="express"}指派给他{/t}
												</a>  
											</div>
											<input type="hidden" class="ex-u-id" value=""/>
										</div>
									{/if}
									<!-- {/foreach} -->
								</div>
						    </div>
						</div>
						{/if}
					
						{if $express_count.offline}
						<div class="control-group control-group-small press-list" style="margin-bottom:0px;">
							<div class="margin-label online-list" style="margin-top:5px;margin-bottom: 5px;">{t domain="express" 1={$express_count.offline}}离线 （%1）{/t}<a class="acc-in  move-mod-head collapsed offline-click" data-toggle="collapse" data-target="#reassign-leave"><b class="triangle1 off-tri"></b></a></div>
							<div class="leaveline-express">
								<div class="express-user-list-off accordion-body collapse" id="reassign-leave">
									<!-- {foreach from=$express_user_list.list item=list} -->
									{if $list.online_status eq '4'}
										<div class="express-user-info" longitude="{$list.longitude}" latitude="{$list.latitude}" name="{$list.name}" mobile="{$list.mobile}" online_status ="{$list.online_status}">
											<div>
												<div class="imginfo-div">
		        		                			<div class="express-img">{if $list.avatar}<img src="{$list.avatar}">{else}<img src="{$app_url}/touxiang.png">{/if}</div>
		        		                			<div class="expressinfo">{$list.name}</div>
												</div>
												<div class="express-order-div">
													<div class="waitfor-pickup">
                                                        {t domain="express"}待取货{/t}<span class="ecjia-red">{if $list.wait_pickup_count}{$list.wait_pickup_count}{else}0{/if}{t domain="express"}单{/t}</span>
													</div>
													<div class="wait-sending">
                                                        {t domain="express"}待配送{/t}<span class="ecjia-red">{if $list.sending_count}{$list.sending_count}{else}0{/if}{t domain="express"}单{/t}</span>
													</div>
												</div>
											</div>
											<div class="assign-div">
												<button class="btn btn-default" type="button" disabled="disabled">{t domain="express"}指派给他{/t}</button>
											</div>
										</div>
									{/if}
									<!-- {/foreach} -->
								</div>
							</div>
						</div>
						{/if}
					{else}
						<div class="norecord">{t domain="express"}未查找到配送员!{/t}</div>
					{/if}	
				</div>
			</div>
		</div>
	</div>
</div>	
