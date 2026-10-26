<?php
/** Activity bot control panel. Saves allowlisted settings; preview has no side effects. */
if(!defined('IN_MYBB')) exit;

function stt_bot_admin(){
 global $mybb,$db,$page;
 if(!defined('IN_ADMINCP')||$mybb->get_input('action')!=='sttshoutbox_bot')return;
 check_admin_permissions(['module'=>'config','action'=>'settings']);
 $url='index.php?module=config-plugins&action=sttshoutbox_bot';$errors=[];$result=null;
 $v=stt_bot_admin_values();$sample='';$kind='post';$attached=false;
 $issues=stt_setup_requirements();
 if($mybb->request_method==='post'){
  if(!verify_post_check($mybb->get_input('my_post_key'),true))$errors[]=stt_t('text_your_session_has_expired_refresh_the_page_and_try_ag_4be802');
  elseif($issues)$errors=$issues;
  elseif(!sttshoutbox_is_installed())$errors[]=stt_t('text_install_and_activate_sttshoutbox_from_the_plugin_lis_fb3304');
  else{
   try{
    $values=stt_bot_admin_validate($mybb->input);$action=$mybb->get_input('bot_action');
    if($action==='save'){
     sttshoutbox_upgrade_24();
     foreach($values as$key=>$value)$db->update_query('settings',['value'=>$db->escape_string($value)],"name='stt_".$key."'");
     rebuild_settings();log_admin_action('STTShoutbox','Activity bot');
     flash_message(stt_t('bot_saved'),'success');admin_redirect($url);return;
    }
    if($action!=='test')throw new InvalidArgumentException(stt_t('bot_error_fields'));
    $submittedSample=$mybb->input['bot_sample']??'';$submittedKind=$mybb->input['bot_kind']??'';
    if(!is_string($submittedSample)||mb_strlen($submittedSample,'UTF-8')>20000||!is_string($submittedKind)||!in_array($submittedKind,['post','thread'],true))throw new InvalidArgumentException(stt_t('bot_error_sample'));
    $sample=$submittedSample;$kind=$submittedKind;
    $attached=($mybb->input['bot_attachment']??'')==='1';
    $settings=$mybb->settings;foreach($values as$key=>$value)$settings['stt_'.$key]=$value;
    $result=stt_bot_filter_decide($sample,$kind,$settings,$attached);
    if(empty($settings['stt_enabled'])||$values['bot_enabled']!=='1'){$result['blocked']=true;$result['reasons'][]='disabled';}
    elseif($values['bot_'.$kind]!=='1'){$result['blocked']=true;$result['reasons'][]='type_disabled';}
    $v=$values;
   }catch(InvalidArgumentException $e){$errors[]=$e->getMessage();}
  }
  // Escape all submitted values on render, including rejected input.
  foreach($v as$key=>&$value)if(isset($mybb->input[$key])&&is_string($mybb->input[$key]))$value=$mybb->input[$key];unset($value);
  if(is_string($mybb->input['bot_sample']??null))$sample=mb_substr($mybb->input['bot_sample'],0,20000,'UTF-8');
  if(in_array($mybb->input['bot_kind']??null,['post','thread'],true))$kind=$mybb->input['bot_kind'];
 }
 $page->add_breadcrumb_item(stt_t('bot_panel_title'));$page->output_header(stt_t('bot_panel_title'));
 if($errors)$page->output_inline_error($errors);
 if($issues&&$issues!==$errors)$page->output_inline_error($issues);
 $h=fn($s)=>htmlspecialchars_uni((string)$s);
 echo '<style>.stt-bot-panel{max-width:1080px;line-height:1.6}.stt-bot-panel *{box-sizing:border-box}.stt-bot-panel h1{font-size:25px;margin:12px 0}.stt-bot-panel .stt-bot-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}.stt-bot-panel fieldset{min-width:0;margin:0 0 18px;padding:20px;border:1px solid #bccbd8;border-radius:10px}.stt-bot-panel legend{font-size:16px;font-weight:bold;padding:0 8px}.stt-bot-panel label{display:block;font-weight:bold;margin:12px 0 4px}.stt-bot-panel select,.stt-bot-panel input[type=text],.stt-bot-panel textarea{width:100%;padding:9px;border:1px solid #889eaf;border-radius:5px;background:#fff;color:#172f41}.stt-bot-panel textarea{resize:vertical;min-height:115px}.stt-bot-panel input[type=number]{padding:9px;width:100px}.stt-bot-panel small{display:block;color:#455c70;line-height:1.55;margin:5px 0 12px}.stt-bot-panel .stt-bot-note{padding:14px 18px;background:#eff7ff;color:#173c58;border-left:4px solid #078dcc;border-radius:4px}.stt-bot-panel .stt-bot-result{padding:16px;border:2px solid #078dcc;border-radius:8px;background:#f5fbff;color:#173c58;margin:16px 0}.stt-bot-panel button{padding:11px 19px;border:0;border-radius:6px;background:#006eae;color:white;cursor:pointer;font-weight:bold;margin:8px 8px 0 0}.stt-bot-panel button[value=test]{background:#334f65}.stt-bot-panel details{margin:15px 0}.stt-bot-panel summary{cursor:pointer;font-weight:bold}@media(max-width:720px){.stt-bot-panel .stt-bot-grid{grid-template-columns:1fr}.stt-bot-panel fieldset{padding:14px}}</style>';
 echo '<div class="stt-bot-panel"><h1>'.$h(stt_t('bot_panel_title')).'</h1><p>'.$h(stt_t('bot_panel_intro')).'</p><p class="stt-bot-note">'.$h(stt_t('bot_panel_note')).'</p>';
 if($result!==null){
  echo '<div class="stt-bot-result" role="status"><strong>'.$h(stt_t($result['blocked']?'bot_result_blocked':'bot_result_allowed')).'</strong><p>'.$h(stt_t('bot_result_words',['count'=>$result['words']])).'</p>';
  foreach($result['reasons'] as$reason)echo '<p>'.$h(stt_t('bot_reason_'.$reason,['phrase'=>$result['matched'],'count'=>$v['bot_filter_min_words']])).'</p>';
  if($result['bypassed'])echo '<p>'.$h(stt_t('bot_result_bypass')).'</p>';
  echo '<small>'.$h(stt_t('bot_result_unsaved')).'</small></div>';
 }
 echo '<form method="post" action="'.$h($url).'"><input type="hidden" name="my_post_key" value="'.$h($mybb->post_code).'">';
 $select=function($key,$choices)use($h,$v){echo '<label for="stt-'.$key.'">'.$h(stt_t('setting_stt_'.$key)).'</label><select id="stt-'.$key.'" name="'.$key.'">';foreach($choices as$id=>$label)echo '<option value="'.$h($id).'"'.((string)$id===$v[$key]?' selected':'').'>'.$h($label).'</option>';echo '</select><small>'.$h(stt_t('setting_stt_'.$key.'_desc')).'</small>';};
 $yesno=['1'=>stt_t('bot_on'),'0'=>stt_t('bot_off')];
 echo '<div class="stt-bot-grid"><fieldset><legend>'.$h(stt_t('bot_section_events')).'</legend>';
 foreach(['bot_enabled','bot_thread','bot_post','bot_register'] as$key)$select($key,$yesno);
 echo '<label for="stt-bot_ignore">'.$h(stt_t('setting_stt_bot_ignore')).'</label><input id="stt-bot_ignore" name="bot_ignore" type="text" value="'.$h($v['bot_ignore']).'"><small>'.$h(stt_t('setting_stt_bot_ignore_desc')).'</small></fieldset>';
 echo '<fieldset><legend>'.$h(stt_t('bot_section_thanks')).'</legend>';
 $select('bot_filter_thanks',$yesno);$select('bot_filter_mode',['only'=>stt_t('bot_mode_only'),'contains'=>stt_t('bot_mode_contains')]);
 echo '<label for="stt-bot_filter_phrases">'.$h(stt_t('setting_stt_bot_filter_phrases')).'</label><textarea id="stt-bot_filter_phrases" name="bot_filter_phrases" maxlength="4000" rows="6">'.$h($v['bot_filter_phrases']).'</textarea><small>'.$h(stt_t('setting_stt_bot_filter_phrases_desc')).'</small></fieldset></div>';
 echo '<div class="stt-bot-grid"><fieldset><legend>'.$h(stt_t('bot_section_short')).'</legend>';
 $select('bot_filter_short',$yesno);
 echo '<label for="stt-bot_filter_min_words">'.$h(stt_t('setting_stt_bot_filter_min_words')).'</label><input type="number" id="stt-bot_filter_min_words" name="bot_filter_min_words" min="1" max="100" required value="'.$h($v['bot_filter_min_words']).'"><small>'.$h(stt_t('setting_stt_bot_filter_min_words_desc')).'</small>';
 $select('bot_filter_links',$yesno);echo '</fieldset><fieldset><legend>'.$h(stt_t('bot_section_scope')).'</legend>';
 $select('bot_filter_scope',['replies'=>stt_t('bot_scope_replies'),'all'=>stt_t('bot_scope_all')]);$select('bot_filter_existing',$yesno);
 echo '<details><summary>'.$h(stt_t('bot_count_title')).'</summary><p>'.$h(stt_t('bot_count_help')).'</p></details></fieldset></div>';
 echo '<fieldset><legend>'.$h(stt_t('bot_section_test')).'</legend><p>'.$h(stt_t('bot_test_help')).'</p><label for="stt-bot-kind">'.$h(stt_t('bot_test_kind')).'</label><select id="stt-bot-kind" name="bot_kind">';
 foreach(['post'=>stt_t('bot_test_reply'),'thread'=>stt_t('bot_test_thread')] as$id=>$label)echo '<option value="'.$id.'"'.($kind===$id?' selected':'').'>'.$h($label).'</option>';
 echo '</select><label for="stt-bot-sample">'.$h(stt_t('bot_test_sample')).'</label><textarea id="stt-bot-sample" name="bot_sample" maxlength="20000" rows="4">'.$h($sample).'</textarea><label><input type="checkbox" name="bot_attachment" value="1"'.($attached?' checked':'').'> '.$h(stt_t('bot_test_attachment')).'</label><button type="submit" name="bot_action" value="test">'.$h(stt_t('bot_test_button')).'</button></fieldset>';
 echo '<button type="submit" name="bot_action" value="save">'.$h(stt_t('bot_save_button')).'</button> <a href="index.php?module=config-plugins&amp;action=sttshoutbox_setup">'.$h(stt_t('bot_back_setup')).'</a></form></div>';
 $page->output_footer();exit;
}
