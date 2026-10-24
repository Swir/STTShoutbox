# STTShoutbox

An independent shoutbox for **MyBB 1.8**, built by **SWIRTV TEAM**. Public chat, private conversations, forum activity notifications and a beginner-friendly setup page — in **English and Polish**.

[Polski / Polish](README.pl.md) · [Download a release](https://github.com/Swir/STTShoutbox/releases) · [Installation guide](INSTALL.txt)

![English chat preview with demonstration messages](docs/screenshots/chat-en.png)

## Install in three steps

Back up your forum files and database first.

1. Download the `STTShoutbox-2.3.0.zip` release asset and extract it. Upload the **contents** of `Upload` into the MyBB root directory, alongside `index.php`. Merge the `inc` and `jscripts` directories; do not delete their existing contents.
2. Open **Admin CP → Configuration → Plugins → STTShoutbox → Install & Activate**.
3. Click **Quick setup — customise your chat** beside the plugin. Choose the appearance, accent colour, height and message order, then save.

The chat appears on the forum homepage. No other shoutbox, PluginLibrary or separate bot account is required. The ZIP includes an offline `START-EN.html` guide and a Polish `START.html` guide.

## Features

- Public chat with group permissions, flood control and configurable message order.
- Username colours and smilies from your forum, plus a Unicode emoji picker.
- Mentions, replies, reactions, own-message editing and withdrawal.
- Private conversations, separate from MyBB PMs, respecting participant privacy settings.
- Built-in bot for new threads, replies and optional member registrations. Activity cards are filtered according to each reader's forum permissions; password-protected forums are excluded.
- Custom bot responses, optional sound, expandable and collapsible chat.
- Automatic light/dark detection, explicit light/dark palettes, accent picker, adjustable height and spacing.
- Quick setup, missing-file diagnostics and automatic placement for standard MyBB index templates.

## Languages

The chat and API messages follow the language already resolved by MyBB for the current visitor. The setup page and native settings labels follow the **ACP language**. Polish and English are included; other languages fall back to English. Dates use the corresponding locale and the member's forum time-zone offset.

Language files:

- `Upload/inc/languages/english/sttshoutbox.lang.php`
- `Upload/inc/languages/polish/sttshoutbox.lang.php`

The plugin does not install or replace MyBB's full language packs. Install the standard Polish MyBB pack if you want Polish as a selectable forum language. Messages, forum names, custom chat names, smiley names and administrator-authored bot replies are user content and are not machine-translated.

## Upgrade from 2.0, 2.1 or 2.2

Back up first. **Deactivate**, upload every file in `Upload`, then activate again and hard-refresh the forum. **Do not uninstall to update.** Existing conversation history and settings are retained. Existing installations keep the classic appearance; fresh installations default to automatic appearance.

## Requirements and limits

- MyBB 1.8; integration based on MyBB 1.8.41.
- PHP 8.1+ with `mbstring`; MySQL/MariaDB. SQLite and PostgreSQL are not supported.
- JavaScript in the visitor's browser; HTTPS recommended.
- Standard index templates containing `{$header}` work automatically. A custom template without that variable needs `{$sttshoutbox}` at the desired location.
- Automatic palette selection reads the surrounding background once on page load. Image or gradient backgrounds may require choosing light/dark manually.

This release is checked on PHP 8.5.12 and Chrome. The installer tests use an in-memory database double, not a full clean MyBB installation. Validate other hosting setups and heavily customised themes on a copy of your forum.

## Data and removal

Public chat messages include the sender's IP address. Account for chat storage in your forum privacy policy. Deactivation stops the chat. Uninstallation removes settings but deliberately preserves conversation tables; it is not a data-erasure operation. See [INSTALL.txt](INSTALL.txt) before removing stored data.

This package contains no forum database, user messages, credentials or third-party chat migration tool.

## Tests and contributions

With PHP CLI and `mbstring`, run `php tests/run.php tests english` and `php tests/run.php csrf polish`. Tests create a private temporary copy of the package and use in-memory database/template doubles. They do not connect to a forum database. JavaScript syntax can be checked with `node --check Upload/jscripts/sttshoutbox.js`.

Report reproducible problems in [Issues](https://github.com/Swir/STTShoutbox/issues), with MyBB/PHP versions and the affected theme. Never post passwords, API keys, database exports or private conversations.

## License

STTShoutbox code: [MIT](LICENSE.txt), SWIRTV TEAM. MyBB is a separate project by the MyBB Group. No third-party theme or graphical smiley collection is bundled.
