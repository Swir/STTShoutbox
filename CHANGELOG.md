# Changelog

## 2.5.2 — 2026-10-27

- Show a smaller red × inside the message action bar on hover or keyboard focus, instead of permanently beside every message.
- Put the own-message Edit button first and make it easier to spot.
- Keep message controls usable on touch devices without requiring hover.

## 2.5.1 — 2026-10-27

- Administrators, super moderators and assigned MyBB forum moderators get a red × button for public messages, with a translated accessible label and confirmation.
- Withdrawn public messages disappear completely from the chat view, including old placeholders. Records remain in the existing storage; this is not database erasure.
- Open chats reconcile removals during polling, including loaded older history, without restoring removed rows from a delayed response.
- Ordinary members may still withdraw only their own public messages. Private conversations retain their existing participant-only withdrawal rules.
- New PHP permission/history tests and JavaScript reconciliation tests; versioned script and stylesheet URLs refresh browser caches.

## 2.5.0 — 2026-10-26

- A shorter, organised plugin entry with clearly grouped configuration links.
- Original bundled `jscripts/sttshoutbox/notify.wav` notification sound, served locally with the plugin. No external audio service is required.
- Sound controls include a preview and volume adjustment. Sound is initially off; each account's browser remembers its preference and volume.
- New incoming-message notifications stay silent when chat history loads, the visitor sends their own message or the activity bot posts a notice. Bursts are limited to one chime every three seconds. Browser audio permissions still require user interaction.
- Updated Polish and English installation guides, including the new sound folder. Existing bot configuration and conversation history are preserved.

## 2.4.0 — 2026-10-26

- Dedicated **Activity bot** page in Admin CP → Configuration → Plugins → STTShoutbox, also linked from Quick setup. Notification types, forum exclusions and filters can be configured together in Polish or English.
- Independent on/off switches for thank-you phrases and short replies. Defaults: both enabled, complete-word/phrase matching anywhere, minimum 10 words, replies only.
- Editable thank-you phrases; optional acknowledgement-only matching. Matching ignores letter case and folds Polish accents without matching fragments inside longer words.
- Optional filtering of first posts in new topics; optional links/attachments exemption from the short-post filter; optional filtering of previously recorded activity cards. The link/attachment exemption is off and existing-card filtering is on by default.
- A tester evaluates the current unsaved form settings, notification switches and sample content, reporting the word count and reasons without saving or publishing anything.
- Quoted content is excluded from classification. URLs, code and media are not counted as prose; text inside hide tags is counted. Permissions and excluded forums continue to control real activity cards.
- Filters affect activity notifications only. Forum posts, thank-you/unlock behaviour and conversation history are preserved. Recorded cards can reappear when their filter is disabled; notifications skipped at creation are not backfilled.
- Included the private-message unread indicator previously deployed on the STT forum: dot and badge, collapsed-header alert, unread-first conversation list and read acknowledgements only when messages are visible. Polish and English support is retained.
- Added installation and bot configuration guides in both languages. Upgrade adds settings without resetting existing configuration or migrating conversation tables.

## 2.3.0 — 2026-10-24

First release published in this repository.

- Polish and English translations for the chat, accessibility labels, errors, bot activity cards, quick setup and native ACP settings.
- Language selected from MyBB's current visitor/ACP language; English fallback for other languages.
- Localised date/time formatting without changing stored message timestamps.
- Standalone language files; frontend translations safely embedded as JSON with an English JavaScript fallback.
- English and Polish installation guides and beginner walkthroughs.
- Automated checks for language-key and placeholder parity, language selection, permissions, CSRF, installer idempotency and preserving settings/history.

Includes the existing 2.2 quick setup, automatic light/dark palettes, public/private chat, editing, reactions, mentions, forum smilies and permission-aware bot. No chat schema or history migration is introduced by 2.3.0.
