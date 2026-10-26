<?php
/** STTShoutbox: beginner setup and portable appearance. SWIRTV TEAM, MIT. */
if(!defined('IN_MYBB')) exit;

function stt_setup_fields($fresh=false){
 return [
  'title'=>[stt_t('text_chat_name_41c5e0'),'text','Shoutbox',stt_t('text_a_short_name_displayed_in_the_header_maximum_60_char_4022b1')],
  'appearance'=>[stt_t('text_theme_matching_2add62'),stt_t('text_select_auto_match_the_forum_automatically_dark_dark__c13a3d'),$fresh?'auto':'classic',stt_t('text_automatically_detects_a_light_or_dark_forum_backgrou_559d05')],
  'accent'=>[stt_t('text_accent_colour_851546'),'text','#078dcc',stt_t('text_colour_in_rrggbb_format_e_g_078dcc_quick_setup_inclu_4329ca')],
  'height'=>[stt_t('text_message_list_height_94b986'),'numeric','240',stt_t('text_180_to_600_pixels_240_compact_360_comfortable_480_la_0c205e')],
  'density'=>[stt_t('text_message_spacing_53830f'),stt_t('text_select_compact_compact_comfortable_comfortable_d94927'),'compact',stt_t('text_comfortable_spacing_improves_readability_without_cha_4ed0dd')]
 ];
}

function sttshoutbox_upgrade_22($fresh=false){
 global $db;
 $gid=(int)$db->fetch_field($db->simple_select('settinggroups','gid',"name='sttshoutbox'"),'gid');
 if(!$gid)return;
 $position=2;
 foreach(stt_setup_fields($fresh) as $key=>$v){
  $name='stt_'.$key;
  if(!$db->fetch_field($db->simple_select('settings','sid',"name='$name'"),'sid')){
   $db->insert_query('settings',['name'=>$name,'title'=>$db->escape_string($v[0]),'optionscode'=>$db->escape_string($v[1]),'value'=>$db->escape_string($v[2]),'description'=>$db->escape_string($v[3]),'disporder'=>$position,'gid'=>$gid,'isdefault'=>0]);
  }
  $position++;
 }
 rebuild_settings();
}

function stt_setup_appearance($settings){
 $mode=$settings['stt_appearance']??'classic';
 $density=$settings['stt_density']??'compact';
 $accent=$settings['stt_accent']??'#078dcc';
 $title=trim((string)($settings['stt_title']??'Shoutbox'));
 return [
  'appearance'=>in_array($mode,['auto','dark','light','classic'],true)?$mode:'classic',
  'accent'=>preg_match('/^#[0-9a-f]{6}$/i',(string)$accent)?$accent:'#078dcc',
  'height'=>max(180,min(600,(int)($settings['stt_height']??240))),
  'density'=>$density==='comfortable'?'comfortable':'compact',
  'title'=>$title!==''?mb_substr($title,0,60):'Shoutbox'
 ];
}

// Only the current request's template cache changes. Newly imported themes work too.
function stt_setup_place($template){
 if(strpos($template,'{$sttshoutbox}')!==false)return $template;
 $pos=strpos($template,'{$header}');
 if($pos!==false)return substr_replace($template,'{$sttshoutbox}',$pos+strlen('{$header}'),0);
 return $template;
}

function stt_setup_validate($input){
 $out=[];
 foreach(['title','appearance','accent','height','density','order','private','bot_enabled'] as $key){
  if(!isset($input[$key])||!is_string($input[$key]))throw new InvalidArgumentException(stt_t('text_complete_all_form_fields_7d88ed'));
 }
 $out['title']=trim($input['title']);
 if($out['title']===''||mb_strlen($out['title'])>60)throw new InvalidArgumentException(stt_t('text_the_chat_name_must_contain_between_1_and_60_characte_6f4149'));
 foreach(['appearance'=>['auto','dark','light','classic'],'density'=>['compact','comfortable'],'order'=>['newest','oldest'],'private'=>['0','1'],'bot_enabled'=>['0','1']] as $key=>$allowed){
  if(!in_array($input[$key],$allowed,true))throw new InvalidArgumentException(stt_t('text_choose_one_of_the_available_options_af78a9'));
  $out[$key]=$input[$key];
 }
 if(!preg_match('/^#[0-9a-f]{6}$/i',$input['accent']))throw new InvalidArgumentException(stt_t('text_choose_a_colour_from_the_picker_rrggbb_format_b7653e'));
 $out['accent']=strtolower($input['accent']);
 if(!ctype_digit($input['height'])||(int)$input['height']<180||(int)$input['height']>600)throw new InvalidArgumentException(stt_t('text_height_must_be_between_180_and_600_pixels_ba7e79'));
 $out['height']=(string)(int)$input['height'];
 return $out;
}

function stt_setup_requirements(){
 global $db,$mybb;
 $issues=[];
 if(PHP_VERSION_ID<80100)$issues[]=stt_t('text_the_server_requires_php_8_1_or_later_72f5d6');
 if(!extension_loaded('mbstring'))$issues[]=stt_t('text_ask_your_hosting_provider_to_enable_the_php_mbstring_0feb85');
 if(!in_array($db->type,['mysqli','mysql','mysql_pdo'],true))$issues[]=stt_t('text_mysql_or_mariadb_is_required_4b7244');
 if((int)$mybb->version_code<1800||(int)$mybb->version_code>=1900)$issues[]=stt_t('text_this_package_is_intended_for_mybb_1_8_1c3be5');
 foreach(['inc/plugins/sttshoutbox/i18n.php','inc/languages/english/sttshoutbox.lang.php','inc/languages/polish/sttshoutbox.lang.php','inc/plugins/sttshoutbox/filters.php','inc/plugins/sttshoutbox/bot-admin.php','inc/plugins/sttshoutbox/bot.php','inc/plugins/sttshoutbox/service.php','inc/plugins/sttshoutbox/setup.php','jscripts/sttshoutbox.js','jscripts/sttshoutbox.css','jscripts/sttshoutbox/notify.wav'] as $file){
  if(!is_file(MYBB_ROOT.$file))$issues[]=stt_t('missing_file',['file'=>$file]);
 }
 return $issues;
}

function stt_setup_admin(){
 global $mybb,$db,$page;
 if(!defined('IN_ADMINCP')||$mybb->get_input('action')!=='sttshoutbox_setup')return;
 check_admin_permissions(['module'=>'config','action'=>'settings']);
 $url='index.php?module=config-plugins&action=sttshoutbox_setup';
 $issues=stt_setup_requirements();$errors=[];
 if($mybb->request_method==='post'){
  if(!verify_post_check($mybb->get_input('my_post_key'),true))$errors[]=stt_t('text_your_session_has_expired_refresh_the_page_and_try_ag_4be802');
  elseif($issues)$errors=$issues;
  elseif(!sttshoutbox_is_installed())$errors[]=stt_t('text_install_and_activate_sttshoutbox_from_the_plugin_lis_fb3304');
  else{
   try{
    $values=stt_setup_validate($mybb->input);
    sttshoutbox_upgrade_22();
    foreach($values as $key=>$value)$db->update_query('settings',['value'=>$db->escape_string($value)],"name='stt_".$key."'");
    rebuild_settings();log_admin_action('STTShoutbox',stt_t('text_quick_setup_c151fa'));
    flash_message(stt_t('text_done_settings_saved_open_the_forum_homepage_to_see_y_aebd01'),'success');admin_redirect($url);
   }catch(InvalidArgumentException $e){$errors[]=$e->getMessage();}
  }
 }
 $page->add_breadcrumb_item(stt_t('text_sttshoutbox_quick_setup_ce3ca8'));$page->output_header(stt_t('text_sttshoutbox_quick_setup_ce3ca8'));
 if($errors)$page->output_inline_error($errors);
 if($issues&&$issues!==$errors)$page->output_inline_error($issues);
 $v=stt_setup_appearance($mybb->settings);
 $v+=['order'=>$mybb->settings['stt_order']??'newest','private'=>$mybb->settings['stt_private']??'1','bot_enabled'=>$mybb->settings['stt_bot_enabled']??'1'];
 if($errors)foreach($v as $k=>&$value){if(isset($mybb->input[$k])&&is_string($mybb->input[$k]))$value=$mybb->input[$k];}unset($value);
 $h=fn($x)=>htmlspecialchars_uni((string)$x);
 echo stt_t('text_your_chat_in_a_few_simple_stepschoose_the_appearance_986546');
 echo '<style>.stt-setup{max-width:960px}.stt-setup fieldset{margin:18px 0;padding:20px;border:1px solid #b6c5d3;border-radius:8px}.stt-setup legend{font-weight:bold;padding:0 8px}.stt-setup label{display:block;margin:12px 0 5px;font-weight:bold}.stt-setup input[type=text],.stt-setup select{max-width:100%;width:340px;padding:8px}.stt-setup small{display:block;margin:5px 0 14px;line-height:1.6}.stt-setup input[type=number]{width:100px;padding:8px}.stt-setup .stt-help{padding:16px;border-left:4px solid #078dcc;background:#eff7ff;color:#18354c}.stt-setup button{padding:11px 22px;border:0;border-radius:6px;background:#006dae;color:white;cursor:pointer;font-weight:bold}</style>';
 echo '<p class="stt-help">'.($issues?stt_t('text_check_the_messages_above_97da44'):stt_t('text_all_required_files_and_server_requirements_are_prese_bad68d')).stt_t('text_smilies_and_username_colours_come_from_your_forum_1c7446');
 echo '<form method="post" action="'.$h($url).'"><input type="hidden" name="my_post_key" value="'.$h($mybb->post_code).'">';
 echo stt_t('text_1_name_and_appearancechat_name_input_id_stt_title_na_668053').$h($v['title']).'">';
 $select=function($key,$label,$choices,$help='')use($v,$h){echo '<label for="stt-'.$key.'">'.$label.'</label><select id="stt-'.$key.'" name="'.$key.'">';foreach($choices as $value=>$text)echo '<option value="'.$h($value).'"'.((string)$value===(string)$v[$key]?' selected':'').'>'.$h($text).'</option>';echo '</select><small>'.$help.'</small>';};
 $select('appearance',stt_t('text_appearance_4cdc1e'),['auto'=>stt_t('text_match_the_forum_automatically_recommended_3606bc'),'dark'=>stt_t('text_dark_50e9ae'),'light'=>stt_t('text_light_d5b6c1'),'classic'=>stt_t('text_classic_stt_version_2_1_appearance_a7ee32')],stt_t('text_automatic_mode_chooses_a_light_or_dark_palette_based_450ecd'));
 echo stt_t('text_accent_colour_input_id_stt_accent_name_accent_type_c_ef8f3f').$h($v['accent']).stt_t('text_applies_to_the_automatic_light_and_dark_palettes_cla_882b0e');
 echo stt_t('text_2_size_and_messagesmessage_list_height_px_input_id_s_985dc5').$h($v['height']).stt_t('text_240_compact_360_comfortable_480_large_width_adapts_t_56b842');
 $select('density',stt_t('text_spacing_a75ca0'),['compact'=>stt_t('text_compact_aa0e02'),'comfortable'=>stt_t('text_comfortable_9864ce')]);
 $select('order',stt_t('text_where_do_new_messages_appear_1fedab'),['newest'=>stt_t('text_at_the_top_d16ede'),'oldest'=>stt_t('text_at_the_bottom_448412')]);echo '</fieldset>';
 echo stt_t('text_3_extras_eb0fbb');
 $select('private',stt_t('text_private_conversations_124102'),['1'=>stt_t('text_enabled_92323a'),'0'=>stt_t('text_disabled_b193b1')],stt_t('text_chat_conversations_are_separate_from_mybb_private_me_6fad67'));
 $select('bot_enabled',stt_t('text_bot_notifications_for_new_threads_and_replies_389b2e'),['1'=>stt_t('text_enabled_e743d8'),'0'=>stt_t('text_disabled_f2a59d')],stt_t('text_members_only_see_notifications_from_forums_they_can__cff8b7'));echo '</fieldset>';
 echo '<fieldset><legend>'.$h(stt_t('setup_sound_title')).'</legend><p>'.$h(stt_t('setup_sound_note')).'</p><audio controls preload="none" aria-label="'.$h(stt_t('ui_sound_preview')).'" style="max-width:100%" src="'.$h(rtrim($mybb->settings['bburl'],'/').'/jscripts/sttshoutbox/notify.wav?v=2.5.0').'"></audio></fieldset>';
 echo stt_t('text_save_settings_a_target_blank_rel_noopener_href_179920').$h(rtrim($mybb->settings['bburl'],'/').'/index.php').stt_t('text_open_forum_48d576');
 $gid=(int)$db->fetch_field($db->simple_select('settinggroups','gid',"name='sttshoutbox'"),'gid');
 echo '<p><a href="index.php?module=config-plugins&amp;action=sttshoutbox_bot"><strong>'.htmlspecialchars_uni(stt_t('bot_panel_link')).'</strong></a></p>';
 echo '<p><a href="index.php?module=config-settings&amp;action=change&amp;gid='.$gid.stt_t('text_advanced_settings_access_bot_limitschat_not_showing__9ec0cc');
 $page->output_footer();exit;
}
