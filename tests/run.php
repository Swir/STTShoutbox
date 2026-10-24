<?php
// Isolated installer tests: in-memory database double; no hosting database connection.
define('IN_MYBB',1);define('IN_ADMINCP',1);define('TABLE_PREFIX','test_');require __DIR__.'/sandbox.php';define('MYBB_ROOT',$sandbox.'/');
$tests=[];$mode=$argv[1]??'tests';$previewPath=__DIR__.'/setup-preview-'.($argv[2]??'english').'.html';
function ck($ok,$label){global$tests;if(!$ok)throw new Exception($label);$tests[]=$label;}
function htmlspecialchars_uni($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
function rebuild_settings(){global$db,$mybb;foreach($db->rows['settings'] as$r)$mybb->settings[$r['name']]=stripslashes($r['value']);}
function find_replace_templatesets($name,$pattern,$replace,$x=1){global$index;$index=preg_replace($pattern,$replace,$index);}
function check_admin_permissions($p){global$permission;if(!$permission)throw new RuntimeException('permission denied');}
function verify_post_check($key,$silent=true){return $key==='test-token';}
function log_admin_action(...$args){}
function flash_message($s,$type){global$flash;$flash=[$s,$type];}
class Redirect extends Exception{}
function admin_redirect($url){throw new Redirect($url);}
class PageDouble{function add_breadcrumb_item($s){} function output_header($s){echo '<!doctype html><meta charset="utf-8"><title>'.htmlspecialchars_uni($s).'</title><style>body{font:14px system-ui;margin:35px;color:#253448;background:#fff}a{color:#006dae}summary{cursor:pointer}</style>';}function output_inline_error($e){echo '<p role="alert">'.htmlspecialchars_uni(implode(' ',$e)).'</p>';}function output_footer(){}}
class MyBBdouble{public $version_code=1841,$settings=['bburl'=>'https://example.invalid/forum'],$request_method='get',$input=[],$post_code='test-token';function get_input($k){return $this->input[$k]??'';}}
class ResultDouble{public $row;function __construct($r){$this->row=$r;}}
class DbDouble{
 public $type='mysqli',$tables=[],$rows=['settings'=>[],'settinggroups'=>[]],$ddl=[];
 function table_exists($t){return isset($this->tables[$t]);}
 function field_exists($f,$t){return isset($this->tables[$t][$f]);}
 function add_column($t,$f,$sql){$this->tables[$t][$f]=true;}
 function write_query($sql){if(!preg_match('/^CREATE TABLE test_(\w+)/',$sql,$m))throw new Exception('Unexpected query');$this->tables[$m[1]]=['modified'=>str_contains($sql,'modified')];$this->ddl[]=$sql;}
 function simple_select($t,$fields='*',$where=''){$row=false;foreach($this->rows[$t]??[] as$r){if(preg_match("/name='([^']+)'/",$where,$m)&&$r['name']===$m[1]){$row=$r;break;}}return new ResultDouble($row);}
 function fetch_field($res,$key){return $res->row[$key]??false;}
 function escape_string($s){return addslashes($s);}
 function insert_query($t,$row){$id=count($this->rows[$t])+1;$row[$t==='settings'?'sid':'gid']=$id;$this->rows[$t][]=$row;return$id;}
 function update_query($t,$values,$where){preg_match("/name='([^']+)'/",$where,$m);foreach($this->rows[$t] as&$r)if($r['name']===$m[1])$r=array_merge($r,$values);}
 function delete_query($t,$where){preg_match('/gid=(\d+)/',$where,$m);$this->rows[$t]=array_values(array_filter($this->rows[$t],fn($r)=>$r['gid']!=(int)$m[1]));}
}
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
 ck(count(stt_ui_dictionary())===60,'Only frontend strings passed to JavaScript');
 $mybb->user=['uid'=>0];$mybb->usergroup=['canview'=>0];
 try{stt_send(0,'x');throw new Exception('Expected blocked post');}catch(RuntimeException$e){ck($e->getMessage()==='You cannot post in this chat.','English API denial');}
 $lang->language='polish';try{stt_send(0,'x');throw new Exception('Expected blocked post');}catch(RuntimeException$e){ck($e->getMessage()==='Nie możesz pisać na czacie.','Polish API denial');}
 $lang->language=($argv[2]??'english').'/admin';
 sttshoutbox_activate();
 ck(sttshoutbox_is_installed(),'Fresh installation creates setting group');
 ck(count($db->tables)===7,'Fresh installation creates seven own tables');
 ck($mybb->settings['stt_appearance']==='auto','Fresh installation chooses automatic appearance');
 ck($mybb->settings['stt_private']==='1'&&$mybb->settings['stt_bot_enabled']==='1','Private chat and bot enabled on fresh install');
 ck(substr_count($index,'{$sttshoutbox}')===1,'Activation places exactly one chat');
 $n=count($db->rows['settings']);sttshoutbox_activate();
 ck(count($db->rows['settings'])===$n&&count($db->ddl)===7,'Repeated activation does not duplicate settings or tables');
 ck(substr_count($index,'{$sttshoutbox}')===1,'Repeated activation does not duplicate placement');
 $required=MYBB_ROOT.'jscripts/sttshoutbox.js';rename($required,$required.'.test');
 ck(count(stt_setup_requirements())===1,'Incomplete upload diagnosed');rename($required.'.test',$required);
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
 sttshoutbox_uninstall();ck(!$db->rows['settings']&&!$db->rows['settinggroups']&&count($db->tables)===7,'Uninstall clears settings and preserves conversation tables');
 echo json_encode(['stage'=>'passed','php'=>PHP_VERSION,'tests'=>$tests],JSON_UNESCAPED_UNICODE);
}catch(Throwable$e){echo json_encode(['stage'=>'failed','error'=>$e->getMessage(),'line'=>$e->getLine(),'tests'=>$tests]);exit(1);}
