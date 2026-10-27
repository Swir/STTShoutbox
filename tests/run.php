<?php
// Isolated installer tests: in-memory database double; no hosting database connection.
define('IN_MYBB',1);define('IN_ADMINCP',1);define('TABLE_PREFIX','test_');require __DIR__.'/sandbox.php';define('MYBB_ROOT',$sandbox.'/');
$tests=[];$mode=$argv[1]??'tests';$previewPath=__DIR__.'/setup-preview-'.($argv[2]??'english').'.html';$adminActions=[];
function ck($ok,$label){global$tests;if(!$ok)throw new Exception($label);$tests[]=$label;}
function htmlspecialchars_uni($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
function rebuild_settings(){global$db,$mybb;foreach($db->rows['settings'] as$r)$mybb->settings[$r['name']]=stripslashes($r['value']);$db->rebuilds++;}
function find_replace_templatesets($name,$pattern,$replace,$x=1){global$index;$index=preg_replace($pattern,$replace,$index);}
function check_admin_permissions($p){global$permission;if(!$permission)throw new RuntimeException('permission denied');}
function verify_post_check($key,$silent=true){return $key==='test-token';}
function log_admin_action(...$args){global$adminActions;$adminActions[]=$args;}
function flash_message($s,$type){global$flash;$flash=[$s,$type];}
class Redirect extends Exception{}
function admin_redirect($url){throw new Redirect($url);}
class PageDouble{function add_breadcrumb_item($s){} function output_header($s){echo '<!doctype html><meta charset="utf-8"><title>'.htmlspecialchars_uni($s).'</title><style>body{font:14px system-ui;margin:35px;color:#253448;background:#fff}a{color:#006dae}summary{cursor:pointer}</style>';}function output_inline_error($e){echo '<p role="alert">'.htmlspecialchars_uni(implode(' ',$e)).'</p>';}function output_footer(){}}
class MyBBdouble{public $version_code=1841,$settings=['bburl'=>'https://example.invalid/forum'],$request_method='get',$input=[],$post_code='test-token',$user=[],$usergroup=[];function get_input($k){return $this->input[$k]??'';}}
class TemplatesDouble{public $cache=['index'=>'{$header}{$forums}{$footer}'];function get($name){return $this->cache[$name]??'';}}
class ResultDouble{public $row;function __construct($r){$this->row=$r;}}
class DbDouble{
 public $type='mysqli',$tables=[],$rows=['settings'=>[],'settinggroups'=>[]],$ddl=[],$mutations=0,$rebuilds=0;
 function table_exists($t){return isset($this->tables[$t]);}
 function field_exists($f,$t){return isset($this->tables[$t][$f]);}
 function add_column($t,$f,$sql){$this->tables[$t][$f]=true;$this->mutations++;}
 function write_query($sql){if(!preg_match('/^CREATE TABLE test_(\w+)/',$sql,$m))throw new Exception('Unexpected query');$this->tables[$m[1]]=['modified'=>str_contains($sql,'modified')];$this->ddl[]=$sql;$this->mutations++;}
 function simple_select($t,$fields='*',$where=''){$row=false;foreach($this->rows[$t]??[] as$r){if(preg_match("/name='([^']+)'/",$where,$m)&&$r['name']===$m[1]){$row=$r;break;}}return new ResultDouble($row);}
 function fetch_field($res,$key){return $res->row[$key]??false;}
 function escape_string($s){return addslashes($s);}
 function insert_query($t,$row){$id=count($this->rows[$t])+1;$row[$t==='settings'?'sid':'gid']=$id;$this->rows[$t][]=$row;$this->mutations++;return$id;}
 function update_query($t,$values,$where){preg_match("/name='([^']+)'/",$where,$m);foreach($this->rows[$t] as&$r)if($r['name']===$m[1])$r=array_merge($r,$values);$this->mutations++;}
 function delete_query($t,$where){preg_match('/gid=(\d+)/',$where,$m);$this->rows[$t]=array_values(array_filter($this->rows[$t],fn($r)=>$r['gid']!=(int)$m[1]));$this->mutations++;}
}
function bot_test_snapshot(){global$db,$mybb,$adminActions;return ['rows'=>$db->rows,'tables'=>$db->tables,'ddl'=>$db->ddl,'mutations'=>$db->mutations,'rebuilds'=>$db->rebuilds,'settings'=>$mybb->settings,'actions'=>$adminActions];}
$plugins=new class{public $hooks=[];function add_hook($h,$f,$p=10){$this->hooks[$h]=$f;}};
$db=new DbDouble;$mybb=new MyBBdouble;$page=new PageDouble;$permission=true;$index='{$header}{$forums}{$footer}';
$lang=(object)['language'=>($argv[2]??'english').'/admin'];
require MYBB_ROOT.'inc/plugins/sttshoutbox.php';
require MYBB_ROOT.'inc/plugins/sttshoutbox/service.php';
try{
 $english=stt_dictionary('english');$polish=stt_dictionary('polish');
 ck(array_keys($english)===array_keys($polish),'Matching Polish and English language keys');
 foreach($english as$key=>$value){preg_match_all('/\{([a-zA-Z_]+)\}/',$value,$enTokens);preg_match_all('/\{([a-zA-Z_]+)\}/',$polish[$key],$plTokens);sort($enTokens[0]);sort($plTokens[0]);ck($enTokens[0]===$plTokens[0],'Placeholders match: '.$key);}
 foreach(['polish'=>'polish','polish/admin'=>'polish','pl_PL'=>'polish','english'=>'english','english/admin'=>'english','german'=>'english','../../bad'=>'english']as$input=>$expected){$lang->language=$input;ck(stt_language()===$expected,'Language resolution: '.$input);}
 $lang->language='polish/admin';stt_admin_language();ck($lang->setting_stt_private==='Rozmowy prywatne','Polish ACP settings');
 ck(stt_t('message_length',['count'=>123])==='Wpisz wiadomość o długości do 123 znaków.','Polish error placeholder');
 $lang->language='english/admin';stt_admin_language();ck($lang->setting_stt_private==='Private conversations','English ACP settings');
 ck(stt_t('message_length',['count'=>123])==='Enter a message of up to 123 characters.','English error placeholder');
 $frontend=stt_ui_dictionary();$expectedFrontend=array_filter($english,fn($key)=>str_starts_with($key,'ui_'),ARRAY_FILTER_USE_KEY);
 ck($frontend===$expectedFrontend&&count($frontend)>0,'Frontend dictionary includes all UI strings and no server or administrator strings');
 foreach(['ui_sound_settings','ui_sound_volume','ui_sound_preview','ui_sound_ready','ui_sound_note','ui_sound_unavailable','ui_sound_enabled','ui_sound_close']as$key){ck(isset($frontend[$key])&&trim($frontend[$key])!=='','Frontend sound control is translated: '.$key);}
 $mybb->user=['uid'=>0];$mybb->usergroup=['canview'=>0];
 try{stt_send(0,'x');throw new Exception('Expected blocked post');}catch(RuntimeException$e){ck($e->getMessage()==='You cannot post in this chat.','English API denial');}
 $lang->language='polish';try{stt_send(0,'x');throw new Exception('Expected blocked post');}catch(RuntimeException$e){ck($e->getMessage()==='Nie możesz pisać na czacie.','Polish API denial');}
 $lang->language=($argv[2]??'english').'/admin';
 sttshoutbox_activate();
 ck(sttshoutbox_is_installed(),'Fresh installation creates setting group');
 ck(count($db->tables)===7,'Fresh installation creates seven own tables');
 ck($mybb->settings['stt_appearance']==='auto','Fresh installation chooses automatic appearance');
 ck($mybb->settings['stt_private']==='1'&&$mybb->settings['stt_bot_enabled']==='1','Private chat and bot enabled on fresh install');
 foreach(stt_bot_filter_defaults()as$key=>$value)ck(($mybb->settings['stt_'.$key]??null)===$value,'Fresh installation creates bot option: '.$key);
 ck(substr_count($index,'{$sttshoutbox}')===1,'Activation places exactly one chat');
 $n=count($db->rows['settings']);sttshoutbox_activate();
 ck(count($db->rows['settings'])===$n&&count($db->ddl)===7,'Repeated activation does not duplicate settings or tables');
 ck(substr_count($index,'{$sttshoutbox}')===1,'Repeated activation does not duplicate placement');
 $required=MYBB_ROOT.'jscripts/sttshoutbox.js';rename($required,$required.'.test');
 ck(count(stt_setup_requirements())===1,'Incomplete upload diagnosed');rename($required.'.test',$required);
 $soundFile=MYBB_ROOT.'jscripts/sttshoutbox/notify.wav';
 ck(is_file($soundFile),'Installation package includes notification audio');
 $wav=file_get_contents($soundFile);
 ck(strlen($wav)>44&&substr($wav,0,4)==='RIFF'&&substr($wav,8,4)==='WAVE','Bundled notification is a nonempty WAV asset');
 rename($soundFile,$soundFile.'.test');$soundIssues=stt_setup_requirements();
 ck(count($soundIssues)===1&&str_contains($soundIssues[0],'jscripts/sttshoutbox/notify.wav'),'Incomplete audio upload is diagnosed by the installer');rename($soundFile.'.test',$soundFile);
 $info=sttshoutbox_info();
 ck($info['version']==='2.5.2','Plugin reports release 2.5.2');
 ck(str_contains($info['description'],'action=sttshoutbox_setup')&&str_contains($info['description'],'action=sttshoutbox_bot')&&str_contains($info['description'],'module=config-settings'),'Installed plugin offers organised setup, bot and advanced configuration links');
 ck(!str_contains($info['description'],'Samodzielny czat STT:')&&!str_contains($info['description'],'Standalone STT chat:'),'Plugin entry omits the old feature sentence');
 // Render actual public config from both domain-root and nested forum installations.
 $renderBefore=[$mybb->settings,$mybb->user,$mybb->usergroup];$templates=new TemplatesDouble;
 $mybb->user=['uid'=>7,'username'=>'Example member'];$mybb->usergroup=['canview'=>1];
 foreach(['https://example.invalid','https://example.invalid/community/forum/']as$baseUrl){
  $mybb->settings['bburl']=$baseUrl;sttshoutbox_render();
  ck(preg_match('~<script type="application/json" id="stt-config">(.*?)</script>~s',$sttshoutbox,$configMatch)===1,'Public configuration renders for '.$baseUrl);
  $renderConfig=json_decode($configMatch[1],true,512,JSON_THROW_ON_ERROR);
  ck(($renderConfig['soundUrl']??null)===rtrim($baseUrl,'/').'/jscripts/sttshoutbox/notify.wav?v=2.5.2','Bundled sound URL follows this forum installation path');
  ck(($renderConfig['soundVolume']??null)===40,'Sound has a moderate default volume');
  ck(isset($renderConfig['i18n'])&&!array_diff_key($renderConfig['i18n'],$expectedFrontend),'Public config does not leak administrator translations');
  ck(str_contains($sttshoutbox,'sttshoutbox.js?v=2.5.2')&&str_contains($sttshoutbox,'sttshoutbox.css?v=2.5.2'),'Fresh script and style versions are requested together');
 }
 [$mybb->settings,$mybb->user,$mybb->usergroup]=$renderBefore;
 $valid=['title'=>'Nasza ekipa 😀','appearance'=>'light','accent'=>'#E5B800','height'=>'360','density'=>'comfortable','order'=>'oldest','private'=>'0','bot_enabled'=>'1'];
 $out=stt_setup_validate($valid);ck($out['accent']==='#e5b800'&&$out['title']===$valid['title'],'Setup validates Unicode and normalizes colour');
 foreach(['height'=>'999','title'=>'','accent'=>'red;url(x)','appearance'=>'evil','private'=>'yes','order'=>[]]as$k=>$bad){try{stt_setup_validate(array_replace($valid,[$k=>$bad]));throw new Exception('Accepted invalid '.$k);}catch(InvalidArgumentException$e){$tests[]='Reject invalid '.$k;}}
 $clean=stt_setup_appearance(['stt_title'=>'','stt_accent'=>'<x>','stt_height'=>'900','stt_appearance'=>'bad','stt_density'=>'bad']);
 ck($clean['height']===600&&$clean['title']==='Shoutbox'&&$clean['accent']==='#078dcc'&&$clean['appearance']==='classic'&&$clean['density']==='compact','Runtime safely normalizes advanced settings');
 $raw='{$header}<main>{$forums}</main>{$footer}';$placed=stt_setup_place($raw);
 ck(substr_count($placed,'{$sttshoutbox}')===1&&stt_setup_place($placed)===$placed,'New theme automatically receives one chat');
 ck(stt_setup_place('<main>{$sttshoutbox}{$forums}</main>')==='<main>{$sttshoutbox}{$forums}</main>','Manual placement preserved');
 ck(stt_setup_place('<main>No standard header</main>')==='<main>No standard header</main>','Unknown layout not modified blindly');
 // Upgrade simulation: retain old values and fake history while adding only new settings.
 $db->rows['settings']=array_values(array_filter($db->rows['settings'],fn($r)=>!array_key_exists(substr($r['name'],4),stt_setup_fields())));
 $db->update_query('settings',['value'=>'49,94'],"name='stt_bot_ignore'");rebuild_settings();sttshoutbox_upgrade_22();
 ck($mybb->settings['stt_appearance']==='classic'&&$mybb->settings['stt_bot_ignore']==='49,94','Upgrade preserves classic appearance and forum exclusions');
 sttshoutbox_deactivate();ck(!str_contains($index,'{$sttshoutbox}')&&count($db->tables)===7,'Deactivate removes owned template marker, keeps data');
 sttshoutbox_activate();
 if($mode==='preview'){$mybb->input=['action'=>'sttshoutbox_setup'];ob_start();register_shutdown_function(function()use($previewPath){file_put_contents($previewPath,ob_get_contents());ob_end_clean();});stt_setup_admin();}
 $mybb->input=['action'=>'sttshoutbox_setup','my_post_key'=>'test-token']+$valid;$mybb->request_method='post';
 try{stt_setup_admin();}catch(Redirect$e){ck(str_contains($e->getMessage(),'sttshoutbox_setup'),'Successful setup redirects back');}
 ck($mybb->settings['stt_title']===$valid['title']&&$mybb->settings['stt_private']==='0'&&$mybb->settings['stt_bot_ignore']==='49,94','Setup saves only its allowlisted fields');
 $permission=false;try{stt_setup_admin();throw new Exception('Missing permissions rejection');}catch(RuntimeException$e){ck($e->getMessage()==='permission denied','Admin settings permission enforced');}$permission=true;
 // Output modes exit through the real handler; assert writes in shutdown.
 if($mode==='csrf'){
  $mybb->input['my_post_key']='invalid';$mybb->input['title']='UNAUTHORIZED';$before=$db->rows;
  ob_start();register_shutdown_function(function()use($before){global$db;ob_end_clean();echo json_encode(['csrfRejected'=>$db->rows===$before]);});stt_setup_admin();
 }
 // Real bot controller save: add missing options, retain unrelated settings, log once.
 $botValid=stt_bot_admin_values();
 $botValid=array_replace($botValid,['bot_filter_min_words'=>'13','bot_filter_mode'=>'only','bot_filter_scope'=>'all','bot_filter_phrases'=>"dzięki\nthanks\nbrawo",'bot_filter_links'=>'1','bot_filter_existing'=>'0','bot_ignore'=>'49,94','bot_register'=>'1']);
 $beforeBot=$mybb->settings;$beforeBotTables=$db->tables;$beforeBotActions=count($adminActions);
 $db->rows['settings']=array_values(array_filter($db->rows['settings'],fn($r)=>$r['name']!=='stt_bot_filter_phrases'));
 unset($mybb->settings['stt_bot_filter_phrases']);
 $mybb->input=['action'=>'sttshoutbox_bot','my_post_key'=>'test-token','bot_action'=>'save']+$botValid+['title'=>'UNAUTHORIZED TITLE','interval'=>'999','stt_private'=>'1','unrelated'=>'not saved'];
 $mybb->request_method='post';$redirected=false;
 try{stt_bot_admin();}catch(Redirect$e){$redirected=str_contains($e->getMessage(),'sttshoutbox_bot');}
 ck($redirected,'Bot save redirects to its control panel');
 foreach($botValid as$key=>$value)ck(($mybb->settings['stt_'.$key]??null)===$value,'Bot controller saves validated option: '.$key);
 foreach($beforeBot as$key=>$value)if(!array_key_exists(substr($key,4),$botValid))ck(($mybb->settings[$key]??null)===$value,'Bot controller preserves unrelated setting: '.$key);
 ck($db->tables===$beforeBotTables,'Bot save preserves all conversation tables');
 ck(count($adminActions)===$beforeBotActions+1&&($flash[1]??null)==='success','Successful bot save logs once and confirms success');
 ck(!isset($mybb->settings['stt_unrelated']),'Unknown bot form fields never become settings');
 $before=bot_test_snapshot();$mybb->input=['action'=>'different_action'];stt_bot_admin();ck(bot_test_snapshot()===$before,'Bot controller ignores unrelated actions');
 if($mode==='bot-permission'){
  $permission=false;
  foreach(['get','post']as$method){
   $mybb->request_method=$method;$mybb->input=['action'=>'sttshoutbox_bot','my_post_key'=>'test-token','bot_action'=>'save']+$botValid;$before=bot_test_snapshot();
   try{stt_bot_admin();throw new Exception('Bot permission check missing');}catch(RuntimeException$e){ck($e->getMessage()==='permission denied','Bot '.$method.' requires admin settings permission');}
   ck(bot_test_snapshot()===$before,'Unauthorized bot '.$method.' cannot write');
  }
  echo json_encode(['stage'=>'passed','mode'=>$mode,'permissionRejected'=>true,'php'=>PHP_VERSION,'tests'=>$tests],JSON_UNESCAPED_UNICODE);exit;
 }
 if(in_array($mode,['bot-preview','bot-test','bot-csrf','bot-invalid'],true)){
  $sampleMarker='</textarea><script>STT_SAMPLE_XSS</script> dzięki';
  $mybb->input=['action'=>'sttshoutbox_bot','my_post_key'=>'test-token','bot_action'=>'test','bot_sample'=>$sampleMarker,'bot_kind'=>'post']+array_replace($botValid,['bot_filter_mode'=>'contains','bot_filter_min_words'=>'10','bot_filter_existing'=>'1','bot_filter_phrases'=>"dzięki\n</textarea><script>STT_PHRASE_XSS</script>"]);
  $mybb->request_method='post';
  if($mode==='bot-preview'){$mybb->request_method='get';$mybb->input['bot_action']='save';}
  if($mode==='bot-csrf'){$mybb->input['my_post_key']='invalid';$mybb->input['bot_action']=$argv[3]??'save';}
  if($mode==='bot-invalid'){
   $variant=$argv[3]??'words';
   if($variant==='sample')$mybb->input['bot_sample']=['not'=>'a string'];
   elseif($variant==='action')$mybb->input['bot_action']='unknown';
   elseif($variant==='kind')$mybb->input['bot_kind']=['not'=>'a string'];
   elseif($variant==='missing')unset($mybb->input['bot_filter_thanks']);
   else{$mybb->input['bot_action']='save';$mybb->input['bot_filter_min_words']='0';}
  }
  $before=bot_test_snapshot();$botPreviewPath=__DIR__.'/bot-preview-'.($argv[2]??'english').'.html';
  ob_start();register_shutdown_function(function()use($before,$mode,$botPreviewPath){
   global$tests;$html=ob_get_clean();
   try{
    ck(bot_test_snapshot()===$before,'Bot '.$mode.' performs no writes, cache rebuilds or admin log changes');
    ck(str_contains($html,'id="stt-bot_filter_min_words"')&&str_contains($html,'name="bot_action" value="save"'),'Bot panel renders configuration and save control');
    ck(!str_contains($html,'<script>STT_SAMPLE_XSS</script>')&&!str_contains($html,'<script>STT_PHRASE_XSS</script>'),'Submitted sample and phrase markup are escaped');
    ck(!str_contains($html,'Warning:')&&!str_contains($html,'Deprecated:'),'Bot rendering does not emit PHP warnings');
    if($mode==='bot-preview'){
     ck(!str_contains($html,'role="status"'),'GET preview does not run a test or save');
     if(file_put_contents($botPreviewPath,$html)===false)throw new Exception('Cannot write bot preview');
     ck(is_file($botPreviewPath),'Bot preview artifact created');
    }elseif($mode==='bot-test'){
     ck(str_contains($html,'role="status"')&&str_contains($html,htmlspecialchars_uni(stt_t('bot_result_blocked'))),'Tester reports blocked sample using submitted unsaved controls');
     ck(str_contains($html,htmlspecialchars_uni(stt_t('bot_result_unsaved'))),'Tester explicitly confirms no settings were saved');
    }else{
     ck(str_contains($html,'role="alert"')&&!str_contains($html,'role="status"'),'Rejected bot request shows an error without running a test');
    }
    echo json_encode(['stage'=>'passed','mode'=>$mode,'readOnly'=>true,'php'=>PHP_VERSION,'tests'=>$tests],JSON_UNESCAPED_UNICODE);
   }catch(Throwable$e){echo json_encode(['stage'=>'failed','mode'=>$mode,'error'=>$e->getMessage(),'tests'=>$tests],JSON_UNESCAPED_UNICODE);exit(1);}
  });
  stt_bot_admin();
 }
 sttshoutbox_uninstall();ck(!$db->rows['settings']&&!$db->rows['settinggroups']&&count($db->tables)===7,'Uninstall clears settings and preserves conversation tables');
 echo json_encode(['stage'=>'passed','php'=>PHP_VERSION,'tests'=>$tests],JSON_UNESCAPED_UNICODE);
}catch(Throwable$e){echo json_encode(['stage'=>'failed','error'=>$e->getMessage(),'line'=>$e->getLine(),'tests'=>$tests]);exit(1);}
