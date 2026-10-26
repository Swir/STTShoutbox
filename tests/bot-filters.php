<?php
// Isolated bot regression suite. No hosting database or external connection.
if(PHP_SAPI!=='cli')exit;
define('IN_MYBB',1);define('TABLE_PREFIX','test_');
require_once dirname(__DIR__).'/Upload/inc/plugins/sttshoutbox/bot.php';
require_once dirname(__DIR__).'/Upload/inc/plugins/sttshoutbox/filters.php';
$checks=[];
function check_filter($condition,$label){global $checks;if(!$condition)throw new RuntimeException($label);$checks[]=$label;}
function config_filter($overrides=[]){$out=[];foreach(array_replace(stt_bot_filter_defaults(),$overrides) as$key=>$value)$out['stt_'.$key]=$value;return $out;}
function decide_filter($text,$overrides=[],$kind='post',$attached=false){return stt_bot_filter_decide($text,$kind,config_filter($overrides),$attached);}
function rejected_filter($input,$label){try{stt_bot_admin_validate($input);}catch(InvalidArgumentException $e){check_filter(true,$label);return;}check_filter(false,$label);}
function stt_t($key){return $key;}
function get_post($id){global $posts;return $posts[$id]??false;}
function get_thread($id){global $threads;return $threads[$id]??false;}
function get_forum($id){global $forums;return $forums[$id]??false;}
function forum_permissions($id){global $permissions;return $permissions[$id]??['canview'=>0,'canviewthreads'=>0,'canonlyviewownthreads'=>0];}
function get_user($id){return $id>0?['uid'=>$id,'username'=>'TestUser','usergroup'=>2,'displaygroup'=>0]:false;}
function format_name($text,$group,$display){return $text;}
function htmlspecialchars_uni($text){return htmlspecialchars($text,ENT_QUOTES,'UTF-8');}
function get_thread_link($id){return 'showthread.php?tid='.$id;}
function get_post_link($pid,$tid){return 'showthread.php?tid='.$tid.'&pid='.$pid;}
function get_profile_link($id){return 'member.php?action=profile&uid='.$id;}
function my_inet_pton($text){return inet_pton($text);}
function rebuild_settings(){global $db,$mybb;foreach($db->settings as$row)$mybb->settings[$row['name']]=stripslashes($row['value']);$db->rebuilds++;}
class FilterDbDouble{
 public $writes=[],$settings=[],$attachments=[],$queries=[],$claimed=[],$affected=0,$rebuilds=0;
 function write_query($query){
  if(!preg_match("/VALUES \('([^']+)',(\d+),/",$query,$m))throw new RuntimeException('Unexpected write query');
  $key=$m[1].':'.$m[2];$this->affected=isset($this->claimed[$key])?0:1;
  if($this->affected){$this->claimed[$key]=true;$this->writes[]=['event',$query];}
 }
 function affected_rows(){return $this->affected;}
 function insert_query($table,$row){
  if($table==='settings'){$id=count($this->settings)+1;$row['sid']=$id;$this->settings[$row['name']]=$row;return $id;}
  if($table!=='stt_messages')throw new RuntimeException('Unexpected insert table');
  $this->writes[]=[$table,$row];return 99;
 }
 function update_query($table,$row,$where){if($table!=='stt_events')throw new RuntimeException('Unexpected update table');$this->writes[]=[$table,$row,$where];}
 function simple_select($table,$fields='*',$where='',$options=[]){
  $this->queries[]=[$table,$where];
  if($table==='settinggroups')return ['gid'=>5];
  if($table==='settings'&&preg_match("/name='([^']+)'/",$where,$m))return $this->settings[$m[1]]??[];
  if($table==='attachments'&&preg_match('/pid=(\d+) AND visible=1/',$where,$m))return isset($this->attachments[(int)$m[1]])?['aid'=>$this->attachments[(int)$m[1]]]:[];
  throw new RuntimeException('Unexpected read table');
 }
 function fetch_field($row,$field){return $row[$field]??false;}
 function escape_string($value){return addslashes($value);}
 function escape_binary($value){return $value;}
 function reset_events(){$this->writes=[];$this->claimed=[];$this->queries=[];}
}
$db=new FilterDbDouble;
$base=['stt_enabled'=>'1','stt_bot_enabled'=>'1','stt_bot_post'=>'1','stt_bot_thread'=>'1','stt_bot_register'=>'1','stt_bot_ignore'=>'','bburl'=>'https://example.test'];
$mybb=(object)['settings'=>$base+config_filter(),'user'=>['uid'=>1],'binary_fields'=>[]];
$nine='jeden dwa trzy cztery pięć sześć siedem osiem dziewięć';
$ten=$nine.' dziesięć';$eleven=$ten.' jedenaście';
try{
 foreach([[$nine,9,true],[$ten,10,false],[$eleven,11,false]] as[$text,$words,$blocked]){
  $result=decide_filter($text);check_filter($result['words']===$words&&$result['blocked']===$blocked,'Minimum ten boundary: '.$words);
 }
 $result=decide_filter("  jeden,\ndwa\ttrzy\r\ncztery&nbsp;pięć sześć! siedem? osiem; dziewięć dziesięć. ");
 check_filter($result['words']===10&&!$result['blocked'],'Whitespace, punctuation and HTML entities do not inflate the count');
 check_filter(decide_filter('zażółć gęślą jaźń')['words']===3,'Polish words are counted as words');
 check_filter(decide_filter("don't state-of-art l’été")['words']===3,'Apostrophes and internal hyphens stay within words');
 foreach(['','👍 🙂 😂','!!!'] as$text)check_filter(decide_filter($text)['words']===0&&decide_filter($text)['blocked'],'No prose is below the word minimum');
 check_filter(!decide_filter('a',['bot_filter_min_words'=>'1'])['blocked'],'Minimum one accepts one word');
 check_filter(decide_filter($eleven,['bot_filter_min_words'=>'12'])['blocked'],'Custom minimum is used');
 check_filter(!decide_filter('dzięki',['bot_filter_thanks'=>'0','bot_filter_short'=>'0'])['blocked'],'Both switches off allow a thank-you reply');
 check_filter(decide_filter('dzięki',['bot_filter_thanks'=>'0'])['reasons']===['short'],'Thanks switch off leaves length filtering active');
 check_filter(decide_filter('dzięki',['bot_filter_short'=>'0'])['reasons']===['thanks'],'Length switch off leaves keyword filtering active');
 check_filter(decide_filter('dzięki')['reasons']===['thanks','short'],'Both matching reasons are reported');
 foreach(['dzięki','DZIĘKI!!!','dzieki','Dziękuję','DZIEKUJEMY','&nbsp;ThAnKs&nbsp;','thank you','THX','[b][color=#ff0000]DZIĘKI[/color][/b]'] as$text){
  check_filter(decide_filter($text,['bot_filter_short'=>'0'])['blocked'],'Keyword normalization: '.$text);
 }
 foreach(['dziękiewicz','xthanksx','thank yourself','ty masz rację','thankful'] as$text){
  check_filter(!decide_filter($text,['bot_filter_short'=>'0'])['blocked'],'Whole words and phrases only: '.$text);
 }
 check_filter(decide_filter($ten.' dzięki za pomoc')['blocked'],'Contains mode suppresses a longer reply with thanks');
 check_filter(!decide_filter($ten.' dzięki za pomoc',['bot_filter_mode'=>'only'])['blocked'],'Only mode preserves a longer substantive reply');
 foreach(['Wielkie dzięki za listę','Serdecznie dziękuję za udostępnienie','Thanks for sharing','Thank you very much'] as$text){
  check_filter(decide_filter($text,['bot_filter_mode'=>'only','bot_filter_short'=>'0'])['blocked'],'Only mode recognizes acknowledgement: '.$text);
 }
 check_filter(!decide_filter('Dzięki, ale lista nie działa.',['bot_filter_mode'=>'only','bot_filter_short'=>'0'])['blocked'],'Only mode preserves a problem report');
 check_filter(decide_filter('brawo',['bot_filter_phrases'=>'brawo','bot_filter_short'=>'0','bot_filter_mode'=>'only'])['blocked'],'Custom exact acknowledgement');
 check_filter(!decide_filter('brawo ale coś nie działa',['bot_filter_phrases'=>'brawo','bot_filter_short'=>'0','bot_filter_mode'=>'only'])['blocked'],'Custom acknowledgement does not match arbitrary prose in only mode');
 check_filter(decide_filter('Oto super robota naprawdę',['bot_filter_phrases'=>"super robota\ninny tekst",'bot_filter_short'=>'0'])['matched']==='super robota','Configured multiword phrase');
 check_filter(!decide_filter('dzięki',['bot_filter_phrases'=>'brawo','bot_filter_short'=>'0'])['blocked'],'Removing a phrase actually stops matching it');
 check_filter(!decide_filter('dzięki',['bot_filter_phrases'=>'','bot_filter_short'=>'0'])['blocked'],'Empty runtime phrase list matches nothing');
 check_filter(!decide_filter('[quote]'.$ten.'[/quote] '.$ten)['blocked'],'Own prose counts after quote');
 $quoted=decide_filter('[quote=User]'.$ten.'[quote]'.$ten.'[/quote]'.$ten.'[/quote] dzięki');
 check_filter($quoted['words']===1&&$quoted['reasons']===['thanks','short'],'Nested quoted words do not count');
 check_filter(decide_filter('[quote=User]dzięki[/quote] '.$ten)['reasons']===[],'Quoted thanks does not trigger keywords');
 check_filter(decide_filter('[quote=User]'.$ten.' dzięki')['words']===0,'Unfinished quote is not treated as new prose');
 check_filter(decide_filter('[b]jeden[/b] [i]dwa[/i] [size=10]trzy[/size]')['words']===3,'MyCode tags and attributes do not count');
 check_filter(decide_filter('[hide]'.$ten.'[/hide]')['words']===10,'Hidden original prose is counted without tag names');
 check_filter(decide_filter('[hideuntilthanks]'.$ten.'[/hideuntilthanks]')['reasons']===[],'Thanks embedded in a MyCode name does not trigger keywords');
 check_filter(decide_filter('[code]'.$ten.' dzięki[/code] nowe')['words']===1,'Code payload is not prose');
 check_filter(!decide_filter('[code]thanks[/code] '.$ten)['blocked'],'Thanks inside code does not trigger keywords');
 foreach(['https://example.test/thanks/a/b/c/d/e/f/g/h','[url=https://example.test/thanks]link[/url]','www.example.test/thanks'] as$text){
  check_filter(!decide_filter($text,['bot_filter_short'=>'0'])['blocked'],'URL target does not trigger keywords: '.$text);
 }
 check_filter(decide_filter('https://example.test/a/b/c')['words']===0,'URL path does not count as words');
 check_filter(decide_filter('[url=https://example.test]dzięki[/url]',['bot_filter_short'=>'0'])['blocked'],'Visible link label matches contains mode');
 check_filter(!decide_filter('[url=https://example.test]dzięki[/url]',['bot_filter_short'=>'0','bot_filter_mode'=>'only'])['blocked'],'A linked payload is not acknowledgement-only');
 $link=decide_filter('Nowa lista https://example.test/list',['bot_filter_links'=>'1']);
 check_filter(!$link['blocked']&&$link['bypassed']&&$link['words']===2,'Link exception bypasses the short-post filter');
 check_filter(decide_filter('Nowa lista https://example.test/list')['blocked'],'Link exception is off by default');
 check_filter(decide_filter('[attachment=123]',['bot_filter_links'=>'1'])['bypassed'],'Embedded attachment triggers length exception');
 check_filter(decide_filter('Nowa lista',['bot_filter_links'=>'1'],'post',true)['bypassed'],'Real unembedded attachment triggers length exception');
 $linkedThanks=decide_filter('dzięki https://example.test/list',['bot_filter_links'=>'1']);
 check_filter($linkedThanks['blocked']&&$linkedThanks['reasons']===['thanks']&&$linkedThanks['bypassed'],'Link exception does not disable the keyword filter');
 check_filter(!decide_filter('dzięki',['bot_filter_short'=>'0','bot_filter_mode'=>'only'],'post',true)['blocked'],'Acknowledgement-only does not hide actual attached content');
 check_filter(decide_filter('[quote]https://example.test[/quote] ok',['bot_filter_links'=>'1'])['blocked'],'Quoted link does not grant an exception');
 foreach(['[code]https://example.test/list[/code] ok','[php]https://example.test/list[/php] ok','[img]https://example.test/list.jpg[/img] ok','[video=youtube]https://example.test/video[/video] ok'] as$text){
  $result=decide_filter($text,['bot_filter_links'=>'1']);check_filter($result['blocked']&&!$result['bypassed'],'Code and media URL payload does not grant a short-post exception');
 }
 check_filter(!decide_filter('dzięki',[],'thread')['blocked'],'Replies-only scope preserves new topics');
 check_filter(decide_filter('dzięki',['bot_filter_scope'=>'all'],'thread')['blocked'],'All-posts scope includes new topics');
 check_filter(!decide_filter('dzięki',[],'register')['blocked'],'Registration notices never use post filters');
 check_filter(!decide_filter('dzięki',[],'unknown')['blocked'],'Unknown event types are not processed as posts');
 $before=$mybb->settings;decide_filter('dzięki');check_filter($mybb->settings===$before,'Pure decisions do not mutate settings');

 // Configuration validation, allowlisting and hostile inputs.
 $valid=stt_bot_admin_values();$valid['bot_filter_phrases']=" dzięki \r\n\nthanks\ndzięki";$valid['bot_ignore']='82, 94,82,0';$valid['bot_filter_min_words']='010';$valid['unrelated']='evil';
 $clean=stt_bot_admin_validate($valid);
 check_filter($clean['bot_filter_phrases']==="dzięki\nthanks"&&$clean['bot_ignore']==='82,94'&&$clean['bot_filter_min_words']==='10','Configuration normalization and deduplication');
 check_filter(stt_bot_admin_validate(array_replace($valid,['bot_ignore'=>'36,37,29,30,']))['bot_ignore']==='36,37,29,30','Existing comma-terminated forum exclusion list is accepted and normalized');
 check_filter(!isset($clean['unrelated']),'Configuration saves only allowlisted keys');
 foreach(['0','-1','101','1.5','10 words',''] as$value)rejected_filter(array_replace($valid,['bot_filter_min_words'=>$value]),'Reject invalid minimum: '.$value);
 foreach(['bot_enabled'=>[],'bot_post'=>'yes','bot_filter_thanks'=>'2','bot_filter_scope'=>'other','bot_filter_mode'=>'regex','bot_filter_phrases'=>'','bot_filter_links'=>'false','bot_filter_existing'=>'2','bot_ignore'=>'1 OR 1=1'] as$key=>$value)rejected_filter(array_replace($valid,[$key=>$value]),'Reject invalid field: '.$key);
 $missing=$valid;unset($missing['bot_filter_short']);rejected_filter($missing,'Reject missing field');
 rejected_filter(array_replace($valid,['bot_filter_phrases'=>str_repeat('a',81)]),'Reject overlong individual phrase');
 rejected_filter(array_replace($valid,['bot_filter_phrases'=>implode("\n",array_map(fn($n)=>'word'.$n,range(1,101)))]),'Reject more than one hundred phrases');
 rejected_filter(array_replace($valid,['bot_filter_phrases'=>str_repeat('a',4001)]),'Reject oversized phrase configuration');
 rejected_filter(array_replace($valid,['bot_filter_phrases'=>'!!!']),'Reject a phrase without letters or numbers');
 check_filter(stt_bot_admin_validate(array_replace($valid,['bot_filter_thanks'=>'0','bot_filter_phrases'=>'']))['bot_filter_phrases']==='','Disabled keyword filtering permits an empty list');
 $runtime=stt_bot_filter_config(['stt_bot_filter_min_words'=>'999','stt_bot_filter_scope'=>'bad','stt_bot_filter_mode'=>[]]);
 check_filter($runtime['bot_filter_min_words']==='100'&&$runtime['bot_filter_scope']==='replies'&&$runtime['bot_filter_mode']==='contains','Runtime configuration bounds and sanitizes advanced settings');

 // Upgrade installs only missing options and is idempotent.
 $db->settings['stt_bot_ignore']=['name'=>'stt_bot_ignore','value'=>'49,94','sid'=>1];
 $db->settings['stt_bot_filter_min_words']=['name'=>'stt_bot_filter_min_words','value'=>'17','sid'=>2];
 sttshoutbox_upgrade_24();$settingCount=count($db->settings);$rebuildCount=$db->rebuilds;
 check_filter($settingCount===count(stt_bot_filter_defaults())+1,'Upgrade installs missing bot filter settings');
 check_filter($mybb->settings['stt_bot_ignore']==='49,94'&&$mybb->settings['stt_bot_filter_min_words']==='17','Upgrade preserves existing exclusions and threshold');
 sttshoutbox_upgrade_24();check_filter(count($db->settings)===$settingCount&&$db->rebuilds===$rebuildCount,'Repeated upgrade does not duplicate settings or rewrite cache');

 // Real hook and card functions with isolated storage/permissions.
 $mybb->settings=$base+config_filter();
 $forums=[1=>['fid'=>1,'active'=>1,'parentlist'=>'1','password'=>'','name'=>'Parent'],82=>['fid'=>82,'active'=>1,'parentlist'=>'1,82','password'=>'','name'=>'VIP']];
 $permissions=[1=>['canview'=>1,'canviewthreads'=>1,'canonlyviewownthreads'=>0],82=>['canview'=>1,'canviewthreads'=>1,'canonlyviewownthreads'=>0]];
 $threads=[7=>['tid'=>7,'firstpost'=>10,'fid'=>82,'uid'=>1,'visible'=>1,'closed'=>'','subject'=>'Test topic']];
 $posts=[10=>['pid'=>10,'tid'=>7,'uid'=>1,'visible'=>1,'message'=>'dzięki'],11=>['pid'=>11,'tid'=>7,'uid'=>2,'visible'=>1,'message'=>'dzięki'],12=>['pid'=>12,'tid'=>7,'uid'=>2,'visible'=>1,'message'=>$ten]];
 $event=['kind'=>'post','tid'=>7,'pid'=>11,'actor'=>2];$originalPosts=$posts;
 $db->reset_events();stt_bot_post((object)['pid'=>11]);check_filter(!$db->writes,'Real post hook suppresses thank-you event without writes');
 check_filter(stt_bot_record('post',12)===99&&count($db->writes)===3,'Ten-word reply creates event and chat row');
 $written=count($db->writes);check_filter(stt_bot_record('post',12)===false&&count($db->writes)===$written,'Repeated hooks do not duplicate the event');
 check_filter(stt_event_card($event)===false,'Recorded thanks card is filtered by current settings');
 $mybb->settings['stt_bot_filter_existing']='0';check_filter(stt_event_card($event)['kind']==='post','Existing-card switch preserves previously stored card');
 $db->reset_events();check_filter(stt_bot_record('post',11)===false&&!$db->writes,'Existing-card switch does not bypass filtering of new events');
 $permissions[1]['canview']=0;check_filter(stt_event_card($event)===false,'Existing-card switch never bypasses parent forum permissions');$permissions[1]['canview']=1;
 $permissions[82]['canonlyviewownthreads']=1;$mybb->user['uid']=2;check_filter(stt_event_card($event)===false,'Own-threads-only permission remains enforced');$mybb->user['uid']=1;$permissions[82]['canonlyviewownthreads']=0;
 $forums[1]['password']='secret';check_filter(stt_event_card($event)===false,'Password-protected parent remains hidden');$forums[1]['password']='';
 $forums[82]['active']=0;check_filter(stt_event_card($event)===false&&stt_bot_record('post',12)===false,'Inactive forums remain excluded');$forums[82]['active']=1;
 $mybb->settings['stt_bot_ignore']='1';check_filter(stt_event_card($event)===false&&stt_bot_record('post',12)===false,'Excluded parent forum excludes its descendants');$mybb->settings['stt_bot_ignore']='';
 $posts[11]['visible']=0;check_filter(stt_event_card($event)===false&&stt_bot_record('post',11)===false,'Unapproved reply cannot become a notification');$posts[11]['visible']=1;
 $threads[7]['visible']=0;check_filter(stt_event_card($event)===false&&stt_bot_record('post',12)===false,'Unapproved topic cannot become a notification');$threads[7]['visible']=1;
 $threads[7]['closed']='moved|99';check_filter(stt_event_card($event)===false&&stt_bot_record('post',12)===false,'Moved shadow topic remains excluded');$threads[7]['closed']='';
 $db->reset_events();check_filter(stt_bot_record('thread',7)===99,'New topic allowed under replies-only scope');
 $db->reset_events();check_filter(stt_bot_record('post',10)===false&&!$db->writes,'First post is not duplicated as a reply event');
 $mybb->settings['stt_bot_filter_scope']='all';check_filter(stt_bot_record('thread',7)===false&&!$db->writes,'New topic filtered when administrator chooses all posts');
 $mybb->settings['stt_bot_filter_thanks']='0';$mybb->settings['stt_bot_filter_short']='0';
 check_filter(stt_bot_record('post',11)===99&&stt_event_card($event)['kind']==='post','Both filters off restore normal event behavior');
 $db->reset_events();$mybb->settings['stt_bot_post']='0';check_filter(stt_bot_record('post',12)===false&&!$db->writes,'Bot reply switch remains respected');$mybb->settings['stt_bot_post']='1';
 $mybb->settings['stt_bot_enabled']='0';check_filter(stt_bot_record('post',12)===false&&!$db->writes,'Global bot switch remains respected');$mybb->settings['stt_bot_enabled']='1';
 $mybb->settings['stt_enabled']='0';check_filter(stt_bot_record('post',12)===false&&!$db->writes,'Global shoutbox switch remains respected');$mybb->settings['stt_enabled']='1';
 $mybb->settings=$base+config_filter(['bot_filter_links'=>'1']);$posts[11]['message']='Nowa lista';$db->attachments[11]=33;$db->reset_events();
 check_filter(!stt_bot_filter_post($posts[11],'post')&&count($db->queries)===1,'Unembedded approved attachment is checked when exception is enabled');
 $mybb->settings['stt_bot_filter_links']='0';$db->queries=[];check_filter(stt_bot_filter_post($posts[11],'post')&&!$db->queries,'Attachment lookup avoided when exception is disabled');
 $posts[11]['message']=$originalPosts[11]['message'];check_filter($posts===$originalPosts,'Filtering never modifies forum post contents');
 echo json_encode(['status'=>'passed','checks'=>count($checks),'php'=>PHP_VERSION,'tests'=>$checks],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT).PHP_EOL;
}catch(Throwable $error){fwrite(STDERR,json_encode(['status'=>'failed','error'=>$error->getMessage(),'line'=>$error->getLine(),'passed'=>count($checks)],JSON_UNESCAPED_UNICODE).PHP_EOL);exit(1);}
