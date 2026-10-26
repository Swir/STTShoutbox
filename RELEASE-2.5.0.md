# STTShoutbox 2.5.0 — bundled message sound

Released 2026-10-26. MyBB 1.8 · PHP 8.1+ with mbstring · MySQL/MariaDB · English and Polish.

## English

A gentle original two-note message chime is now included in the installation ZIP. The plugin entry is simpler, with separate **Appearance & setup**, **Activity bot** and **Advanced settings** links.

- Upload `jscripts/sttshoutbox/notify.wav` with the other plugin files. Audio comes from your own forum, with no external sound service.
- Use **♪** in the chat header for on/off. The adjacent sound settings provide a volume control and preview.
- Sound starts off. The current browser remembers the preference and volume separately for each forum account. Browsers require a click before audio can play, including after reopening the forum.
- Incoming public and private chat messages can notify you. Loading history, your own messages and activity-bot notices stay silent; bursts are limited to one sound every three seconds.
- Existing activity-bot filters, configuration and conversation history are preserved. There is no database migration for audio preferences.

**Download:** choose `STTShoutbox-2.5.0.zip` below, rather than GitHub's source-code archive. **Update:** back up, deactivate, upload every file from `Upload` (including the new sound folder), activate again and hard-refresh the forum. Do not uninstall. New installations use **Install & Activate**.

[English installation guide](https://github.com/Swir/STTShoutbox/blob/v2.5.0/INSTALL.txt) · [Changelog](https://github.com/Swir/STTShoutbox/blob/v2.5.0/CHANGELOG.md)

## Polski

Paczka zawiera teraz własny, łagodny sygnał nowej wiadomości z dwóch dźwięków. Wpis przy wtyczce jest uporządkowany: osobne przyciski **Wygląd i ustawienia**, **Bot aktywności** oraz **Ustawienia zaawansowane**.

- Plik `jscripts/sttshoutbox/notify.wav` wgrywasz z resztą wtyczki. Dźwięk pochodzi z Twojego forum, bez dodatkowego serwisu.
- Przycisk **♪** w nagłówku czatu włącza i wyłącza sygnał. Obok otworzysz regulację głośności i odsłuch.
- Początkowo dźwięk jest wyłączony. Przeglądarka zapamiętuje wybór i głośność dla każdego konta. Przed odtwarzaniem wymaga kliknięcia, również po ponownym wejściu na forum.
- Powiadomienia obejmują nowe wiadomości innych osób na czacie ogólnym i prywatnym. Historia, własne wpisy i ogłoszenia bota pozostają bez dźwięku. Seria wiadomości uruchamia najwyżej jeden sygnał na trzy sekundy.
- Filtry bota, konfiguracja i historia rozmów są zachowane. Ustawienia dźwięku nie wymagają migracji bazy.

**Pobierz:** wybierz poniżej `STTShoutbox-2.5.0.zip`, czyli gotową paczkę instalacyjną. **Aktualizacja:** kopia, dezaktywacja, wgranie wszystkich plików z `Upload` (także nowego folderu dźwięku), ponowna aktywacja i odświeżenie forum. Nie odinstalowuj. Przy pierwszej instalacji wybierz **Zainstaluj i aktywuj**.

[Polska instrukcja instalacji](https://github.com/Swir/STTShoutbox/blob/v2.5.0/INSTALACJA.txt) · [Lista zmian](https://github.com/Swir/STTShoutbox/blob/v2.5.0/CHANGELOG.md)
