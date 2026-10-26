<?php
// No forum/database connection: classification and event-hook regression checks.
if(PHP_SAPI!=='cli')exit;
define('IN_MYBB',1);define('TABLE_PREFIX','test_');
require $argv[1]??dirname(__DIR__).'/Upload/inc/plugins/sttshoutbox/bot.php';
$checks=0;
function check_bot($ok,$label){global $checks;if(!$ok)throw new RuntimeException($label);$checks++;}
$thanks=['dzięki','DZIĘKI!!!','dzieki','Dziękuję.','Dziękuję bardzo!','Bardzo dziękuję','Wielkie dzięki za listę','[b][color=#ff0000]dzięki[/color][/b]','&nbsp;dzięki&nbsp;','Dzięki 👍🙂',"\ndzięki\r\n",'serdecznie dziękuję za udostępnienie','Thanks!','Thank you very much','thx','Thanks for sharing'];
foreach($thanks as $body)check_bot(stt_bot_thanks_only($body),'Acknowledgement: '.$body);
$content=['Dzięki, ale lista nie działa.','Dziękuję, dodałem nową listę.','Nowa lista do pobrania','Aktualizacja: działa ponownie','Thanks, the link has expired.','Dzięki https://example.test/lista','[url=https://example.test]dzięki[/url]','[quote]dzięki[/quote]Nie działa','[code]thanks[/code]','[hide]nowa lista[/hide] dzięki','dzięki 12345','Dzięki za pomoc. Mam jeszcze pytanie.','',str_repeat('dzięki ',400)];
foreach($content as $body)check_bot(!stt_bot_thanks_only($body),'Preserve substantive/structured reply');
class BotDbDouble{
 public $writes=[];
 function write_query($q){$this->writes[]=['event',$q];}
 function affected_rows(){return 1;}
 function insert_query($t,$row){$this->writes[]=[$t,$row];return 99;}
 function update_query($t,$row,$where){$this->writes[]=[$t,$row,$where];}
 function escape_string($s){return addslashes($s);}
 function escape_binary($s){return $s;}
}
function get_post($id){global $posts;return $posts[$id]??false;}
function get_thread($id){global $thread;return $id===$thread['tid']?$thread:false;}
function get_forum($id){return ['fid'=>$id,'active'=>1,'parentlist'=>(string)$id,'password'=>'','name'=>'Test forum'];}
function forum_permissions($id){global $canView;return ['canview'=>$canView,'canviewthreads'=>$canView,'canonlyviewownthreads'=>false];}
function get_user($id){return ['uid'=>$id,'username'=>'TestUser','usergroup'=>2,'displaygroup'=>0];}
function format_name($s,$g,$d){return $s;}
function htmlspecialchars_uni($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
function get_thread_link($id){return 'showthread.php?tid='.$id;}
function get_post_link($pid,$tid){return 'showthread.php?tid='.$tid.'&pid='.$pid;}
function my_inet_pton($s){return inet_pton($s);}
function stt_t($key){return $key;}
$db=new BotDbDouble;
$mybb=(object)['settings'=>['stt_enabled'=>1,'stt_bot_enabled'=>1,'stt_bot_post'=>1,'stt_bot_thread'=>1,'stt_bot_ignore'=>'','bburl'=>'https://example.test'],'user'=>['uid'=>1],'binary_fields'=>[]];
// Legacy acknowledgement-only mode remains available alongside the new defaults.
$mybb->settings['stt_bot_filter_short']='0';
$mybb->settings['stt_bot_filter_mode']='only';
$canView=true;
$thread=['tid'=>13011,'firstpost'=>10,'fid'=>82,'uid'=>1,'visible'=>1,'closed'=>'','subject'=>'Test topic'];
$posts=[10=>['pid'=>10,'tid'=>13011,'uid'=>1,'visible'=>1,'message'=>'[hide]A new contribution[/hide]'],11=>['pid'=>11,'tid'=>13011,'uid'=>2788,'visible'=>1,'message'=>'dzięki'],12=>['pid'=>12,'tid'=>13011,'uid'=>2788,'visible'=>1,'message'=>'Dzięki, ale lista nie działa.']];
check_bot(stt_bot_record('post',11)===false&&count($db->writes)===0,'Thanks does not create an event or chat row');
check_bot(stt_bot_record('post',12)===99&&count($db->writes)===3,'Substantive reply still creates an event and chat row');
$db->writes=[];
stt_bot_post((object)['pid'=>11]);check_bot(count($db->writes)===0,'Actual post hook skips thanks');
$posts[10]['message']='dzięki';
check_bot(stt_bot_record('thread',13011)===99,'New topics are never suppressed by reply filter');
$db->writes=[];
check_bot(stt_bot_record('post',10)===false&&count($db->writes)===0,'First post is not duplicated as a reply');
$event=['kind'=>'post','tid'=>13011,'pid'=>11,'actor'=>2788];
check_bot(stt_event_card($event)===false,'Previously recorded thanks card is hidden without deleting history');
$event['pid']=12;check_bot(stt_event_card($event)['kind']==='post','Substantive reply card still visible');
$event['kind']='thread';$event['pid']=10;check_bot(stt_event_card($event)['kind']==='thread','New-topic card still visible even with short thanks body');
$canView=false;check_bot(stt_event_card($event)===false,'Private-forum permission checks remain enforced');$canView=true;
$mybb->settings['stt_bot_ignore']='82';check_bot(stt_bot_record('post',12)===false&&stt_event_card($event)===false,'Excluded forums still excluded');$mybb->settings['stt_bot_ignore']='';
$posts[12]['visible']=0;check_bot(stt_bot_record('post',12)===false,'Unapproved posts still excluded');
echo json_encode(['status'=>'passed','checks'=>$checks,'php'=>PHP_VERSION]).PHP_EOL;
