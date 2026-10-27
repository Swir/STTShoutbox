<?php
// Isolated real-query tests in SQLite; no forum connection or credentials.
if(PHP_SAPI!=='cli')exit;
error_reporting(E_ALL);
set_error_handler(function($s,$m,$f,$l){throw new ErrorException($m,0,$s,$f,$l);});
define('IN_MYBB',1);define('TABLE_PREFIX','test_');require __DIR__.'/sandbox.php';define('MYBB_ROOT',$sandbox.'/');
file_put_contents($sandbox.'/inc/class_parser.php','<?php class postParser { function parse_message($text,$options){ return htmlspecialchars($text,ENT_QUOTES,"UTF-8"); }}');
require MYBB_ROOT.'inc/plugins/sttshoutbox/service.php';
function stt_t($key){return $key;}
function htmlspecialchars_uni($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
function format_name($s,$g,$d){return $s;}
function is_member($groups){global $mybb;return in_array($mybb->user['usergroup'],$groups);}
function is_moderator($fid,$action,$uid){return $uid===3;}
function get_user($uid){return ['uid'=>$uid,'username'=>'Member'];}
class ModerationDb {
 public $pdo;
 function __construct(){$this->pdo=new PDO('sqlite::memory:');$this->pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);}
 function query($sql){return $this->pdo->query($sql);}
 function simple_select($table,$fields='*',$where=''){return $this->query('SELECT '.$fields.' FROM test_'.$table.($where?' WHERE '.$where:''));}
 function fetch_array($result){return $result->fetch(PDO::FETCH_ASSOC);}
 function replace_query($table,$data,$key){$columns=implode(',',array_keys($data));$values=implode(',',array_map(fn($v)=>$this->pdo->quote((string)$v),array_values($data)));$this->pdo->exec('INSERT OR REPLACE INTO test_'.$table.' ('.$columns.') VALUES ('.$values.')');}
 function update_query($table,$data,$where){$parts=[];foreach($data as$k=>$v)$parts[]=$k.'='.$this->pdo->quote((string)$v);$this->pdo->exec('UPDATE test_'.$table.' SET '.implode(',',$parts).' WHERE '.$where);}
}
$db=new ModerationDb;
foreach([
 'users'=>'uid INTEGER PRIMARY KEY,username TEXT,avatar TEXT,usergroup INTEGER,displaygroup INTEGER',
 'stt_messages'=>'id INTEGER PRIMARY KEY,uid INTEGER,text TEXT,date INTEGER,modified INTEGER',
 'stt_hidden'=>'sid INTEGER PRIMARY KEY,actor INTEGER,created INTEGER',
 'stt_events'=>'sid INTEGER PRIMARY KEY',
 'stt_reactions'=>'sid INTEGER,uid INTEGER,kind TEXT',
 'stt_dm'=>'id INTEGER PRIMARY KEY,sender INTEGER,recipient INTEGER,body TEXT,created INTEGER,modified INTEGER,deleted INTEGER'
]as$table=>$fields)$db->pdo->exec('CREATE TABLE test_'.$table.' ('.$fields.')');
for($id=1;$id<=4;$id++){$db->pdo->exec("INSERT INTO test_users VALUES ($id,'Member $id','',2,0)");$db->pdo->exec("INSERT INTO test_stt_messages VALUES ($id,$id,'Message $id',1000,0)");}
$db->pdo->exec("INSERT INTO test_stt_dm VALUES(1,2,4,'Private content',1000,0,0)");
$mybb=(object)['settings'=>['stt_private'=>1,'enablepms'=>1],'user'=>[],'usergroup'=>[]];
function user_context($uid,$extra=[]){global $mybb;$mybb->user=['uid'=>$uid,'usergroup'=>2,'postnum'=>10];$mybb->usergroup=$extra+['canview'=>1,'canusepms'=>1];}
$checks=0;function ck_mod($ok,$label){global $checks;if(!$ok)throw new RuntimeException($label);$checks++;}
function denied($op,$data,$label){try{stt_dispatch($op,$data);throw new Exception('Operation unexpectedly permitted: '.$label);}catch(RuntimeException$e){ck_mod($e->getCode()===403,$label);}}
user_context(2);ck_mod(!stt_mod(),'Ordinary member is not a moderator');
denied('delete',['id'=>1],'Member cannot remove another public message');
stt_dispatch('delete',['id'=>2]);
ck_mod(array_column(stt_rows(0),'id')===[1,3,4],'Own withdrawal leaves no public placeholder');
ck_mod(stt_removed_public('1,2,3')===[2],'Older loaded public history receives removal ID only');
ck_mod(stt_removed_public(['2'])===[]&&stt_removed_public('2) OR 1=1--')===[],'Invalid ID data cannot alter query');
ck_mod(stt_removed_public(str_repeat('1,',3001))===[],'Oversized requests are ignored');
user_context(1,['cancp'=>1]);ck_mod(stt_mod(),'Administrator receives moderation');
stt_dispatch('delete',['id'=>3]);ck_mod(!in_array(3,array_column(stt_rows(0),'id')),'Admin removal is absent from latest history');
ck_mod(!in_array(3,array_column(stt_rows(0,4),'id')),'Admin removal is absent from paginated history');
denied('delete',['id'=>1,'peer'=>4],'Administrator cannot remove unrelated private messages');
user_context(3);ck_mod(stt_mod(),'Assigned forum moderator receives moderation');
stt_dispatch('delete',['id'=>4]);ck_mod(stt_removed_public('4')===[4],'Assigned moderator can remove another public message');
user_context(4,['issupermod'=>1]);ck_mod(stt_mod(),'Super moderator receives moderation');
user_context(0,['cancp'=>1]);ck_mod(!stt_mod(),'Guest never receives moderation');denied('delete',['id'=>1],'Guest cannot delete');
user_context(1,['cancp'=>1,'isbannedgroup'=>1]);denied('delete',['id'=>1],'Banned administrator cannot delete');
user_context(1,['cancp'=>1]);$mybb->settings['stt_blocked_users']='1';denied('delete',['id'=>1],'Blocked administrator cannot delete');unset($mybb->settings['stt_blocked_users']);
user_context(2);stt_dispatch('delete',['id'=>1,'peer'=>4]);
$private=stt_rows(4);ck_mod(count($private)===1&&$private[0]['deleted']&&$private[0]['text']==='','Private participant withdrawal remains a marker');
ck_mod((int)$db->pdo->query('SELECT COUNT(*) FROM test_stt_messages')->fetchColumn()===4,'Moderation preserves original database records');
echo "$checks moderation PHP checks passed.\n";
