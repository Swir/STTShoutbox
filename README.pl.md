# STTShoutbox

Samodzielny shoutbox dla **MyBB 1.8**, autorstwa **SWIRTV TEAM**, dostępny po **polsku i angielsku**.

[English](README.md) · [Pobierz 2.4.0](https://github.com/Swir/STTShoutbox/releases/tag/v2.4.0) · [Pełna instrukcja](INSTALACJA.txt) · [Instrukcja bota](docs/BOT-AKTYWNOSCI.md)

![Polski interfejs z przykładowymi wiadomościami](docs/screenshots/chat-pl.png)

## Instalacja

Najpierw zrób kopię plików i bazy forum.

1. Pobierz `STTShoutbox-2.4.0.zip` z wydań i rozpakuj. Wgraj **zawartość folderu Upload** do katalogu MyBB, obok `index.php`. Scal katalogi `inc` i `jscripts`; nie usuwaj ich istniejącej zawartości.
2. **ACP → Konfiguracja → Pluginy → STTShoutbox → Zainstaluj i aktywuj**.
3. Kliknij **Szybki start — dopasuj czat**, wybierz wygląd i zapisz.

Czat pojawi się na stronie głównej standardowego forum. Nie wymaga innego shoutboxa, PluginLibrary ani osobnego konta bota. Paczka zawiera przewodnik `START.html` po polsku i `START-EN.html` po angielsku.

## Możliwości

- Czat ogólny, rozmowy prywatne, kolorowe nicki, emotikony i emoji.
- Świecąca kropka i licznik nieprzeczytanych rozmów prywatnych, także w zwiniętym nagłówku. Rozmowy z nowymi wiadomościami są na początku; odczyt następuje dopiero po faktycznym wyświetleniu wiadomości.
- Wzmianki, cytowanie, reakcje, edycja i wycofywanie własnych wiadomości.
- Własny bot: nowe tematy, odpowiedzi, opcjonalnie rejestracje oraz własne odpowiedzi na komendy.
- Powiadomienia bota respektują dostęp odbiorcy do działów; działy z hasłem są pomijane.
- Osobne filtry podziękowań i krótkich postów, własna lista słów, regulowany limit oraz tester przykładów w ACP bez publikowania postów.
- Wiadomości od najnowszych lub najstarszych, limity i uprawnienia grup.
- Automatyczny jasny/ciemny wygląd, kolor przewodni, wysokość i odstępy.
- Prosty panel startowy, sprawdzanie plików i pomoc w razie problemów.

## Dwa języki

Interfejs czatu i błędy używają języka ustalonego przez MyBB dla danego odwiedzającego. Panel startowy i ustawienia zaawansowane używają języka ACP. Przy innych językach używany jest angielski.

Tłumaczenia są w `Upload/inc/languages/polish/sttshoutbox.lang.php` i `english/sttshoutbox.lang.php`. Wtyczka nie instaluje całego polskiego pakietu MyBB. Własne wiadomości, nazwa czatu, działy, nazwy emotikonów i odpowiedzi bota nie są tłumaczone automatycznie.

## Ustawienia bota aktywności

Otwórz **ACP → Konfiguracja → Pluginy → STTShoutbox → Bot aktywności** albo link w Szybkim starcie. W jednym panelu wybierzesz rodzaje powiadomień, pomijane działy i dwa niezależne filtry:

- **Podziękowania:** domyślnie włączony, dopasowuje całe słowa lub frazy w dowolnym miejscu odpowiedzi. Możesz zmienić listę albo wybrać tryb samych podziękowań.
- **Krótkie posty:** domyślnie włączony, minimum **10 słów**. Dziewięć słów jest pomijane; dokładnie dziesięć przechodzi ten filtr.

Domyślnie reguły obejmują odpowiedzi; możesz rozszerzyć je na nowe tematy. Wyjątek dla linków i załączników jest początkowo wyłączony i dotyczy tylko limitu słów. Filtrowanie zapisanych wcześniej kart aktywności jest domyślnie włączone. **Tester** sprawdza aktualne wartości formularza, również niezapisane, i podaje liczbę słów oraz powody decyzji. Nie zapisuje ustawień ani nie dodaje postu.

Filtry dotyczą powiadomień bota. Posty forum, podziękowania, odsłanianie treści i historia rozmów pozostają zachowane. Szczegóły liczenia słów i działania historii znajdziesz w [instrukcji bota](docs/BOT-AKTYWNOSCI.md).

## Aktualizacja z 2.0–2.3

Zrób kopię, **dezaktywuj** wtyczkę, wgraj wszystkie pliki z Upload, aktywuj ponownie i odśwież forum. Nie odinstalowuj przy aktualizacji. Historia, dotychczasowe ustawienia i wybrany wygląd zostają zachowane. Wersja 2.4.0 dodaje nowe ustawienia filtrów z wartościami opisanymi powyżej. Po aktualizacji sprawdź panel **Bot aktywności**.

## Wymagania i sprawdzenie

MyBB 1.8, PHP 8.1+ z mbstring, MySQL/MariaDB. Zalecane HTTPS. Brak obsługi SQLite/PostgreSQL. Integracja bazuje na MyBB 1.8.41; testy wydania wykonano na PHP 8.5.12 i w Chrome. Testy instalatora korzystają z bazy zastąpionej pamięcią testową; nie zastępują sprawdzenia czystej instalacji na każdym hostingu.

Nietypowy szablon index bez `{$header}` wymaga ręcznego umieszczenia `{$sttshoutbox}` przez autora motywu. Przy tle obrazkowym lub gradiencie może być potrzebny ręczny wybór jasnej/ciemnej palety.

## Dane i usuwanie

Publiczne wiadomości zapisują także adres IP nadawcy. Uwzględnij to w zasadach prywatności forum. Odinstalowanie usuwa ustawienia, ale **zachowuje historię**. Pełne usuwanie danych wymaga osobnej operacji opisanej w instrukcji. Paczka nie zawiera danych forum STT ani migratora innych shoutboxów.

Kod STTShoutbox jest na [licencji MIT](LICENSE.txt). MyBB to oddzielny projekt MyBB Group. W paczce nie ma cudzych motywów ani zestawów graficznych emotikonów.
