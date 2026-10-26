<?php
/** STTShoutbox — SWIRTV TEAM, 2026. Independent MyBB plugin; its own storage, permissions and bot. */
if(!defined('IN_MYBB')) die('Direct access is not allowed.');
// Incomplete uploads must not break the forum or the plugin list.
$sttFilesReady=is_file(__DIR__.'/sttshoutbox/i18n.php')&&is_file(__DIR__.'/sttshoutbox/bot.php')&&is_file(__DIR__.'/sttshoutbox/filters.php')&&is_file(__DIR__.'/sttshoutbox/bot-admin.php')&&is_file(__DIR__.'/sttshoutbox/setup.php')&&is_file(__DIR__.'/sttshoutbox/service.php');
if($sttFilesReady){
require_once __DIR__.'/sttshoutbox/i18n.php';
$plugins->add_hook('admin_load','stt_admin_language');
require_once __DIR__.'/sttshoutbox/bot.php';
require_once __DIR__.'/sttshoutbox/setup.php';
require_once __DIR__.'/sttshoutbox/bot-admin.php';
$plugins->add_hook('admin_config_plugins_begin','stt_setup_admin');
$plugins->add_hook('admin_config_plugins_begin','stt_bot_admin');
$plugins->add_hook('datahandler_post_insert_thread_end','stt_bot_thread');
$plugins->add_hook('datahandler_post_insert_post_end','stt_bot_post');
$plugins->add_hook('member_do_register_end','stt_bot_register');
$plugins->add_hook('index_end','sttshoutbox_render',100);
$plugins->add_hook('xmlhttp','sttshoutbox_api',1);
}
function sttshoutbox_info(){
 $description=(function_exists('stt_t')?stt_t('text_standalone_stt_chat_forum_smilies_coloured_usernames_ea07a2'):'Standalone STT chat: forum smilies, coloured usernames, private conversations and a bot.');
 if(function_exists('stt_setup_requirements')){$issues=stt_setup_requirements();if($issues)$description.='<br><strong>'.htmlspecialchars_uni(implode(' ',$issues)).'</strong>';}
 else $description.=(function_exists('stt_t')?stt_t('text_incomplete_package_upload_the_entire_inc_plugins_stt_fec9f9'):'<br><strong>Incomplete package: upload the entire inc/plugins/sttshoutbox folder from Upload.</strong>');
 if(defined('IN_ADMINCP')&&function_exists('stt_t')&&sttshoutbox_is_installed())$description.=stt_t('text_quick_setup_customise_your_chat_after_activation_3bafe5');
 if(defined('IN_ADMINCP')&&function_exists('stt_t')&&sttshoutbox_is_installed())$description.='<br><a href="index.php?module=config-plugins&amp;action=sttshoutbox_bot"><strong>'.htmlspecialchars_uni(stt_t('bot_panel_link')).'</strong></a>';
 return ['name'=>'STTShoutbox','description'=>$description,'website'=>'https://swirtvteam.info/','author'=>'SWIRTV TEAM','authorsite'=>'https://swirtvteam.info/','version'=>'2.4.0','compatibility'=>'18*'];
}
function sttshoutbox_preflight(){
 $issues=function_exists('stt_setup_requirements')?stt_setup_requirements():[(function_exists('stt_t')?stt_t('text_incomplete_package_upload_the_contents_of_upload_to__b1ec3c'):'Incomplete package. Upload the contents of Upload to the forum directory.')];
 if($issues){if(defined('IN_ADMINCP')){flash_message(implode(' ',$issues),'error');admin_redirect('index.php?module=config-plugins');exit;}throw new RuntimeException(implode(' ',$issues));}
}
function sttshoutbox_is_installed(){global $db;return (bool)$db->fetch_field($db->simple_select('settinggroups','gid',"name='sttshoutbox'"),'gid');}
function sttshoutbox_install(){
 global $db;
 sttshoutbox_preflight();$fresh=!sttshoutbox_is_installed();
 if(!in_array($db->type,['mysqli','mysql','mysql_pdo'],true)||!extension_loaded('mbstring'))throw new RuntimeException(stt_t('text_sttshoutbox_requires_mysql_mariadb_and_the_php_mbstr_1e2cfd'));
 // STT owns this table. No external chat installation or hooks are required.
 if(!$db->table_exists('stt_messages'))$db->write_query('CREATE TABLE '.TABLE_PREFIX.'stt_messages (id int unsigned NOT NULL AUTO_INCREMENT,uid int unsigned NOT NULL,text text NULL,date int unsigned NOT NULL,modified int unsigned DEFAULT NULL,ipaddress varbinary(16) NOT NULL,PRIMARY KEY(id),KEY(date)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
 $schemas=[
 'stt_dm'=>'id bigint unsigned NOT NULL AUTO_INCREMENT,sender int unsigned NOT NULL,recipient int unsigned NOT NULL,body text NOT NULL,created int unsigned NOT NULL,modified int unsigned DEFAULT NULL,seen tinyint NOT NULL DEFAULT 0,deleted tinyint NOT NULL DEFAULT 0,PRIMARY KEY(id),KEY sender_recipient(sender,recipient,id),KEY recipient_unread(recipient,seen,id)',
 'stt_rate'=>'uid int unsigned NOT NULL,last_sent int unsigned NOT NULL DEFAULT 0,PRIMARY KEY(uid)',
 'stt_hidden'=>'sid int unsigned NOT NULL,actor int unsigned NOT NULL,created int unsigned NOT NULL,PRIMARY KEY(sid)',
 'stt_reactions'=>'sid int unsigned NOT NULL,uid int unsigned NOT NULL,kind varchar(12) NOT NULL,PRIMARY KEY(sid,uid,kind)'];
 foreach($schemas as$name=>$sql)if(!$db->table_exists($name))$db->write_query('CREATE TABLE '.TABLE_PREFIX.$name.' ('.$sql.') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
 if(!$db->fetch_field($db->simple_select('settinggroups','gid',"name='sttshoutbox'"),'gid')){
  $gid=$db->insert_query('settinggroups',['name'=>'sttshoutbox','title'=>'STTShoutbox','description'=>stt_t('text_swirtv_team_chat_smilies_come_from_configuration_smi_3ce680'),'disporder'=>2,'isdefault'=>0]);
  foreach(['enabled'=>[stt_t('text_enable_sttshoutbox_85bc5e'),'yesno','1'],'preview'=>[stt_t('text_administrator_only_preview_4473dd'),'yesno','0'],'interval'=>[stt_t('text_refresh_interval_seconds_minimum_5_e02c61'),'numeric','7'],'limit'=>[stt_t('text_maximum_message_length_up_to_4000_characters_fbfc7d'),'numeric','2000'],'cooldown'=>[stt_t('text_delay_between_messages_seconds_f2e0d4'),'numeric','3'],'private'=>[stt_t('text_private_conversations_994c26'),'yesno','1']]as$key=>$v)$db->insert_query('settings',['name'=>'stt_'.$key,'title'=>$db->escape_string($v[0]),'description'=>'','optionscode'=>$v[1],'value'=>$v[2],'disporder'=>1,'gid'=>$gid,'isdefault'=>0]);
  rebuild_settings();
 }
 sttshoutbox_upgrade_22($fresh);
}
function sttshoutbox_activate(){
 sttshoutbox_install();sttshoutbox_upgrade_20();sttshoutbox_upgrade_21();sttshoutbox_upgrade_24();
 require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
 find_replace_templatesets('index','#(\{\$header\})(?![\s\S]*\{\$sttshoutbox\})#','$1<!-- STTShoutbox:start -->{$sttshoutbox}<!-- STTShoutbox:end -->');
}
function sttshoutbox_deactivate(){
 require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
 find_replace_templatesets('index','#<!-- STTShoutbox:start -->\{\$sttshoutbox\}<!-- STTShoutbox:end -->#','',0);
}
// Uninstall removes settings but deliberately preserves conversation history.
function sttshoutbox_uninstall(){global $db;$gid=(int)$db->fetch_field($db->simple_select('settinggroups','gid',"name='sttshoutbox'"),'gid');if($gid){$db->delete_query('settings','gid='.$gid);$db->delete_query('settinggroups','gid='.$gid);rebuild_settings();}}
function sttshoutbox_load(){require_once MYBB_ROOT.'inc/plugins/sttshoutbox/service.php';}
function sttshoutbox_render(){
 global $mybb,$sttshoutbox,$templates;$sttshoutbox="";
 if(empty($mybb->settings['stt_enabled'])||( !empty($mybb->settings['stt_preview'])&&empty($mybb->usergroup['cancp'])))return;
 sttshoutbox_load();if(!stt_view())return;
 $base=rtrim($mybb->settings['bburl'],'/');
 $appearance=stt_setup_appearance($mybb->settings);
 $templates->get('index');
 $templates->cache['index']=stt_setup_place($templates->cache['index']);
 $cfg=json_encode($appearance+['locale'=>stt_language()==='polish'?'pl-PL':'en-GB','i18n'=>stt_ui_dictionary(),'endpoint'=>$base.'/xmlhttp.php?action=sttshoutbox','csrf'=>$mybb->post_code,'uid'=>(int)$mybb->user['uid'],'name'=>$mybb->user['username'],'write'=>stt_write(),'private'=>stt_pm_allowed(),'moderator'=>stt_mod(),'interval'=>max(5,(int)($mybb->settings['stt_interval']??7)),'limit'=>stt_limit(),'order'=>($mybb->settings['stt_order']??'newest'),'timeOffset'=>(float)($mybb->user['timezone']??$mybb->settings['timezoneoffset']??0)+(float)($mybb->user['dst']??$mybb->settings['dstcorrection']??0),'preview'=>!empty($mybb->settings['stt_preview']),'profile'=>$base.'/member.php?action=profile&uid='],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
 $url=htmlspecialchars_uni($base);
 $sttshoutbox='<link rel="stylesheet" href="'.$url.'/jscripts/sttshoutbox.css?v=2.4.0"><section id="stt-chat" aria-label="STTShoutbox"><div class="stt-loading">'.htmlspecialchars_uni(stt_t('bot_chat_loading')).'</div></section><script type="application/json" id="stt-config">'.$cfg.'</script><script defer src="'.$url.'/jscripts/sttshoutbox.js?v=2.4.0"></script>';
}
function sttshoutbox_api(){
 global $mybb;
 if($mybb->get_input('action')!=='sttshoutbox')return;
 header('Content-Type: application/json; charset=utf-8');header('Cache-Control: private, no-store');header('X-Content-Type-Options: nosniff');
 sttshoutbox_load();
 try{
  if(empty($mybb->settings['stt_enabled'])||(!empty($mybb->settings['stt_preview'])&&empty($mybb->usergroup['cancp']))||!stt_view())throw new RuntimeException(stt_t('text_chat_access_denied_21f5b7'),403);
  $op=$mybb->get_input('op')?:'load';
  $writes=['send','edit','delete','react','read'];
  if(in_array($op,$writes,true)&&($mybb->request_method!=='post'||!verify_post_check($mybb->get_input('my_post_key'),true)))throw new RuntimeException(stt_t('text_your_session_has_expired_refresh_the_page_9cd843'),403);
  $result=stt_dispatch($op,$mybb->input);echo json_encode(['ok'=>true,'data'=>$result],JSON_INVALID_UTF8_SUBSTITUTE);
 }catch(Throwable $e){$status=in_array($e->getCode(),[400,403,404,409,429],true)?$e->getCode():500;http_response_code($status);if($status===500)error_log('STTShoutbox: '.$e->getMessage());echo json_encode(['ok'=>false,'error'=>$status===500?stt_t('text_the_operation_failed_please_try_again_5bb16c'):$e->getMessage()]);}
 exit;
}

function sttshoutbox_upgrade_21(){global $db;if($db->table_exists('stt_dm')&&!$db->field_exists('modified','stt_dm'))$db->add_column('stt_dm','modified','int unsigned DEFAULT NULL');}
