<?php
/** STTShoutbox language selection, English fallback and MyBB ACP labels. */
if(!defined('IN_MYBB')) exit;

function stt_language(){
 global $lang,$mybb;
 // MyBB resolves member, guest-cookie and ACP preferences before these calls.
 $name=(string)($lang->language??($mybb->user['language']??''));
 if($name==='')$name=(string)($mybb->settings['bblanguage']??'english');
 return preg_match('~^(polish|polski|pl)(?:[/_-]|$)~i',$name)?'polish':'english';
}

function stt_dictionary($language=null){
 static $cache=[];
 $language=$language??stt_language();$language=$language==='polish'?'polish':'english';
 if(!isset($cache[$language])){
  $l=[];$base=MYBB_ROOT.'inc/languages/';
  if(is_file($base.'english/sttshoutbox.lang.php'))require $base.'english/sttshoutbox.lang.php';
  if($language==='polish'&&is_file($base.'polish/sttshoutbox.lang.php'))require $base.'polish/sttshoutbox.lang.php';
  $cache[$language]=$l;
 }
 return $cache[$language];
}

function stt_t($key,$values=[]){
 $dictionary=stt_dictionary();$text=$dictionary[$key]??$key;
 $replace=[];foreach($values as$name=>$value)$replace['{'.$name.'}']=(string)$value;
 return strtr($text,$replace);
}

function stt_ui_dictionary(){return array_filter(stt_dictionary(),fn($key)=>str_starts_with($key,'ui_'),ARRAY_FILTER_USE_KEY);}

function stt_admin_language(){
 global $lang;
 if(!defined('IN_ADMINCP'))return;
 foreach(stt_dictionary() as$key=>$value)if(str_starts_with($key,'setting_'))$lang->$key=$value;
}
