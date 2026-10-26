<?php
/** Configurable activity notifications. No forum post or chat history is modified. */
if(!defined('IN_MYBB')) exit;

function stt_bot_filter_defaults(){
 return ['bot_filter_thanks'=>'1','bot_filter_mode'=>'contains','bot_filter_phrases'=>"dzięki\ndziękuję\ndziękujemy\nthanks\nthank you\nthx",'bot_filter_short'=>'1','bot_filter_min_words'=>'10','bot_filter_scope'=>'replies','bot_filter_links'=>'0','bot_filter_existing'=>'1'];
}

function stt_bot_filter_fields(){
 $fields=[];$defaults=stt_bot_filter_defaults();
 foreach($defaults as$key=>$value){
  $type=$key==='bot_filter_phrases'?'textarea':($key==='bot_filter_min_words'?'numeric':'yesno');
  if($key==='bot_filter_mode')$type="select\nonly=".stt_t('bot_mode_only')."\ncontains=".stt_t('bot_mode_contains');
  if($key==='bot_filter_scope')$type="select\nreplies=".stt_t('bot_scope_replies')."\nall=".stt_t('bot_scope_all');
  $fields[$key]=[stt_t('setting_stt_'.$key),$type,$value,stt_t('setting_stt_'.$key.'_desc')];
 }
 return $fields;
}

function sttshoutbox_upgrade_24(){
 global $db;
 $gid=(int)$db->fetch_field($db->simple_select('settinggroups','gid',"name='sttshoutbox'"),'gid');
 if(!$gid)return;
 $changed=false;$position=40;
 foreach(stt_bot_filter_fields() as$key=>$field){
  $name='stt_'.$key;
  if(!$db->fetch_field($db->simple_select('settings','sid',"name='$name'"),'sid')){
   $db->insert_query('settings',['name'=>$name,'title'=>$db->escape_string($field[0]),'optionscode'=>$db->escape_string($field[1]),'value'=>$db->escape_string($field[2]),'description'=>$db->escape_string($field[3]),'disporder'=>$position,'gid'=>$gid,'isdefault'=>0]);$changed=true;
  }
  $position++;
 }
 if($changed)rebuild_settings();
}

function stt_bot_filter_config($settings=null){
 global $mybb;$settings=$settings??$mybb->settings;$out=stt_bot_filter_defaults();
 foreach($out as$key=>$default){$v=$settings['stt_'.$key]??$default;if(is_scalar($v))$out[$key]=(string)$v;}
 foreach(['bot_filter_thanks','bot_filter_short','bot_filter_links','bot_filter_existing'] as$key)$out[$key]=$out[$key]==='1'?'1':'0';
 $out['bot_filter_mode']=$out['bot_filter_mode']==='only'?'only':'contains';
 $out['bot_filter_scope']=$out['bot_filter_scope']==='all'?'all':'replies';
 $out['bot_filter_min_words']=(string)max(1,min(100,(int)$out['bot_filter_min_words']));
 $out['bot_filter_phrases']=mb_substr($out['bot_filter_phrases'],0,4000,'UTF-8');
 return $out;
}

function stt_bot_filter_fold($text){
 $text=mb_strtolower((string)$text,'UTF-8');
 $text=strtr($text,['ą'=>'a','ć'=>'c','ę'=>'e','ł'=>'l','ń'=>'n','ó'=>'o','ś'=>'s','ź'=>'z','ż'=>'z']);
 return trim((string)preg_replace('/[^\p{L}\p{N}]+/u',' ',$text));
}

/** Remove quoted material with a stack, including nested and unfinished quotes. */
function stt_bot_filter_unquote($message){
 $parts=preg_split('~(\[/?quote(?:=[^\]\r\n]*)?\])~iu',$message,-1,PREG_SPLIT_DELIM_CAPTURE);
 if($parts===false)return $message;
 $depth=0;$out='';
 foreach($parts as$part){
  if(preg_match('~^\[quote(?:=[^\]\r\n]*)?\]$~iu',$part)){$depth++;$out.=' ';}
  elseif(preg_match('~^\[/quote\]$~iu',$part)){$depth=max(0,$depth-1);$out.=' ';}
  elseif(!$depth)$out.=$part;
 }
 return $out;
}

function stt_bot_filter_text($message){
 $text=stt_bot_filter_unquote(html_entity_decode((string)$message,ENT_QUOTES|ENT_HTML5,'UTF-8'));
 // URLs/code are payload, not prose. Their paths must not match keywords or inflate word counts.
 $payload=(bool)preg_match('~(?:https?://|ftp://|www\.|\[(?:url|img|video|attachment|code|php)(?:[=\s\]]))~iu',$text);
 $text=preg_replace('~\[(code|php|img|video)(?:=[^\]]*)?\].*?\[/\1\]~isu',' ',$text)??$text;
 $links=(bool)preg_match('~(?:https?://|ftp://|www\.|\[(?:url|attachment)(?:[=\s\]]))~iu',$text);
 $text=preg_replace('~\[attachment(?:=[^\]]*)?\].*?\[/attachment\]~isu',' ',$text)??$text;
 $text=preg_replace('~\[/?[a-z][a-z0-9_]*(?:[=\s][^\]\r\n]*)?\]~iu',' ',$text)??$text;
 $text=strip_tags($text);
 $text=preg_replace('~(?:https?://|ftp://|www\.)[^\s<>\[\]]+~iu',' ',$text)??$text;
 return ['text'=>$text,'normalized'=>stt_bot_filter_fold($text),'payload'=>$payload,'links'=>$links];
}

function stt_bot_filter_decide($message,$kind='post',$settings=null,$hasAttachment=false){
 $config=stt_bot_filter_config($settings);$body=stt_bot_filter_text($message);
 preg_match_all('/[\p{L}\p{N}]+(?:[\x{2019}\x{0027}\-][\p{L}\p{N}]+)*/u',$body['text'],$tokens);
 $result=['blocked'=>false,'reasons'=>[],'words'=>count($tokens[0]),'matched'=>'','bypassed'=>false];
 if(!in_array($kind,['thread','post'],true)||($kind==='thread'&&$config['bot_filter_scope']!=='all'))return $result;
 if($config['bot_filter_thanks']==='1'){
  $phrases=array_slice(preg_split('/\R/u',$config['bot_filter_phrases'])?:[],0,100);
  foreach($phrases as$phrase){
   $needle=stt_bot_filter_fold(mb_substr(trim($phrase),0,80,'UTF-8'));if($needle==='')continue;
   if(!str_contains(' '.$body['normalized'].' ',' '.$needle.' '))continue;
   $only=$body['normalized']===$needle||stt_bot_thanks_only($body['text']);
   if($config['bot_filter_mode']==='contains'||($only&&!$body['payload']&&!$hasAttachment)){$result['reasons'][]='thanks';$result['matched']=trim($phrase);break;}
  }
 }
 if($config['bot_filter_short']==='1'&&$result['words']<(int)$config['bot_filter_min_words']){
  if($config['bot_filter_links']==='1'&&($body['links']||$hasAttachment))$result['bypassed']=true;
  else $result['reasons'][]='short';
 }
 $result['blocked']=(bool)$result['reasons'];return $result;
}

function stt_bot_filter_post($post,$kind,$existing=false){
 $config=stt_bot_filter_config();
 if($existing&&$config['bot_filter_existing']!=='1')return false;
 $attached=false;
 // Only query for unembedded attachments when their exception could change the outcome.
 if(!$attached&&$config['bot_filter_links']==='1'&&!empty($post['pid'])){
  global $db;$attached=(bool)$db->fetch_field($db->simple_select('attachments','aid','pid='.(int)$post['pid'].' AND visible=1',['limit'=>1]),'aid');
 }
 return stt_bot_filter_decide($post['message']??'',$kind,null,$attached)['blocked'];
}

function stt_bot_admin_values(){
 global $mybb;$out=stt_bot_filter_config();
 foreach(['bot_enabled'=>'1','bot_thread'=>'1','bot_post'=>'1','bot_register'=>'0','bot_ignore'=>''] as$key=>$default)$out[$key]=(string)($mybb->settings['stt_'.$key]??$default);
 return $out;
}

function stt_bot_admin_validate($input){
 $out=[];
 foreach(array_keys(stt_bot_admin_values()) as$key){if(!isset($input[$key])||!is_string($input[$key]))throw new InvalidArgumentException(stt_t('bot_error_fields'));$out[$key]=trim($input[$key]);}
 foreach(['bot_enabled','bot_thread','bot_post','bot_register','bot_filter_thanks','bot_filter_short','bot_filter_links','bot_filter_existing'] as$key)if(!in_array($out[$key],['0','1'],true))throw new InvalidArgumentException(stt_t('bot_error_fields'));
 if(!in_array($out['bot_filter_mode'],['only','contains'],true)||!in_array($out['bot_filter_scope'],['replies','all'],true))throw new InvalidArgumentException(stt_t('bot_error_fields'));
 if(!ctype_digit($out['bot_filter_min_words'])||(int)$out['bot_filter_min_words']<1||(int)$out['bot_filter_min_words']>100)throw new InvalidArgumentException(stt_t('bot_error_words'));
 $out['bot_filter_min_words']=(string)(int)$out['bot_filter_min_words'];
 if(mb_strlen($out['bot_filter_phrases'],'UTF-8')>4000)throw new InvalidArgumentException(stt_t('bot_error_phrases'));
 $phrases=preg_split('/\R/u',$out['bot_filter_phrases'])?:[];$clean=[];
 foreach($phrases as$phrase){$phrase=trim($phrase);if($phrase==='')continue;if(mb_strlen($phrase,'UTF-8')>80||stt_bot_filter_fold($phrase)==='')throw new InvalidArgumentException(stt_t('bot_error_phrases'));$clean[]=$phrase;}
 $clean=array_values(array_unique($clean));
 if(count($clean)>100||($out['bot_filter_thanks']==='1'&&!$clean))throw new InvalidArgumentException(stt_t('bot_error_phrases'));
 $out['bot_filter_phrases']=implode("\n",$clean);
 if(strlen($out['bot_ignore'])>2000||($out['bot_ignore']!==''&&!preg_match('/^\d+(?:\s*,\s*\d+)*\s*,?$/D',$out['bot_ignore'])))throw new InvalidArgumentException(stt_t('bot_error_forums'));
 $out['bot_ignore']=implode(',',array_unique(array_filter(array_map('intval',explode(',',$out['bot_ignore'])))));
 return $out;
}
