<?php
if(!defined('IN_MYBB')) exit;
if(!function_exists('stt_t'))require_once __DIR__.'/i18n.php';
require_once __DIR__.'/filters.php';

function stt_bot_enabled(){global $mybb;return !empty($mybb->settings['stt_enabled'])&&!empty($mybb->settings['stt_bot_enabled']);}

function stt_bot_ignore($fid){
 global $mybb;$forum=get_forum((int)$fid);if(!$forum||empty($forum['active']))return true;
 $ignored=array_filter(array_map('intval',explode(',',(string)($mybb->settings['stt_bot_ignore']??''))));
 $parents=array_map('intval',explode(',',(string)$forum['parentlist']));$parents[]=(int)$fid;
 return (bool)array_intersect($ignored,$parents);
}

function stt_bot_shout($text){
 global $db,$mybb;$uid=max(0,(int)($mybb->settings['stt_bot_uid']??0));
 $mybb->binary_fields['stt_messages']=['ipaddress'=>true];
 return (int)$db->insert_query('stt_messages',['uid'=>$uid,'text'=>$db->escape_string($text),'date'=>time(),'ipaddress'=>$db->escape_binary(my_inet_pton('127.0.0.1'))]);
}

/** Ignore acknowledgement-only replies, never substantive replies or new topics. */
function stt_bot_thanks_only($message){
 $text=(string)$message;
 if($text===''||strlen($text)>1500)return false;
 $text=html_entity_decode($text,ENT_QUOTES|ENT_HTML5,'UTF-8');
 // Formatting may surround a thank-you. Quotes, links, code and hide tags remain
 // significant so that a real contribution is not silently classified as thanks.
 $text=preg_replace('~\[/?(?:b|i|u|s|color|size|font|align)(?:=[^\]\r\n]*)?\]~iu','',$text);
 if($text===null)return false;
 $text=mb_strtolower($text,'UTF-8');
 $text=strtr($text,['ą'=>'a','ć'=>'c','ę'=>'e','ł'=>'l','ń'=>'n','ó'=>'o','ś'=>'s','ź'=>'z','ż'=>'z']);
 $text=preg_replace('/[\p{P}\p{S}]+/u',' ',$text);
 if($text===null)return false;
 $text=trim((string)preg_replace('/[\s\p{Z}]+/u',' ',$text));
 $polish='(?:(?:wielkie|serdeczne|ogromne|bardzo|serdecznie) )?(?:dzieki|dziekuje|dziekujemy)(?: (?:bardzo|serdecznie))?(?: za (?:udostepnienie|liste|konto|wstawke|material|pomoc|link|post|temat))?';
 $english='(?:(?:many|big) )?(?:thanks|thank you|thank u|thx|ty)(?: (?:very much|a lot))?(?: for (?:sharing|the (?:list|link|post|account)))?';
 return preg_match('/^(?:'.$polish.'|'.$english.')$/D',$text)===1;
}

function stt_bot_record($kind,$entity){
 global $db,$mybb;if(!stt_bot_enabled()||!in_array($kind,['thread','post','register'],true)||empty($mybb->settings['stt_bot_'.$kind]))return false;
 $entity=(int)$entity;if($entity<1)return false;$tid=$pid=$actor=0;
 if($kind==='register'){$u=get_user($entity);if(!$u)return false;$actor=$entity;}
 else{
  if($kind==='thread'){$t=get_thread($entity);$p=$t?get_post((int)$t['firstpost']):false;}
  else{$p=get_post($entity);$t=$p?get_thread((int)$p['tid']):false;}
  if(!$p||!$t||(int)$t['visible']!==1||(int)$p['visible']!==1||str_starts_with((string)$t['closed'],'moved|')||stt_bot_ignore((int)$t['fid']))return false;
  if($kind==='post'&&(int)$p['pid']===(int)$t['firstpost'])return false;
  if(stt_bot_filter_post($p,$kind))return false;
  $tid=(int)$t['tid'];$pid=(int)$p['pid'];$actor=(int)$p['uid'];
 }
 // Claim the exact event, never "the most recent post". Concurrent hooks cannot duplicate it.
 $db->write_query("INSERT IGNORE INTO ".TABLE_PREFIX."stt_events (kind,entityid,tid,pid,actor,sid) VALUES ('$kind',$entity,$tid,$pid,$actor,0)");
 if(!$db->affected_rows())return false;
 // Legacy readers see only a neutral marker. The title is resolved after viewer permission checks.
 $sid=stt_bot_shout(stt_t('text_stt_forum_activity_4eb9f9'));
 $db->update_query('stt_events',['sid'=>$sid],"kind='$kind' AND entityid=$entity");return $sid;
}

function stt_bot_thread($handler){try{stt_bot_record('thread',(int)($handler->tid??0));}catch(Throwable $e){error_log('STT bot thread: '.$e->getMessage());}}
function stt_bot_post($handler){try{stt_bot_record('post',(int)($handler->pid??0));}catch(Throwable $e){error_log('STT bot post: '.$e->getMessage());}}
function stt_bot_register(){global $user_info;try{stt_bot_record('register',(int)($user_info['uid']??0));}catch(Throwable $e){error_log('STT bot registration: '.$e->getMessage());}}

function stt_bot_reply(&$event){
 global $db,$mybb;if(!stt_bot_enabled()||empty($mybb->settings['stt_bot_commands'])||(int)($event['uid']??0)===(int)($mybb->settings['stt_bot_uid']??0)||!$db->table_exists('stt_bot_replies'))return;
 $input=trim((string)($event['text']??''));
 foreach(explode("\n",(string)($mybb->settings['stt_bot_answers']??''))as$line){$parts=explode('=>',$line,2);if(count($parts)===2&&trim($parts[0])===$input&&trim($parts[1])!==''){stt_bot_shout(stt_store_text(trim($parts[1])));return;}}
 $text=$db->escape_string($input);$r=$db->fetch_array($db->simple_select('stt_bot_replies','answer',"trigger_text='$text' AND enabled=1",['limit'=>1]));
 if($r&&$r['answer']!=='')stt_bot_shout($r['answer']);
}

function stt_event_forum_visible($forum,$perm,$thread){
 global $mybb;return $forum&&!empty($forum['active'])&&empty($forum['password'])&&!empty($perm['canview'])&&!empty($perm['canviewthreads'])&&(empty($perm['canonlyviewownthreads'])||(int)$thread['uid']===(int)$mybb->user['uid']);
}

function stt_event_visible($thread,$post){
 global $mybb;if(!$thread||!$post||(int)$thread['visible']!==1||(int)$post['visible']!==1||(int)$post['tid']!==(int)$thread['tid']||str_starts_with((string)$thread['closed'],'moved|')||stt_bot_ignore((int)$thread['fid']))return false;
 $f=get_forum((int)$thread['fid']);if(!$f)return false;
 foreach(array_unique(array_merge(array_map('intval',explode(',',$f['parentlist'])),[(int)$f['fid']]))as$fid){
  if(!$fid)continue;$parent=get_forum($fid);$perm=forum_permissions($fid);
  if(!stt_event_forum_visible($parent,$perm,$thread))return false;
 }
 return true;
}

function stt_event_card($event){
 global $mybb;$kind=$event['kind'];$actor=get_user((int)$event['actor']);$name=$actor['username']??stt_t('text_deleted_member_471af1');
 $actorHtml=format_name(htmlspecialchars_uni($name),(int)($actor['usergroup']??1),(int)($actor['displaygroup']??0));
 if($kind==='register')return ['kind'=>$kind,'label'=>stt_t('text_new_member_30631a'),'subject'=>$name,'forum'=>'','author'=>$name,'authorHtml'=>$actorHtml,'url'=>$mybb->settings['bburl'].'/'.get_profile_link((int)$event['actor'])];
 $t=get_thread((int)$event['tid']);$p=get_post((int)$event['pid']);if(!stt_event_visible($t,$p))return false;if(stt_bot_filter_post($p,$kind,true))return false;$f=get_forum((int)$t['fid']);
 $link=$kind==='thread'?get_thread_link((int)$t['tid']):get_post_link((int)$p['pid'],(int)$t['tid']).'#pid'.(int)$p['pid'];
 return ['kind'=>$kind,'label'=>$kind==='thread'?stt_t('text_new_thread_913b10'):stt_t('text_new_reply_298911'),'subject'=>$t['subject'],'forum'=>$f['name'],'author'=>$name,'authorHtml'=>$actorHtml,'url'=>rtrim($mybb->settings['bburl'],'/').'/'.$link];
}

function sttshoutbox_upgrade_20(){
 global $db,$mybb;
 if(!$db->table_exists('stt_bot_replies'))$db->write_query('CREATE TABLE '.TABLE_PREFIX.'stt_bot_replies (id int unsigned NOT NULL AUTO_INCREMENT,trigger_text varchar(190) NOT NULL,answer text NOT NULL,enabled tinyint NOT NULL DEFAULT 1,PRIMARY KEY(id),UNIQUE KEY(trigger_text)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
 if(!$db->table_exists('stt_events'))$db->write_query('CREATE TABLE '.TABLE_PREFIX.'stt_events (kind varchar(12) NOT NULL,entityid int unsigned NOT NULL,tid int unsigned NOT NULL DEFAULT 0,pid int unsigned NOT NULL DEFAULT 0,actor int unsigned NOT NULL DEFAULT 0,sid int unsigned NOT NULL DEFAULT 0,PRIMARY KEY(kind,entityid),KEY(sid)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
 $gid=(int)$db->fetch_field($db->simple_select('settinggroups','gid',"name='sttshoutbox'"),'gid');if(!$gid)return;
 $settings=[
 'groups_view'=>[stt_t('text_groups_allowed_to_read_the_chat_03a8c0'),'groupselect','-1',stt_t('text_1_means_all_groups_with_access_to_the_forum_a9e90e')],
 'groups_write'=>[stt_t('text_groups_allowed_to_post_89f46d'),'groupselect','-1',stt_t('text_posting_always_requires_login_79f672')],
 'blocked_users'=>[stt_t('text_blocked_chat_accounts_3b0f48'),'text','',stt_t('text_comma_separated_user_ids_93af8c')],
 'minposts'=>[stt_t('text_minimum_forum_posts_required_to_chat_d1fef9'),'numeric','0',''],
 'order'=>[stt_t('text_message_order_8f7480'),stt_t('text_select_newest_newest_at_the_top_oldest_newest_at_the_8b25ce'),'newest',stt_t('text_applies_to_public_and_private_chats_refresh_the_foru_08a04f')],
 'bot_enabled'=>[stt_t('text_built_in_stt_bot_676cc3'),'yesno','1',stt_t('text_independent_stt_bot_forum_activity_notifications_a42213')],
 'bot_thread'=>[stt_t('text_bot_new_threads_a57cc7'),'yesno','1',''],
 'bot_post'=>[stt_t('text_bot_new_replies_08b0d7'),'yesno','1',''],
 'bot_register'=>[stt_t('text_bot_new_members_5cd7ba'),'yesno','0',''],
 'bot_uid'=>[stt_t('text_bot_account_id_428b28'),'numeric','0',stt_t('text_0_virtual_stt_bot_another_id_that_forum_account_5bd77d')],
 'bot_ignore'=>[stt_t('text_bot_excluded_forums_f9693f'),'text','',stt_t('text_comma_separated_forum_ids_their_subforums_are_exclud_c439e1')],
 'bot_answers'=>[stt_t('text_bot_custom_responses_83ad6a'),'textarea','',stt_t('text_one_rule_per_line_help_response_text_enable_the_opti_8ead59')],
 'bot_commands'=>[stt_t('text_bot_automatic_responses_39cb4b'),'yesno','0',stt_t('text_responses_from_the_list_above_and_saved_rules_applie_70915a')]
 ];$position=20;
 foreach($settings as$key=>$v){$name='stt_'.$key;if(!$db->fetch_field($db->simple_select('settings','sid',"name='$name'"),'sid'))$db->insert_query('settings',['name'=>$name,'title'=>$db->escape_string($v[0]),'description'=>$db->escape_string($v[3]),'optionscode'=>$db->escape_string($v[1]),'value'=>$db->escape_string($v[2]),'disporder'=>$position++,'gid'=>$gid,'isdefault'=>0]);}
 rebuild_settings();
}
