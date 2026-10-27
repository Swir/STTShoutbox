<?php
if(!defined('IN_MYBB'))exit;
function stt_mod(){global $mybb;return !empty($mybb->user['uid'])&&(!empty($mybb->usergroup['cancp'])||!empty($mybb->usergroup['issupermod'])||(function_exists('is_moderator')&&is_moderator(0,'',(int)$mybb->user['uid'])));}
function stt_group($setting){global $mybb;$s=(string)($mybb->settings[$setting]??'-1');return $s==='-1'||($s!==''&&is_member(explode(',',$s)));}
function stt_view(){global $mybb;return !empty($mybb->usergroup['canview'])&&empty($mybb->usergroup['isbannedgroup'])&&stt_group('stt_groups_view');}
function stt_write(){global $mybb;return stt_view()&&!empty($mybb->user['uid'])&&!in_array((int)$mybb->user['uid'],array_map('intval',explode(',',(string)($mybb->settings['stt_blocked_users']??''))),true)&&stt_group('stt_groups_write')&&(int)($mybb->user['postnum']??0)>=(int)($mybb->settings['stt_minposts']??0);}
function stt_pm_allowed(){global $mybb;return stt_write()&&!empty($mybb->settings['stt_private'])&&!empty($mybb->settings['enablepms'])&&!empty($mybb->usergroup['canusepms']);}
function stt_limit(){global $mybb;return max(100,min(4000,(int)($mybb->settings['stt_limit']??2000)));}
function stt_peer($uid,$writing=false){
 global $mybb;$uid=(int)$uid;
 if(!stt_pm_allowed()||$uid<1||$uid===(int)$mybb->user['uid'])throw new RuntimeException(stt_t('text_private_conversation_unavailable_ab3a93'),403);
 $u=get_user($uid);if(!$u)throw new RuntimeException(stt_t('text_member_not_found_a22123'),404);
 if($writing){$g=user_permissions($uid);$me=(int)$mybb->user['uid'];
  if(empty($g['canusepms'])||!empty($g['isbannedgroup'])||empty($u['receivepms'])||in_array($me,array_map('intval',explode(',',$u['ignorelist']??'')),true)||in_array($uid,array_map('intval',explode(',',$mybb->user['ignorelist']??'')),true)||((!empty($mybb->settings['allowbuddyonly'])&&!empty($u['receivefrombuddy']))&&!in_array($me,array_map('intval',explode(',',$u['buddylist']??'')),true)))throw new RuntimeException(stt_t('text_this_member_does_not_accept_private_messages_from_yo_d57554'),403);
 }return $u;
}
function stt_pair($peer){global $mybb;$me=(int)$mybb->user['uid'];$peer=(int)$peer;return "((sender=$me AND recipient=$peer) OR (sender=$peer AND recipient=$me))";}
// Keep four-byte characters lossless on the forum's legacy utf8 connection.
function stt_store_text($text){return preg_replace_callback('/[\x{10000}-\x{10FFFF}]/u',fn($m)=>'&#'.mb_ord($m[0],'UTF-8').';',$text);}
function stt_read_text($text){return preg_replace_callback('/&#([0-9]{5,7});/',function($m){$n=(int)$m[1];return $n>=65536&&$n<=1114111?mb_chr($n,'UTF-8'):$m[0];},$text);}
function stt_html($text){
 require_once MYBB_ROOT.'inc/class_parser.php';$p=new postParser();
 return $p->parse_message($text,['allow_html'=>0,'allow_mycode'=>1,'allow_smilies'=>1,'allow_imgcode'=>0,'allow_videocode'=>0,'filter_badwords'=>1,'nl2br'=>1]);
}
function stt_name_html($row){return format_name(htmlspecialchars_uni((string)($row['username']??stt_t('text_deleted_member_471af1'))),(int)($row['usergroup']??1),(int)($row['displaygroup']??0));}
function stt_user_data($row){return ['uid'=>(int)$row['uid'],'name'=>(string)$row['username'],'nameHtml'=>stt_name_html($row),'avatar'=>(string)($row['avatar']??'')];}
function stt_rows($peer,$before=0){
 global $db,$mybb,$stt_next_before;$stt_next_before=0;$before=max(0,(int)$before);$me=(int)$mybb->user['uid'];
 if($peer){stt_peer($peer);$q=$db->query('SELECT m.id,m.sender AS uid,m.body AS text,m.created AS date,m.modified,m.deleted,u.username,u.avatar,u.usergroup,u.displaygroup FROM '.TABLE_PREFIX.'stt_dm m LEFT JOIN '.TABLE_PREFIX.'users u ON u.uid=m.sender WHERE '.stt_pair($peer).($before?' AND m.id<'.$before:'').' ORDER BY m.id DESC LIMIT 50');}
 else{$q=$db->query('SELECT m.id,m.uid,m.text,m.date,m.modified,u.username,u.avatar,u.usergroup,u.displaygroup,h.sid AS hidden FROM '.TABLE_PREFIX.'stt_messages m LEFT JOIN '.TABLE_PREFIX.'users u ON u.uid=m.uid LEFT JOIN '.TABLE_PREFIX.'stt_hidden h ON h.sid=m.id WHERE m.text IS NOT NULL AND h.sid IS NULL'.($before?' AND m.id<'.$before:'').' ORDER BY m.id DESC LIMIT 200');}
 $raw=[];while($r=$db->fetch_array($q))$raw[]=$r;$events=[];if(!$peer&&$raw){$eq=$db->simple_select('stt_events','*','sid IN ('.implode(',',array_map('intval',array_column($raw,'id'))).')');while($e=$db->fetch_array($eq))$events[(int)$e['sid']]=$e;}
 $rows=[];foreach($raw as$r){$stt_next_before=(int)$r['id'];$r['text']=stt_read_text($r['text']);$deleted=!empty($r['deleted'])||!empty($r['hidden']);$own=(int)$r['uid']===$me;$event=null;if(!$peer&&isset($events[(int)$r['id']])){$event=stt_event_card($events[(int)$r['id']]);if($event===false)continue;}
 $rows[]=['id'=>(int)$r['id'],'uid'=>(int)$r['uid'],'name'=>(!(int)$r['uid'])?'STT Bot':($r['username']?:stt_t('text_deleted_member_471af1')),'nameHtml'=>(!(int)$r['uid'])?'STT Bot':stt_name_html($r),'event'=>$deleted?null:$event,'avatar'=>$r['avatar']??'','html'=>$deleted?'':stt_html($r['text']),'text'=>$deleted?'':$r['text'],'date'=>(int)$r['date'],'edited'=>(int)($r['modified']??0),'canEdit'=>!$deleted&&$own&&$me>0&&$event===null&&stt_write(),'deleted'=>$deleted,'canDelete'=>!$deleted&&($peer?$own:($own||stt_mod())),'reactions'=>[]];if(count($rows)>=50)break;}
 if(!$peer&&$rows){$ids=implode(',',array_column($rows,'id'));$q=$db->query('SELECT sid,kind,COUNT(*) AS n,MAX(uid='.$me.') AS mine FROM '.TABLE_PREFIX.'stt_reactions WHERE sid IN ('.$ids.') GROUP BY sid,kind');$rx=[];while($r=$db->fetch_array($q))$rx[$r['sid']][]=['kind'=>$r['kind'],'count'=>(int)$r['n'],'mine'=>(bool)$r['mine']];foreach($rows as&$r)$r['reactions']=$rx[$r['id']]??[];unset($r);}
 return array_reverse($rows);
}
function stt_conversations(){
 global $db,$mybb;if(!stt_pm_allowed())return []; $me=(int)$mybb->user['uid'];
 $q=$db->query('SELECT IF(sender='.$me.',recipient,sender) AS peer,MAX(id) AS lastid,SUM(recipient='.$me.' AND seen=0 AND deleted=0) AS unread FROM '.TABLE_PREFIX.'stt_dm WHERE sender='.$me.' OR recipient='.$me.' GROUP BY peer ORDER BY lastid DESC LIMIT 50');
 $out=[];while($r=$db->fetch_array($q)){$u=get_user((int)$r['peer']);if($u)$out[]=stt_user_data($u)+['unread'=>(int)$r['unread']];}return $out;
}
function stt_boot(){
 global $cache,$mybb;$emotes=[];foreach((array)$cache->read('smilies')as$s){$code=explode('\n',str_replace("\r",'',(string)$s['find']))[0];$code=explode("\n",$code)[0];$src=str_replace('{theme}','images',(string)$s['image']);if(!preg_match('~^(?:https?://|[a-zA-Z0-9_./-]+$)~',$src))continue;if(!preg_match('~^https?://~',$src))$src=rtrim($mybb->settings['bburl'],'/').'/'.ltrim($src,'/');$emotes[]=['name'=>$s['name'],'code'=>$code,'src'=>$src];}return ['emotes'=>$emotes];
}
// Reconcile already loaded public history, including messages outside the latest page.
// IDs are bounded and parsed as integers; never query private conversations here.
function stt_removed_public($known){
 global $db;if(!is_string($known)||strlen($known)>6000)return [];
 $ids=[];foreach(array_slice(explode(',',$known),0,500)as$id){if(ctype_digit($id)&&(int)$id>0)$ids[]=(int)$id;}
 $ids=array_unique($ids);if(!$ids)return [];
 $q=$db->simple_select('stt_hidden','sid','sid IN ('.implode(',',$ids).')');$removed=[];
 while($r=$db->fetch_array($q))$removed[]=(int)$r['sid'];return $removed;
}
function stt_send($peer,$text){
 global $db,$mybb;if(!stt_write())throw new RuntimeException(stt_t('text_you_cannot_post_in_this_chat_ae5bc7'),403);
 if(!is_string($text))throw new RuntimeException(stt_t('text_invalid_message_content_b4ed0b'),400);$text=trim($text);if($text===''||mb_strlen($text)>stt_limit())throw new RuntimeException(stt_t('message_length',['count'=>stt_limit()]),400);
 if($peer)stt_peer($peer,true);$me=(int)$mybb->user['uid'];$lock=$db->escape_string(TABLE_PREFIX.'stt_'.$me);
 $got=$db->fetch_field($db->query("SELECT GET_LOCK('$lock',2) AS n"),'n');if((int)$got!==1)throw new RuntimeException(stt_t('text_please_wait_a_moment_and_try_again_82b83b'),429);
 try{$last=(int)$db->fetch_field($db->simple_select('stt_rate','last_sent','uid='.$me),'last_sent');$wait=max(1,(int)($mybb->settings['stt_cooldown']??3));if(time()-$last<$wait)throw new RuntimeException(stt_t('message_delay',['count'=>$wait]),429);
  if($peer)$id=$db->insert_query('stt_dm',['sender'=>$me,'recipient'=>(int)$peer,'body'=>$db->escape_string(stt_store_text($text)),'created'=>time()]);
  else{$ip=my_inet_pton(get_ip());$mybb->binary_fields['stt_messages']=['ipaddress'=>true];$id=$db->insert_query('stt_messages',['uid'=>$me,'text'=>$db->escape_string(stt_store_text($text)),'date'=>time(),'ipaddress'=>$db->escape_binary($ip)]);}
  $db->replace_query('stt_rate',['uid'=>$me,'last_sent'=>time()],'uid');
  if(!$peer){global $plugins;$event=['uid'=>$me,'text'=>$text,'shout_id'=>(int)$id,'ipaddress'=>$db->escape_binary($ip)];try{stt_bot_reply($event);$plugins->run_hooks('sttshoutbox_message_saved',$event);}catch(Throwable $e){error_log('STTShoutbox public integration: '.$e->getMessage());}}
  return ['id'=>(int)$id];
 }finally{$db->query("SELECT RELEASE_LOCK('$lock')");}
}
function stt_dispatch($op,$in){
 global $db,$mybb;$me=(int)$mybb->user['uid'];$peer=max(0,(int)($in['peer']??0));$id=max(0,(int)($in['id']??0));
 if($op==='boot')return stt_boot();
 if($op==='load'){global $stt_next_before;$rows=stt_rows($peer,$in['before']??0);return ['messages'=>$rows,'removed'=>$peer?[]:stt_removed_public($in['known']??''),'cursor'=>$stt_next_before,'conversations'=>stt_conversations(),'time'=>time()];}
 if($op==='users'){$term=trim((string)($in['q']??''));if(!$me||mb_strlen($term)<2)return []; $q=$db->simple_select('users','uid,username,avatar,usergroup,displaygroup',"username LIKE '%".$db->escape_string($db->escape_string_like(mb_substr($term,0,80)))."%'",['limit'=>12,'order_by'=>'username']);$a=[];while($r=$db->fetch_array($q))$a[]=stt_user_data($r);return $a;}
 if($op==='send')return stt_send($peer,$in['text']??'');
 if($op==='edit')return stt_edit($peer,$id,$in['text']??'', $in['expected']??null);
 if($op==='read'){stt_peer($peer);$last=max(0,(int)($in['last']??0));$db->update_query('stt_dm',['seen'=>1],"recipient=$me AND sender=$peer AND id<=$last");return [];}
 if($op==='delete'){
  if(!stt_write())throw new RuntimeException(stt_t('text_permission_denied_5aaffa'),403);
  if($peer){stt_peer($peer);$r=$db->fetch_array($db->simple_select('stt_dm','id',"id=$id AND sender=$me AND recipient=$peer"));if(!$r)throw new RuntimeException(stt_t('text_you_cannot_access_this_message_c0f3c5'),403);$db->update_query('stt_dm',['deleted'=>1],'id='.$id);}
  else{$r=$db->fetch_array($db->simple_select('stt_messages','uid','id='.$id));if(!$r||((int)$r['uid']!==$me&&!stt_mod()))throw new RuntimeException(stt_t('text_permission_denied_5aaffa'),403);$db->replace_query('stt_hidden',['sid'=>$id,'actor'=>$me,'created'=>time()],'sid');}return [];
 }
 if($op==='react'){
  if(!stt_write()||$peer)throw new RuntimeException(stt_t('text_permission_denied_5aaffa'),403);$kind=$in['kind']??'';if(!in_array($kind,['like','heart','laugh'],true))throw new RuntimeException(stt_t('text_unknown_reaction_fbc8f5'),400);
  $r=$db->fetch_array($db->query('SELECT m.id FROM '.TABLE_PREFIX.'stt_messages m LEFT JOIN '.TABLE_PREFIX."stt_hidden h ON h.sid=m.id WHERE m.id=$id AND m.text IS NOT NULL AND h.sid IS NULL"));if(!$r)throw new RuntimeException(stt_t('text_message_unavailable_01ad7a'),404);
  $event=$db->fetch_array($db->simple_select('stt_events','*','sid='.$id));if($event&&stt_event_card($event)===false)throw new RuntimeException(stt_t('text_message_unavailable_01ad7a'),404);
  $where="sid=$id AND uid=$me AND kind='".$db->escape_string($kind)."'";if($db->num_rows($db->simple_select('stt_reactions','sid',$where)))$db->delete_query('stt_reactions',$where);else $db->insert_query('stt_reactions',['sid'=>$id,'uid'=>$me,'kind'=>$kind]);return [];
 }
 throw new RuntimeException(stt_t('text_unknown_operation_f8cc4f'),400);
}

function stt_edit($peer,$id,$text,$expected){
 global $db,$mybb;if(!stt_write())throw new RuntimeException(stt_t('text_you_cannot_edit_messages_80e541'),403);
 if(!is_string($text))throw new RuntimeException(stt_t('text_invalid_message_content_b4ed0b'),400);$text=trim($text);if($text===''||mb_strlen($text)>stt_limit())throw new RuntimeException(stt_t('message_length',['count'=>stt_limit()]),400);
 if(!is_string($expected))throw new RuntimeException(stt_t('text_refresh_the_message_before_editing_9f29a5'),400);
 $me=(int)$mybb->user['uid'];$id=max(0,(int)$id);$peer=max(0,(int)$peer);
 if($peer){stt_peer($peer,true);$table='stt_dm';$field='body';$where="id=$id AND sender=$me AND recipient=$peer AND deleted=0";}
 else{$table='stt_messages';$field='text';$where="id=$id AND uid=$me AND text IS NOT NULL AND id NOT IN (SELECT sid FROM ".TABLE_PREFIX."stt_hidden) AND id NOT IN (SELECT sid FROM ".TABLE_PREFIX."stt_events)";}
 $row=$db->fetch_array($db->simple_select($table,$field.',modified',$where));if(!$row)throw new RuntimeException(stt_t('text_you_can_only_edit_your_own_messages_that_have_not_be_845691'),403);
 if(stt_read_text($row[$field])!==$expected)throw new RuntimeException(stt_t('text_the_message_has_already_changed_cancel_editing_and_o_8e674f'),409);
 if($text===stt_read_text($row[$field]))return ['id'=>$id,'edited'=>(int)($row['modified']??0)];
 $now=time();$db->update_query($table,[$field=>$db->escape_string(stt_store_text($text)),'modified'=>$now],$where." AND BINARY $field='".$db->escape_string($row[$field])."'");
 if(!$db->affected_rows())throw new RuntimeException(stt_t('text_the_message_changed_while_saving_open_the_editor_aga_3efca8'),409);
 return ['id'=>$id,'edited'=>$now];
}
