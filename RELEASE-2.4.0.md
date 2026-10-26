# STTShoutbox 2.4.0 — configurable activity bot

Released 2026-10-26. MyBB 1.8 · PHP 8.1+ with mbstring · MySQL/MariaDB · English and Polish.

## English

The activity bot now has its own ACP page with independent thank-you and short-post filters. Configure the phrase list, matching mode, minimum word count, scope and optional exemptions, then try a sample without saving settings or publishing a post.

- **Defaults:** both filters on; complete-word/phrase matching anywhere; 10-word minimum; replies only. Nine words are skipped and ten pass the length rule.
- Choose acknowledgement-only matching, include new-topic first posts, or exempt links and attachments (including those not embedded in the post) from the short-post rule. The exemption is off by default.
- Previously recorded activity cards use the current filters by default. Turning a filter off can reveal stored cards; events skipped at creation are not backfilled.
- Quotes, URLs, code and media do not inflate the prose word count. Text inside hide tags is counted. Forum permissions, moderation visibility and exclusions remain enforced for actual cards.
- Private unread notifications are included: a dot and counter on the private tab and collapsed header, unread-first conversations, and read acknowledgements when messages are actually visible.

Open **Admin CP → Configuration → Plugins → STTShoutbox → Activity bot**, or follow the link in Quick setup. Posts, thanks/unlock plugins and chat history are preserved.

**Install:** download `STTShoutbox-2.4.0.zip`, upload the contents of `Upload` to the forum root, then Install & Activate. **Update:** back up, deactivate, upload all files, activate again and refresh. Do not uninstall. Review the new filter defaults after updating.

Details: [Activity bot guide](https://github.com/Swir/STTShoutbox/blob/v2.4.0/docs/ACTIVITY-BOT.md), [installation](https://github.com/Swir/STTShoutbox/blob/v2.4.0/INSTALL.txt), [changelog](https://github.com/Swir/STTShoutbox/blob/v2.4.0/CHANGELOG.md).

## Polski

Bot aktywności ma własny panel w ACP z niezależnymi filtrami podziękowań i krótkich postów. Ustaw listę fraz, sposób dopasowania, minimalną liczbę słów, zakres oraz wyjątki, a następnie sprawdź przykład bez zapisywania ustawień i publikowania postu.

- **Domyślnie:** oba filtry włączone, całe słowa/frazy w dowolnym miejscu, minimum 10 słów, tylko odpowiedzi. Dziewięć słów jest pomijane, a dziesięć przechodzi limit długości.
- Możesz wybrać same podziękowania, rozszerzyć reguły na nowe tematy lub przepuszczać linki i załączniki, także niewstawione do treści postu, przez filtr długości. Ten wyjątek jest początkowo wyłączony.
- Istniejące zapisane karty są domyślnie filtrowane aktualnymi regułami. Wyłączenie filtra może je przywrócić; powiadomienia pominięte już przy utworzeniu nie są odtwarzane.
- Cytaty, adresy, kod i multimedia nie zwiększają liczby słów tekstu. Treść w znacznikach hide jest liczona. Prawdziwe powiadomienia nadal respektują dostęp do działów, moderację i wykluczenia.
- W wydaniu są także powiadomienia o nieprzeczytanych rozmowach prywatnych: kropka i licznik, alert w zwiniętym nagłówku, nowe rozmowy na początku listy i odczyt dopiero przy widocznej wiadomości.

Panel: **ACP → Konfiguracja → Pluginy → STTShoutbox → Bot aktywności** lub link w Szybkim starcie. Posty, podziękowania, odsłanianie treści i historia czatu pozostają zachowane.

**Instalacja:** pobierz `STTShoutbox-2.4.0.zip`, wgraj zawartość `Upload` do katalogu forum, wybierz Zainstaluj i aktywuj. **Aktualizacja:** kopia, dezaktywacja, wgranie wszystkich plików, ponowna aktywacja i odświeżenie. Nie odinstalowuj. Po aktualizacji sprawdź nowe ustawienia filtrów.

Szczegóły: [instrukcja bota](https://github.com/Swir/STTShoutbox/blob/v2.4.0/docs/BOT-AKTYWNOSCI.md), [instalacja](https://github.com/Swir/STTShoutbox/blob/v2.4.0/INSTALACJA.txt), [lista zmian](https://github.com/Swir/STTShoutbox/blob/v2.4.0/CHANGELOG.md).
