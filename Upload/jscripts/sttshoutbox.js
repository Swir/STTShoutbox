(() => {
 'use strict';
 const root=document.getElementById('stt-chat'), config=document.getElementById('stt-config');
 if(!root||!config)return;
 const cfg=JSON.parse(config.textContent);
 const fallback={"ui_could_not_connect_to_the_chat_1bbda1":"Could not connect to the chat.","ui_refresh_the_page_to_renew_your_session_or_complete_t_380151":"Refresh the page to renew your session or complete the site verification.","ui_administrator_preview_swirtv_team_dafc0a":"Administrator preview · SWIRTV TEAM","ui_a_place_for_our_community_694c04":"A place for our community","ui_connecting_c1994a":"Connecting…","ui_turn_sound_off_4dbeb0":"Turn sound off","ui_turn_sound_on_d92467":"Turn sound on","ui_new_message_sound_da7181":"New message sound","ui_expand_chat_ff4d47":"Expand chat","ui_show_chat_b7f5fe":"Show chat","ui_hide_chat_191b13":"Hide chat","ui_show_or_hide_chat_16e8c8":"Show or hide chat","ui_conversations_fdfb91":"Conversations","ui_general_f0badc":"# General","ui_private_200e9c":"✉ Private","ui_close_conversations_439140":"Close conversations","ui_private_conversations_124102":"Private conversations","ui_find_a_member_182eb0":"Find a member…","ui_find_a_member_to_chat_with_97761a":"Find a member to chat with","ui_messages_are_visible_to_the_participants_forum_priva_453321":"Messages are visible to the participants. Forum privacy settings apply.","ui_older_messages_011978":"↑ Older messages","ui_chat_messages_604574":"Chat messages","ui_new_messages_ca81fd":" New messages","ui_write_a_message_53505d":"Write a message…","ui_log_in_to_join_the_conversation_37e187":"Log in to join the conversation","ui_message_8a4754":"Message","ui_smilies_17d0af":"☺ Smilies","ui_enter_send_shift_enter_new_line_f3750c":"Enter — send · Shift+Enter — new line","ui_send_6161a2":"Send ↗","ui_smiley_picker_f090a4":"Smiley picker","ui_search_smilies_66a1fd":"Search smilies…","ui_search_smilies_3cd3f4":"Search smilies","ui_recent_b17a5b":"Recent","ui_close_smilies_8205a6":"Close smilies","ui_no_matching_smilies_c35171":"No matching smilies.","ui_choose_a_member_to_start_a_conversation_ff0177":"Choose a member to start a conversation.","ui_this_is_the_start_of_your_conversation_say_hello_04d82e":"This is the start of your conversation. Say hello 👋","ui_it_is_quiet_here_start_a_conversation_144286":"It is quiet here. Start a conversation 👋","ui_mention_3a8063":"Mention ","ui_edited_7a5e88":"edited","ui_this_message_has_been_edited_237267":"This message has been edited","ui_private_a23aa9":"private","ui_you_were_mentioned_94e0c2":"you were mentioned","ui_this_message_has_been_withdrawn_4f8b52":"This message has been withdrawn.","ui_reaction_e25678":"Reaction ","ui_edit_6ebdce":"Edit","ui_reply_540892":"Reply","ui_replying_to_8151e0":"Replying to: ","ui_private_ffd7ec":"Private","ui_withdraw_af7e9b":"Withdraw","ui_withdraw_this_message_from_the_conversation_41ace3":"Withdraw this message from the conversation?","ui_save_changes_e4249f":"Save changes","ui_you_are_editing_your_message_9f026c":"You are editing your message","ui_cancel_49285f":"Cancel","ui_private_message_to_f7d2db":"Private message to ","ui_refreshing_3fb27e":"Refreshing…","ui_live_769d27":"● Live","ui_disconnected_8ac350":"Disconnected","ui_the_message_and_quote_exceed_the_character_limit_f7f3ef":"The message and quote exceed the character limit.","ui_if_the_connection_was_interrupted_check_the_history__ff675a":" If the connection was interrupted, check the history before sending again."};
 const t=(key)=>typeof cfg.i18n?.[key]==='string'?cfg.i18n[key]:(fallback[key]||key);
 // Read the surrounding theme once. No polling or page scrolling is involved.
 let appearance=['auto','light','dark','classic'].includes(cfg.appearance)?cfg.appearance:'classic';
 if(appearance==='auto'){
  let surface=root.parentElement, rgb=null;
  while(surface){const match=getComputedStyle(surface).backgroundColor.match(/^rgba?\(([^)]+)\)/);if(match){const parts=match[1].split(',').map(Number);if(parts.length===3||parts[3]>=0.9){rgb=parts;break;}}surface=surface.parentElement;}
  appearance=rgb?(rgb[0]*.2126+rgb[1]*.7152+rgb[2]*.0722<140?'dark':'light'):'light';
 }
 root.dataset.appearance=appearance;
 root.classList.toggle('stt-adaptive',appearance!=='classic');
 root.dataset.density=cfg.density==='comfortable'?'comfortable':'compact';
 root.style.setProperty('--stt-height',Math.max(180,Math.min(600,Number(cfg.height)||240))+'px');
 if(appearance!=='classic'&&/^#[0-9a-f]{6}$/i.test(cfg.accent||'')){
  root.style.setProperty('--stt-accent',cfg.accent);
  const rgb=cfg.accent.slice(1).match(/../g).map(x=>parseInt(x,16)/255).map(x=>x<=.04045?x/12.92:((x+.055)/1.055)**2.4);
  root.style.setProperty('--stt-on-accent',rgb[0]*.2126+rgb[1]*.7152+rgb[2]*.0722>.179?'#10202f':'#ffffff');
 }
 const rx={like:'👍',heart:'❤️',laugh:'😄'};
 let peer=0, peerName='', epoch=0, messages=new Map(), conversations=[], emotes=[], busy=false, loading=false, timer, searchTimer, selectedReply=null, selectedEdit=null, beforeEdit=null, requestCount=0;
 const newestFirst=cfg.order!=='oldest';root.classList.toggle('stt-newest',newestFirst);
 let olderCursor=0;let sound=false, lastTop=0, draft=new Map(), olderAvailable=true;
 const node=(tag,cls,text)=>{const el=document.createElement(tag);if(cls)el.className=cls;if(text!==undefined)el.textContent=text;return el;};
 const coloredName=m=>{let n=node('span','stt-nick');if(m.nameHtml)n.innerHTML=m.nameHtml;else n.textContent=m.name;return n;};
 const latest=()=>{log.scrollTop=newestFirst?0:log.scrollHeight;};
 const nearLatest=()=>newestFirst?log.scrollTop<70:log.scrollHeight-log.scrollTop-log.clientHeight<70;
 const btn=(text,cls,fn,title)=>{let b=node('button',cls,text);b.type='button';if(title)b.title=title;b.addEventListener('click',fn);return b;};
 const safeURL=value=>{try{let u=new URL(value,location.href);return /^https?:$/.test(u.protocol)?u.href:'';}catch{return '';}};
 async function api(op,data={},write=false){
  const ctrl=new AbortController(),timeout=setTimeout(()=>ctrl.abort(),15000);let p=new URLSearchParams({op,...data});if(write)p.set('my_post_key',cfg.csrf);
  try{const r=await fetch(cfg.endpoint+(write?'':'&'+p),{method:write?'POST':'GET',credentials:'same-origin',cache:'no-store',signal:ctrl.signal,headers:write?{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'}:{},body:write?p:undefined});const j=await r.json();if(!r.ok||!j.ok)throw Error(j.error||t("ui_could_not_connect_to_the_chat_1bbda1"));return j.data;}
  catch(e){if(e instanceof SyntaxError)throw Error(t("ui_refresh_the_page_to_renew_your_session_or_complete_t_380151"));throw e;}finally{clearTimeout(timeout);}
 }
 root.replaceChildren();
 const head=node('header','stt-head'), brand=node('div','stt-brand');brand.append(node('span','stt-mark','STT'),node('div','', cfg.title||'Shoutbox'));
 brand.lastChild.append(node('small','',cfg.preview?t("ui_administrator_preview_swirtv_team_dafc0a"):t("ui_a_place_for_our_community_694c04")));
 const state=node('span','stt-status',t("ui_connecting_c1994a"));state.setAttribute('role','status');
 const soundBtn=btn('♪','stt-icon',()=>{sound=!sound;soundBtn.classList.toggle('is-on',sound);soundBtn.setAttribute('aria-pressed',String(sound));soundBtn.title=sound?t("ui_turn_sound_off_4dbeb0"):t("ui_turn_sound_on_d92467");},t("ui_turn_sound_on_d92467"));soundBtn.setAttribute('aria-label',t("ui_new_message_sound_da7181"));soundBtn.setAttribute('aria-pressed','false');
 const full=btn('⛶','stt-icon',()=>{root.classList.toggle('stt-expanded');full.setAttribute('aria-pressed',String(root.classList.contains('stt-expanded')));},t("ui_expand_chat_ff4d47"));full.setAttribute('aria-label',t("ui_expand_chat_ff4d47"));
 const collapse=btn('−','stt-icon',()=>{const collapsed=root.classList.toggle('stt-collapsed');collapse.textContent=collapsed?'+':'−';collapse.setAttribute('aria-expanded',String(!collapsed));collapse.setAttribute('aria-label',collapsed?t("ui_show_chat_b7f5fe"):t("ui_hide_chat_191b13"));},t("ui_show_or_hide_chat_16e8c8"));collapse.setAttribute('aria-label',t("ui_hide_chat_191b13"));collapse.setAttribute('aria-expanded','true');
 head.append(brand,state,soundBtn,full,collapse);
 const tabs=node('nav','stt-tabs');tabs.setAttribute('aria-label',t("ui_conversations_fdfb91"));
 const publicBtn=btn(t("ui_general_f0badc"),'stt-tab is-active',()=>switchPeer(0,''));
 const privateBtn=btn(t("ui_private_200e9c"),'stt-tab',()=>{people.hidden=!people.hidden;userSearch.focus();});privateBtn.hidden=!cfg.private;
 const room=node('span','stt-room');tabs.append(publicBtn,privateBtn,room);
 const layout=node('div','stt-layout'), main=node('div','stt-main'), people=node('aside','stt-people');people.hidden=true;
 const closePeople=btn('×','stt-icon',()=>people.hidden=true,t("ui_close_conversations_439140"));
 const peopleHead=node('div','stt-people-head',t("ui_private_conversations_124102"));peopleHead.append(closePeople);
 const userSearch=node('input','stt-search');userSearch.placeholder=t("ui_find_a_member_182eb0");userSearch.setAttribute('aria-label',t("ui_find_a_member_to_chat_with_97761a"));
 const userResults=node('div','stt-user-results'), convList=node('div','stt-conversations');people.append(peopleHead,userSearch,userResults,node('small','stt-note',t("ui_messages_are_visible_to_the_participants_forum_priva_453321")),convList);
 const older=btn(t("ui_older_messages_011978"),'stt-older',loadOlder);
 const log=node('div','stt-log');log.setAttribute('role','log');log.setAttribute('aria-label',t("ui_chat_messages_604574"));log.setAttribute('aria-live','off');
 const news=btn((newestFirst?'↑':'↓')+t("ui_new_messages_ca81fd"),'stt-new',()=>{latest();news.hidden=true;});news.hidden=true;
 const error=node('div','stt-error');error.setAttribute('role','alert');error.hidden=true;
 const reply=node('div','stt-reply');reply.hidden=true;
 const editBar=node('div','stt-editbar');editBar.hidden=true;editBar.setAttribute('role','status');
 const form=node('form','stt-compose');
 const input=node('textarea','stt-input');input.rows=1;input.maxLength=cfg.limit;input.placeholder=cfg.write?t("ui_write_a_message_53505d"):t("ui_log_in_to_join_the_conversation_37e187");input.disabled=!cfg.write;input.setAttribute('aria-label',t("ui_message_8a4754"));
 const tools=node('div','stt-compose-tools'), emojiBtn=btn(t("ui_smilies_17d0af"),'stt-emoji-toggle',()=>{picker.hidden=!picker.hidden;emojiBtn.setAttribute('aria-expanded',String(!picker.hidden));if(!picker.hidden)emojiSearch.focus();});emojiBtn.disabled=!cfg.write;
 const hint=node('small','stt-hint',t("ui_enter_send_shift_enter_new_line_f3750c")), counter=node('span','stt-counter','0 / '+cfg.limit),send=node('button','stt-send',t("ui_send_6161a2"));send.type='submit';send.disabled=!cfg.write;
 tools.append(emojiBtn,hint,counter,send);form.append(input,tools);
 const picker=node('div','stt-picker');picker.hidden=true;picker.setAttribute('aria-label',t("ui_smiley_picker_f090a4"));
 const emojiSearch=node('input','stt-search');emojiSearch.placeholder=t("ui_search_smilies_66a1fd");emojiSearch.setAttribute('aria-label',t("ui_search_smilies_3cd3f4"));
 const emojiTabs=node('div','stt-emoji-tabs'), grid=node('div','stt-emoji-grid');let emojiMode='forum';
 for(const [key,label]of [['forum','Forum'],['unicode','Emoji'],['recent',t("ui_recent_b17a5b")]])emojiTabs.append(btn(label,'stt-chip',()=>{emojiMode=key;renderEmotes();}));
 const pickerHead=node('div','stt-picker-head');pickerHead.append(emojiSearch,btn('×','stt-icon',()=>picker.hidden=true,t("ui_close_smilies_8205a6")));picker.append(pickerHead,emojiTabs,grid);
 const mentions=node('div','stt-mentions');mentions.hidden=true;
 tabs.append(older);main.append(log,news,reply,editBar,error,picker,mentions,form);layout.append(main,people);root.append(head,tabs,layout);
 let recent=[];try{recent=JSON.parse(localStorage.getItem('stt_emotes')||'[]');if(!Array.isArray(recent))recent=[];}catch{}
 function tell(s){error.textContent=s;error.hidden=!s;}
 function count(){counter.textContent=input.value.length+' / '+cfg.limit;}
 function insert(s){input.focus();input.setRangeText(s,input.selectionStart,input.selectionEnd,'end');count();}
 function remember(s){recent=[s,...recent.filter(x=>x!==s)].slice(0,24);try{localStorage.setItem('stt_emotes',JSON.stringify(recent));}catch{}}
 const unicode='😀 😃 😄 😁 😆 😅 😂 🙂 😉 😊 😎 🤔 🤨 😮 😢 😡 ❤️ 💙 👍 👎 👏 🙌 🔥 🎉 💪 ☕ 🍺 🚀 👋 ✅'.split(' ');
 function renderEmotes(){grid.replaceChildren();const q=emojiSearch.value.toLowerCase();let list=emojiMode==='forum'?emotes:emojiMode==='unicode'?unicode.map(x=>({code:x,name:x})):recent.map(code=>emotes.find(x=>x.code===code)||{code,name:code});for(const e of list.filter(x=>(x.name+' '+x.code).toLowerCase().includes(q)).slice(0,350)){let b=btn('', 'stt-emote',()=>{insert(' '+e.code+' ');remember(e.code);},e.name+' '+e.code);b.setAttribute('aria-label',e.name);if(e.src){let img=node('img');img.src=safeURL(e.src);img.alt=e.name;img.loading='lazy';b.append(img);}else b.textContent=e.code;grid.append(b);}if(!grid.children.length)grid.append(node('p','stt-note',t("ui_no_matching_smilies_c35171")));}
 emojiSearch.addEventListener('input',renderEmotes);
 function avatar(m){let a=node('span','stt-avatar',m.name.slice(0,1).toUpperCase());const url=safeURL(m.avatar);if(m.avatar&&url){let img=node('img');img.src=url;img.alt='';img.loading='lazy';img.addEventListener('error',()=>img.remove());a.append(img);}return a;}
 function renderConversations(){convList.replaceChildren();let total=0;for(const c of conversations){total+=c.unread;let b=btn('', 'stt-person',()=>switchPeer(c.uid,c.name));b.append(avatar(c),coloredName(c));if(c.unread)b.append(node('b','stt-unread',String(c.unread)));convList.append(b);}if(!conversations.length)convList.append(node('p','stt-note',t("ui_choose_a_member_to_start_a_conversation_ff0177")));privateBtn.textContent=t("ui_private_200e9c")+(total?' · '+total:'');}
 function mentionMine(text){let n=cfg.name.toLowerCase(),t=text.toLowerCase();return n&&new RegExp('@(?:"'+escapeRegex(n)+'"|'+escapeRegex(n)+'(?=$|[\\s,.:!?]))','iu').test(t);}
 function escapeRegex(s){return s.replace(/[.*+?^${}()|[\]\\]/g,'\\$&');}
 function render(){
  const near=nearLatest(),oldHeight=log.scrollHeight,oldScroll=log.scrollTop;const anchor=[...log.querySelectorAll('[data-id]')].find(x=>x.getBoundingClientRect().bottom>log.getBoundingClientRect().top);const anchorId=anchor?.dataset.id,anchorOffset=anchor?anchor.getBoundingClientRect().top-log.getBoundingClientRect().top:0;
  const list=[...messages.values()].sort((a,b)=>newestFirst?b.id-a.id:a.id-b.id);log.replaceChildren();
  if(!list.length)log.append(node('div','stt-empty',peer?t("ui_this_is_the_start_of_your_conversation_say_hello_04d82e"):t("ui_it_is_quiet_here_start_a_conversation_144286")));
  let date='';for(const m of list){let dateLabel=new Date((m.date+(cfg.timeOffset||0)*3600)*1000).toLocaleDateString(cfg.locale||'en-GB',{day:'numeric',month:'long',timeZone:'UTC'});if(dateLabel!==date){log.append(node('div','stt-day',dateLabel));date=dateLabel;}
   const row=node('article','stt-message'+(m.uid===cfg.uid?' stt-own':'')+(mentionMine(m.text)?' stt-mentioned':''));row.dataset.id=m.id;
   row.append(avatar(m));let content=node('div','stt-message-content'),meta=node('div','stt-meta');
   const author=btn(m.name,'stt-author',()=>{if(!cfg.write)return;insert('@"'+m.name+'" ');});author.replaceChildren(coloredName(m));author.title=t("ui_mention_3a8063")+m.name;
   let time=node('time','',new Date((m.date+(cfg.timeOffset||0)*3600)*1000).toLocaleTimeString(cfg.locale||'en-GB',{hour:'2-digit',minute:'2-digit',timeZone:'UTC'}));time.dateTime=new Date(m.date*1000).toISOString();meta.append(author,time);if(m.edited){const stamp=node('span','stt-edited',t("ui_edited_7a5e88"));stamp.title=t("ui_this_message_has_been_edited_237267");meta.append(stamp);}
   if(peer)meta.append(node('span','stt-private-label',t("ui_private_a23aa9")));if(mentionMine(m.text))meta.append(node('span','stt-mention-label',t("ui_you_were_mentioned_94e0c2")));
   const body=node('div','stt-body');if(m.event&&!m.deleted){row.classList.add('stt-bot-event');const tag=node('span','stt-event-tag',m.event.label);const link=node('a','stt-event-title',m.event.subject);link.href=safeURL(m.event.url);const details=node('span','stt-event-meta');const person=node('span','stt-event-author');person.innerHTML=m.event.authorHtml;details.append(person);if(m.event.forum)details.append(document.createTextNode(' · '+m.event.forum));body.append(tag,link,details);}else if(m.deleted){body.textContent=t("ui_this_message_has_been_withdrawn_4f8b52");body.classList.add('stt-deleted');}else{body.innerHTML=m.html;for(const a of body.querySelectorAll('a')){if(!safeURL(a.href)){a.replaceWith(document.createTextNode(a.textContent));}else{a.rel='noopener noreferrer';a.target='_blank';}}for(const a of body.querySelectorAll('a.mentionme_mention')){a.classList.add('stt-mention');a.removeAttribute('style');}}
   let actions=node('div','stt-message-actions');if(!m.deleted&&cfg.write){if(!peer)for(const [kind,label]of Object.entries(rx)){let r=m.reactions.find(x=>x.kind===kind);let b=btn(label+(r?' '+r.count:''),'stt-reaction'+(r?.mine?' is-on':''),async()=>{try{await api('react',{id:m.id,kind},true);await load();}catch(e){tell(e.message);}},t("ui_reaction_e25678")+label);actions.append(b);}
    if(m.canEdit)actions.append(btn(t("ui_edit_6ebdce"),'stt-small',()=>startEdit(m)));
    actions.append(btn(t("ui_reply_540892"),'stt-small',()=>{cancelEdit();selectedReply=m;reply.replaceChildren(node('span','', t("ui_replying_to_8151e0")+m.name+' · '+m.text.slice(0,90)),btn('×','stt-icon',clearReply));reply.hidden=false;input.focus();}));
    if(cfg.private&&m.uid!==cfg.uid)actions.append(btn(t("ui_private_ffd7ec"),'stt-small',()=>switchPeer(m.uid,m.name)));
    if(m.canDelete)actions.append(btn(t("ui_withdraw_af7e9b"),'stt-small',async()=>{if(!confirm(t("ui_withdraw_this_message_from_the_conversation_41ace3")))return;try{await api('delete',{id:m.id,peer},true);await load();}catch(e){tell(e.message);}}));
   }
   content.append(meta,body,actions);row.append(content);log.append(row);
  }
  if(near)latest();else{const a=anchorId?log.querySelector('[data-id="'+anchorId+'"]'):null;log.scrollTop=oldScroll;if(a)log.scrollTop+=a.getBoundingClientRect().top-log.getBoundingClientRect().top-anchorOffset;if(log.scrollHeight>oldHeight)news.hidden=false;}
 }
 function startEdit(m){if(busy)return;if(!selectedEdit)beforeEdit={text:input.value,reply:selectedReply};selectedEdit={id:m.id,text:m.text,peer};clearReply();input.value=m.text;count();send.textContent=t("ui_save_changes_e4249f");editBar.replaceChildren(node('span','',t("ui_you_are_editing_your_message_9f026c")),btn(t("ui_cancel_49285f"),'stt-small',cancelEdit));editBar.hidden=false;input.focus();}
 function cancelEdit(){if(!selectedEdit)return;selectedEdit=null;input.value=beforeEdit?.text||'';const savedReply=beforeEdit?.reply;beforeEdit=null;clearReply();if(savedReply){selectedReply=savedReply;reply.replaceChildren(node('span','',t("ui_replying_to_8151e0")+savedReply.name+' · '+savedReply.text.slice(0,90)),btn('×','stt-icon',clearReply));reply.hidden=false;}editBar.hidden=true;editBar.replaceChildren();send.textContent=t("ui_send_6161a2");count();}
 function clearReply(){selectedReply=null;reply.hidden=true;reply.replaceChildren();}
 async function switchPeer(uid,name){cancelEdit();draft.set(peer,input.value);peer=uid;peerName=name;epoch++;messages.clear();lastTop=0;olderCursor=0;olderAvailable=true;older.hidden=false;input.value=draft.get(peer)||'';count();clearReply();tell('');publicBtn.classList.toggle('is-active',!peer);privateBtn.classList.toggle('is-active',!!peer);room.textContent=peer?'🔒 '+name:'';input.placeholder=peer?t("ui_private_message_to_f7d2db")+name+'…':t("ui_write_a_message_53505d");people.hidden=true;news.hidden=true;render();await load();}
 function beep(){if(!sound)return;try{let a=new (window.AudioContext||window.webkitAudioContext)(),o=a.createOscillator(),g=a.createGain();o.connect(g);g.connect(a.destination);o.frequency.value=660;g.gain.value=.045;o.start();g.gain.exponentialRampToValueAtTime(.001,a.currentTime+.15);o.stop(a.currentTime+.15);o.onended=()=>a.close();}catch{}}
 async function load(){
  const turn=epoch,roomPeer=peer,request=++requestCount;state.textContent=t("ui_refreshing_3fb27e");
  try{let data=await api('load',{peer:roomPeer});if(turn!==epoch||request!==requestCount)return;const old=lastTop;lastTop=data.messages.at(-1)?.id||0;for(const m of data.messages)messages.set(m.id,m);if(!olderCursor)olderCursor=data.cursor||0;conversations=data.conversations;renderConversations();render();state.textContent=t("ui_live_769d27");state.classList.add('is-live');if(old&&lastTop>old&&data.messages.some(m=>m.id>old&&m.uid!==cfg.uid))beep();if(peer&&document.visibilityState==='visible'&&lastTop)api('read',{peer,last:lastTop},true).catch(()=>{});}
  catch(e){if(turn===epoch){state.textContent=t("ui_disconnected_8ac350");state.classList.remove('is-live');tell(e.message);}}
 }
 async function loadOlder(){if(!olderCursor||!olderAvailable)return;let before=olderCursor,turn=epoch;older.disabled=true;try{let d=await api('load',{peer,before});if(turn!==epoch)return;let h=log.scrollHeight,top=log.scrollTop;for(const m of d.messages)messages.set(m.id,m);render();log.scrollTop=newestFirst?top:top+log.scrollHeight-h;olderCursor=d.cursor||0;olderAvailable=olderCursor>0;older.hidden=!olderAvailable;}catch(e){tell(e.message);}finally{older.disabled=false;}}
 userSearch.addEventListener('input',()=>{clearTimeout(searchTimer);const q=userSearch.value;searchTimer=setTimeout(async()=>{try{const users=await api('users',{q});if(userSearch.value!==q)return;userResults.replaceChildren();for(const u of users.filter(u=>u.uid!==cfg.uid)){let b=btn('', 'stt-person',()=>{userResults.replaceChildren();userSearch.value='';switchPeer(u.uid,u.name);});b.append(avatar(u),coloredName(u));userResults.append(b);}}catch(e){tell(e.message);}},300);});
 let mentionTimer;input.addEventListener('input',()=>{count();clearTimeout(mentionTimer);const m=input.value.slice(0,input.selectionStart).match(/@([^\s@"]{2,30})$/u);if(!m){mentions.hidden=true;return;}mentionTimer=setTimeout(async()=>{try{const users=await api('users',{q:m[1]});mentions.replaceChildren();for(const u of users){mentions.append(btn('@'+u.name,'stt-chip',()=>{const end=input.selectionStart;input.setRangeText('@"'+u.name+'" ',end-m[0].length,end,'end');input.focus();count();mentions.hidden=true;}));}mentions.hidden=!users.length;}catch{}},300);});
 input.addEventListener('keydown',e=>{if(e.key==='Enter'&&!e.shiftKey&&!e.isComposing){e.preventDefault();form.requestSubmit();}if(e.key==='Escape'){picker.hidden=true;mentions.hidden=true;cancelEdit();}});
 form.addEventListener('submit',async e=>{e.preventDefault();if(busy||!cfg.write||!input.value.trim())return;let text=input.value.trim();if(selectedEdit){const editing={...selectedEdit};busy=true;send.disabled=true;try{await api('edit',{peer:editing.peer,id:editing.id,text,expected:editing.text},true);if(selectedEdit?.id===editing.id&&peer===editing.peer){cancelEdit();tell('');await load();}}catch(e){tell(e.message);}finally{busy=false;send.disabled=!cfg.write;}return;}if(selectedReply)text='[quote='+selectedReply.name.replace(/[\[\]\r\n]/g,'')+']'+selectedReply.text.slice(0,250).replace(/\[\/?quote[^\]]*\]/gi,'')+'[/quote]\n'+text;if(text.length>cfg.limit){tell(t("ui_the_message_and_quote_exceed_the_character_limit_f7f3ef"));return;}busy=true;send.disabled=true;const dest=peer;try{await api('send',{peer:dest,text},true);if(peer===dest){input.value='';count();clearReply();draft.delete(peer);tell('');await load();latest();}}catch(e){tell(e.message+t("ui_if_the_connection_was_interrupted_check_the_history__ff675a"));}finally{busy=false;send.disabled=!cfg.write;}});
 log.addEventListener('scroll',()=>{if(nearLatest())news.hidden=true;});
 document.addEventListener('visibilitychange',()=>{if(document.visibilityState==='visible')load();});
 api('boot').then(d=>{emotes=d.emotes;renderEmotes();}).catch(e=>tell(e.message));load();
 timer=setInterval(()=>{if(document.visibilityState==='visible'&&!busy)load();},cfg.interval*1000);
})();
