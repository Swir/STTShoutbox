# Bot aktywności — STTShoutbox 2.4.0

[English guide](ACTIVITY-BOT.md) · [Powrót do README](../README.pl.md)

Otwórz **ACP → Konfiguracja → Pluginy → STTShoutbox → Bot aktywności**. Link znajdziesz także w Szybkim starcie. Potrzebujesz uprawnienia do zmiany ustawień MyBB.

Panel ustawia powiadomienia o aktywności forum. Nie moderuje ani nie usuwa postów, nie wyłącza podziękowań i odsłaniania treści, a także nie filtruje rozmów wpisywanych bezpośrednio do shoutboxa.

## Co bot ma ogłaszać

Główny przełącznik steruje powiadomieniami bota. Osobno włączysz nowe tematy, odpowiedzi oraz rejestracje. Domyślnie nowe tematy i odpowiedzi są włączone, a rejestracje wyłączone. Wykluczenie działu obejmuje też jego poddziały. Użytkownik widzi wyłącznie karty z działów, do których ma dostęp; działy zabezpieczone hasłem pozostają pomijane.

## Domyślne ustawienia filtrów

| Ustawienie | Domyślnie | Działanie |
| --- | --- | --- |
| Filtr podziękowań | Włączony | Pomija powiadomienie pasujące do ustawionej reguły słów. |
| Sposób dopasowania | Zawiera frazę | Szuka całego słowa lub frazy w dowolnym miejscu tekstu autora. |
| Lista fraz | `dzięki`, `dziękuję`, `dziękujemy`, `thanks`, `thank you`, `thx` | Jedna fraza w wierszu; listę możesz zmienić. |
| Filtr krótkich postów | Włączony | Pomija powiadomienie o poście krótszym od minimum. |
| Minimalna liczba słów | 10 | Dziewięć słów jest pomijane; dokładnie dziesięć przechodzi ten filtr. |
| Zakres | Odpowiedzi | Pierwsze posty nowych tematów nie podlegają tym filtrom, dopóki nie wybierzesz obu rodzajów. |
| Wyjątek dla linków i załączników | Wyłączony | Po włączeniu link lub załącznik (także niewstawiony do treści postu) może ominąć wyłącznie limit słów. |
| Filtruj istniejące karty | Włączone | Stosuje aktualne reguły przy odczycie zapisanych wcześniej powiadomień. |

Oba filtry działają niezależnie. Wystarczy odrzucenie przez jedną włączoną regułę, aby pominąć powiadomienie. Post mający co najmniej dziesięć słów nadal może odpaść przez filtr podziękowań. Wyjątek dla linków i załączników nie omija filtra podziękowań, przełączników bota, wykluczonych działów ani uprawnień.

## Dopasowanie podziękowań

Wielkość liter i polskie znaki nie zmieniają dopasowania: `DZIĘKI`, `dzieki` i `dzięki` oznaczają tę samą frazę. Dopasowywane są całe słowa i frazy, a nie ich fragmenty w dłuższym wyrazie. Adresy URL i cytaty nie są przeszukiwane jak własny tekst autora.

W trybie **zawiera frazę** tekst `Dzięki, ale link już nie działa` pasuje, bo zawiera słowo `dzięki`. Jest to tryb domyślny; może ukryć także wartościową odpowiedź, w której ktoś dziękuje. Wybierz tryb **samych podziękowań**, jeżeli takie rozbudowane odpowiedzi mają pozostać. Ten tryb rozpoznaje krótkie potwierdzenia, np. `Dziękuję bardzo`, i typowe polskie/angielskie warianty zamiast dowolnego wystąpienia w dłuższej wypowiedzi.

Lista mieści do 100 fraz, po 80 znaków każda, łącznie do 4000 znaków. Wpisuj jedną frazę w wierszu. Po zmianie listy użyj testera. Unikaj niejednoznacznych krótkich słów, np. `ty`: po polsku to zwykły zaimek, a po angielsku skrót podziękowania.

## Jak liczone są słowa

Bot ocenia tekst autora po usunięciu formatowania:

- Cytaty, również zagnieżdżone, nie zwiększają liczby słów i nie uruchamiają filtra podziękowań.
- Znaczniki formatowania MyCode oraz ich parametry nie są słowami.
- Adresy URL, bloki kodu i multimedia nie są liczone jako zwykły tekst.
- Tekst między znacznikami hide jest liczony; ukrycie treści nie powoduje uznania postu za pusty.
- Emoji i interpunkcja nie dodają słów.

Minimum możesz ustawić od 1 do 100. Aby dopuścić dowolną długość, wyłącz filtr krótkich postów. Widoczny opis linku może zawierać liczone słowa; sam adres docelowy nie jest liczony.

Krótkie wpisy z linkiem albo załącznikiem mogą korzystać z opcjonalnego wyjątku. Osadzony obraz lub blok multimedialny sam w sobie nie jest tekstem. Tester ma opcję załącznika, także takiego, którego nie wstawiono do treści postu.

## Sprawdzenie przykładu przed zapisaniem

Ustaw przełączniki, wybierz rodzaj zdarzenia testowego i wklej przykładową treść. Tester pokaże decyzję, liczbę słów oraz powody. Korzysta z **bieżących wartości formularza, również jeszcze niezapisanych**. Testowanie nie zapisuje konfiguracji, nie dodaje postu i nie wysyła wiadomości na czat.

Tester respektuje główny przełącznik bota i przełączniki rodzajów powiadomień. Wynik opisuje reguły treści dla przykładu. Wyświetlanie prawdziwych kart dodatkowo uwzględnia wykluczenia działów, widoczność po moderacji i uprawnienia odbiorcy.

Przy ustawieniach domyślnych:

| Przykładowa odpowiedź | Wynik |
| --- | --- |
| `Dzięki!` | Pominięta: pasuje do podziękowań i ma mniej niż 10 słów. |
| `Dzięki, ale ten link już nie działa i wymaga poprawy.` | Pominięta przez filtr podziękowań, mimo osiągnięcia 10 słów. |
| `Nowa lista działa poprawnie na wszystkich urządzeniach od dzisiejszego poranka.` | Przechodzi filtry treści: 10 słów i brak frazy podziękowania. |
| `[quote]Dłuższa wypowiedź innego użytkownika zacytowana z wcześniejszego wpisu.[/quote] dzięki` | Cytat jest pomijany; pozostaje krótkie podziękowanie. |
| Nowy temat z krótką treścią | Nie podlega filtrom ustawionym tylko na odpowiedzi; nadal obowiązuje przełącznik nowych tematów i dostęp do działu. |

Gdy reguły działają zgodnie z oczekiwaniami, kliknij **Zapisz ustawienia**. Pozostałe ustawienia shoutboxa i historia rozmów pozostają zachowane.

## Historia i wyłączanie filtrów

Przy włączonym filtrowaniu istniejących kart zapisane wcześniej powiadomienia są oceniane aktualnymi regułami podczas odczytu. Wyłączenie odpowiedniego filtra może ponownie pokazać te zapisane karty. Wyłączenie filtrowania istniejących kart pozostawia je poza filtrami treści, a nowe zdarzenia nadal podlegają regułom.

Jeżeli powiadomienie odpadło już przy dodawaniu postu, nie powstał dla niego wpis aktywności na czacie. Późniejsze wyłączenie filtra **nie odtworzy** tego brakującego powiadomienia. Oryginalny post, podziękowania, odsłanianie treści i zapisane rozmowy pozostają zachowane w obu sytuacjach.

## Aktualizacja istniejącej instalacji

Zrób kopię, dezaktywuj plugin, wgraj całą zawartość `Upload`, aktywuj ponownie i odśwież forum. Nie odinstalowuj przy aktualizacji. Nowe ustawienia są dodawane bez resetowania dotychczasowych wartości. Po aktualizacji otwórz Bot aktywności, sprawdź domyślne reguły i przetestuj typowe wpisy swojej społeczności.
