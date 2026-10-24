# Changelog

## 2.3.0 — 2026-10-24

First release published in this repository.

- Polish and English translations for the chat, accessibility labels, errors, bot activity cards, quick setup and native ACP settings.
- Language selected from MyBB's current visitor/ACP language; English fallback for other languages.
- Localised date/time formatting without changing stored message timestamps.
- Standalone language files; frontend translations safely embedded as JSON with an English JavaScript fallback.
- English and Polish installation guides and beginner walkthroughs.
- Automated checks for language-key and placeholder parity, language selection, permissions, CSRF, installer idempotency and preserving settings/history.

Includes the existing 2.2 quick setup, automatic light/dark palettes, public/private chat, editing, reactions, mentions, forum smilies and permission-aware bot. No chat schema or history migration is introduced by 2.3.0.
