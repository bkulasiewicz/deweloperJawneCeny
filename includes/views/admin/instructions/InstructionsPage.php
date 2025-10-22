<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Strona Instrukcji - pełna instrukcja obsługi wtyczki
 */
class InstructionsPage {

    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets() {
        wp_enqueue_style(
            'instructions-page',
            JAWNECENY_PLUGIN_URL . 'includes/views/admin/instructions/InstructionsPage.css',
            [],
            JAWNECENY_VERSION
        );

        wp_enqueue_script(
            'instructions-page',
            JAWNECENY_PLUGIN_URL . 'includes/views/admin/instructions/InstructionsPage.js',
            ['jquery'],
            JAWNECENY_VERSION,
            true
        );
    }

    public function render() {
        ?>
        <div class="wrap instructions-page">
            <h1>Instrukcja obsługi wtyczki</h1>

            <div class="instructions-accordion">

                <!-- Accordion Item 1 -->
                <div class="accordion-item">
                    <button class="accordion-header" type="button">
                        <span class="accordion-title">1. Pierwsze kroki - skonfiguruj wtyczkę</span>
                        <span class="accordion-icon">▼</span>
                    </button>
                    <div class="accordion-content">
                        <p class="intro">
                            Zanim zaczniesz wyświetlać dane na stronie, musisz najpierw dodać informacje o swoich nieruchomościach. Proces składa się z trzech prostych kroków.
                        </p>

                        <div class="step">
                            <h3>Krok 1: Dodaj dane dostawcy</h3>
                            <ol>
                                <li>W menu wtyczki przejdź do zakładki <strong>"Dane dostawcy"</strong></li>
                                <li>Wypełnij formularz swoimi danymi</li>
                                <li>Kliknij przycisk <strong>"Zapisz"</strong></li>
                            </ol>
                            <p class="note">Te dane będą wykorzystywane we wszystkich generowanych plikach i dokumentach.</p>
                        </div>

                        <div class="step">
                            <h3>Krok 2: Dodaj inwestycję</h3>
                            <ol>
                                <li>Przejdź do zakładki <strong>"Zasoby"</strong> w menu wtyczki</li>
                                <li>Jeśli nie masz jeszcze inwestycji, zobaczysz komunikat "Brak danych o inwestycji"</li>
                                <li>Kliknij przycisk <strong>"Dodaj Inwestycję"</strong></li>
                                <li>Wypełnij formularz z danymi inwestycji (nazwa, adres, kod pocztowy)</li>
                                <li>Kliknij przycisk <strong>"Zapisz"</strong></li>
                            </ol>

                            <div class="info-box" style="background: #fff3cd; border-left: 4px solid #ff9800; margin-top: 20px;">
                                <p><strong>⚠️ BARDZO WAŻNE - Konfiguracja ma wpływ na późniejsze kroki!</strong></p>

                                <p style="margin-top: 15px;"><strong>1. Konfiguracja komponentów</strong></p>
                                <p>W formularzu inwestycji znajdziesz sekcję <strong>"Konfiguracja komponentów"</strong> z checkboxami:</p>
                                <ul style="margin-left: 20px; margin-top: 8px;">
                                    <li><strong>części nieruchomości</strong> (np. miejsce postojowe)</li>
                                    <li><strong>pomieszczenia przynależne</strong> (np. komórka lokatorska)</li>
                                    <li><strong>prawa niezbędne do korzystania z lokalu</strong></li>
                                    <li><strong>inne rodzaje świadczeń pieniężnych</strong></li>
                                </ul>
                                <p style="margin-top: 10px; font-weight: 600;">Zaznaczenie tych pól ma <strong>kluczowe znaczenie</strong>, ponieważ:</p>
                                <ul style="margin-left: 20px; margin-top: 8px;">
                                    <li>Określa sposób sprzedaży inwestycji i sposób raportowania cen do ministerstwa</li>
                                    <li>Odblokowuje dodatkowe pola cenowe w formularzu dodawania lokali/domów</li>
                                    <li>Wpływa na generowanie prawidłowych plików XML i CSV zgodnych z ustawą</li>
                                </ul>
                                <p style="margin-top: 10px;"><em>Przykład: Jeśli zaznaczysz "części nieruchomości", to przy dodawaniu lokalu pojawi się pole do wprowadzenia ceny za miejsce postojowe.</em></p>

                                <p style="margin-top: 15px;"><strong>2. Pola marketingowe</strong></p>
                                <p>W formularzu inwestycji znajdziesz również sekcję <strong>"Pola marketingowe"</strong> z checkboxami:</p>
                                <ul style="margin-left: 20px; margin-top: 8px;">
                                    <li><strong>Piętro</strong> - numer piętra nieruchomości</li>
                                    <li><strong>Liczba pokoi</strong> - ilość pokoi w mieszkaniu/domu</li>
                                    <li><strong>Dodatkowy opis</strong> - pole tekstowe na informacje marketingowe</li>
                                    <li><strong>Ogród</strong> - powierzchnia przynależnego ogrodu [m²]</li>
                                    <li><strong>Plan mieszkania</strong> - możliwość upload pliku PDF z planem</li>
                                </ul>
                                <p style="margin-top: 10px;">Zaznaczenie tych checkboxów włącza odpowiednie pola w formularzu dodawania zasobów (Krok 3). Te pola nie są raportowane do ministerstwa - służą tylko do wyświetlania dodatkowych informacji na Twojej stronie internetowej.</p>
                                <p style="margin-top: 10px;"><em>Przykład: Jeśli zaznaczysz "Liczba pokoi", to przy dodawaniu lokalu będziesz mógł wprowadzić ilość pokoi, a informacja ta będzie wyświetlana w tabeli z mieszkaniami.</em></p>
                            </div>
                        </div>

                        <div class="step">
                            <h3>Krok 3: Dodaj zasoby (mieszkania, lokale)</h3>
                            <ol>
                                <li>Pozostań w zakładce <strong>"Zasoby"</strong></li>
                                <li>Kliknij przycisk <strong>"Dodaj Zasób"</strong></li>
                                <li>Wypełnij formularz z danymi nieruchomości</li>
                                <li>Kliknij przycisk <strong>"Zapisz zasób"</strong></li>
                                <li>Powtórz proces dla każdego mieszkania/lokalu w swojej inwestycji</li>
                            </ol>
                            <p class="note">Możesz w każdej chwili edytować lub usunąć dodane zasoby klikając na karcie zasobu.</p>
                        </div>
                    </div>
                </div>

                <!-- Accordion Item 2 -->
                <div class="accordion-item">
                    <button class="accordion-header" type="button">
                        <span class="accordion-title">2. Wyświetlanie zasobów na stronie</span>
                        <span class="accordion-icon">▼</span>
                    </button>
                    <div class="accordion-content">
                        <p class="intro">
                            Teraz, gdy masz już dodane zasoby, możesz wyświetlić je na swojej stronie internetowej. Masz do wyboru dwa sposoby - wybierz ten, który jest dla Ciebie wygodniejszy.
                        </p>

                        <div class="method">
                            <h3>Sposób A: Bloki Gutenberga (wizualny edytor) - POLECANY</h3>
                            <p>To najprostszy sposób.</p>

                            <h4>Blok 1: "Lista Zasobów" - tabela lub kafelki z mieszkaniami</h4>
                            <p><strong>Jak dodać:</strong></p>
                            <ol>
                                <li>Otwórz stronę lub wpis, gdzie chcesz wyświetlić listę mieszkań</li>
                                <li>Kliknij przycisk <strong>"+"</strong> (dodaj blok)</li>
                                <li>W wyszukiwarce wpisz <strong>"Lista Zasobów"</strong></li>
                                <li>Kliknij na blok <strong>"Lista Zasobów"</strong></li>
                                <li>Po prawej stronie edytora zobaczysz panel ustawień bloku</li>
                            </ol>

                            <p><strong>Co możesz skonfigurować:</strong></p>
                            <ul>
                                <li>Typy nieruchomości: Zaznacz które rodzaje chcesz pokazać (mieszkania, garaże, itp.)</li>
                                <li>Sposób wyświetlania: Tabela lub kafelki</li>
                                <li>Widoczne kolumny: Zaznacz które informacje mają być widoczne (numer, cena, powierzchnia, itp.)</li>
                                <li>Nazwy kolumn: Możesz zmienić nazwy każdej kolumny</li>
                                <li>Kolejność kolumn: Przeciągnij kolumny aby zmienić ich kolejność</li>
                                <li>Kolory: Zmień kolory tła, tekstu, nagłówków, przycisków</li>
                                <li>Czcionki: Wybierz czcionki dla nagłówków i treści</li>
                                <li>Nawigacja: Ustaw klikalne wiersze lub przycisk "Zobacz więcej"</li>
                                <li>Sortowanie: Włącz sortowanie po kliknięciu w nagłówek kolumny</li>
                            </ul>

                            <ol start="6">
                                <li>Kliknij <strong>"Opublikuj"</strong> lub <strong>"Aktualizuj"</strong></li>
                                <li>Gotowe! Odwiedź swoją stronę, aby zobaczyć efekt</li>
                            </ol>

                            <h4>Blok 2: "Filtry Zasobów" - zewnętrzne filtrowanie dla klientów</h4>
                            <p><strong>Jak dodać:</strong></p>
                            <ol>
                                <li>Na tej samej stronie co "Lista Zasobów" kliknij przycisk <strong>"+"</strong></li>
                                <li>W wyszukiwarce wpisz <strong>"Filtry Zasobów"</strong></li>
                                <li>Kliknij na blok <strong>"Filtry Zasobów"</strong></li>
                                <li>Umieść ten blok obok "Lista Zasobów"</li>
                            </ol>

                            <p><strong>Co możesz skonfigurować:</strong></p>
                            <ul>
                                <li>Dostępne filtry: Zaznacz które filtry mają być widoczne (rodzaj, pokoje, piętro, powierzchnia, cena, status)</li>
                                <li>Kolory: Dostosuj kolory przycisków i tła</li>
                                <li>Układ: Wybierz sposób wyświetlania filtrów</li>
                            </ul>

                            <div class="important">
                                <p><strong>WAŻNE:</strong> Aby filtry działały:</p>
                                <ol>
                                    <li>W bloku "Lista Zasobów" włącz opcję <strong>"Czytaj filtry z URL"</strong> (znajdziesz ją w panelu ustawień bloku)</li>
                                    <li>Oba bloki muszą być na tej samej stronie</li>
                                </ol>
                            </div>

                            <p class="note">Jak to działa: Klient wybiera filtry, lista automatycznie się aktualizuje bez przeładowania strony</p>

                            <h4>Blok 3: "Szczegóły zasobu" - pojedyncze mieszkanie</h4>
                            <p><strong>Jak dodać:</strong></p>
                            <ol>
                                <li>Utwórz osobną stronę dla każdego mieszkania (np. "/mieszkania/a1")</li>
                                <li>Na tej stronie kliknij przycisk <strong>"+"</strong> i wyszukaj <strong>"Szczegóły zasobu"</strong></li>
                                <li>Kliknij na blok <strong>"Szczegóły zasobu"</strong></li>
                            </ol>

                            <p><strong>Co możesz skonfigurować:</strong></p>
                            <ul>
                                <li>Widoczne pola: Zaznacz które informacje mają być wyświetlane</li>
                                <li>Nazwy pól: Zmień nazwy każdego pola</li>
                                <li>Kolejność pół: Przeciągnij pola aby zmienić ich kolejność</li>
                                <li>Kolory: Dostosuj kolory tła, tekstu, nagłówków</li>
                                <li>Historia cen: Włącz/wyłącz przycisk historii cen</li>
                            </ul>

                            <div class="important">
                                <p><strong>WAŻNE:</strong> Blok automatycznie wykrywa numer lokalu z adresu URL strony. Jeśli strona ma adres <code>/mieszkania/A1</code>, blok wyświetli dane lokalu A1.</p>
                            </div>

                            <ol start="4">
                                <li>Zapisz stronę</li>
                                <li>Gotowe!</li>
                            </ol>
                        </div>

                        <div class="method">
                            <h3>Sposób B: Shortcode</h3>

                            <h4>Jak użyć shortcode dla listy zasobów:</h4>
                            <ol>
                                <li>Przejdź do zakładki <strong>"Generator Shortcode"</strong> w menu wtyczki</li>
                                <li>Przełącz na zakładkę <strong>"Lista zasobów"</strong></li>
                                <li>Skonfiguruj wygląd tabeli używając formularza</li>
                                <li>Na dole strony w sekcji "Wygenerowany shortcode" zobaczysz kod, np:<br>
                                    <code>[resources_list types="residential_unit" columns="nr_lokalu:Numer,powierzchnia_uzytkowa:Powierzchnia,cena_calkowita:Cena"]</code>
                                </li>
                                <li>Skopiuj ten kod</li>
                                <li>Otwórz stronę, gdzie chcesz wyświetlić mieszkania</li>
                                <li>Dodaj blok <strong>"Shortcode"</strong></li>
                                <li>Wklej skopiowany kod</li>
                                <li>Zapisz stronę</li>
                            </ol>

                            <h4>Jak użyć shortcode dla pojedynczego zasobu:</h4>
                            <ol>
                                <li>Utwórz stronę dla mieszkania (np. "/mieszkania/a1")</li>
                                <li>Dodaj blok <strong>"Shortcode"</strong></li>
                                <li>Wklej kod: <code>[resource_single]</code></li>
                                <li>Shortcode automatycznie wykryje numer lokalu z adresu URL</li>
                                <li>Zapisz stronę</li>
                            </ol>

                            <p class="note">W Generatorze Shortcode możesz też skonfigurować wygląd pojedynczego zasobu w zakładce "Szczegóły zasobu".</p>
                        </div>
                    </div>
                </div>

                <!-- Accordion Item 3 -->
                <div class="accordion-item">
                    <button class="accordion-header" type="button">
                        <span class="accordion-title">3. Następny krok - spełnienie wymogów prawnych</span>
                        <span class="accordion-icon">▼</span>
                    </button>
                    <div class="accordion-content">
                        <h3>Dlaczego potrzebujesz więcej?</h3>
                        <p>
                            Jako deweloper jesteś <strong>zobowiązany prawnie</strong> do przekazywania danych o cenach mieszkań
                            do Ministerstwa Rozwoju i Technologii zgodnie z <strong>Ustawą o jawności cen nieruchomości</strong>.
                        </p>
                        <p>Do tej pory dodałeś dane i wyświetliłeś je na stronie. To świetny początek!</p>
                        <p>Ale <strong>aby spełnić wymogi prawne</strong>, musisz:</p>
                        <ul>
                            <li>Generować pliki XML i CSV w formacie wymaganym przez ministerstwo</li>
                            <li>Automatycznie aktualizować te pliki codziennie</li>
                            <li>Udostępnić je ministerstwu przez publicznie dostępne adresy URL</li>
                        </ul>
                        <p><strong>Bez tego możesz być narażony na kary prawne.</strong></p>

                        <h3>Rozwiązanie: Pełna wersja wtyczki</h3>
                        <p>Pełna wersja wtyczki automatyzuje cały proces zgodności z ustawą:</p>
                        <ul>
                            <li>Automatyczne generowanie plików XML i CSV - zgodnie z wymaganiami ministerstwa</li>
                            <li>Automatyczna aktualizacja co 24h - nie musisz pamiętać o niczym</li>
                            <li>Integracja z portalem dane.gov.pl - automatyczne zasilanie danych</li>
                            <li>Pełna zgodność z ustawą - spokój prawny</li>
                        </ul>

                        <h3>Jak kupić pełną wersję?</h3>
                        <p>Odwiedź stronę: <a href="https://www.deweloperjawneceny.pl/?utm_source=wordpress&utm_medium=instruction&utm_campaign=promotion" target="_blank">https://www.deweloperjawneceny.pl</a></p>
                        <p>Po zakupie otrzymasz:</p>
                        <ul>
                            <li>Plik wtyczki w wersji Premium</li>
                            <li>Dostęp do wszystkich funkcji automatyzacji</li>
                        </ul>
                        <p class="note">Po zainstalowaniu pełnej wersji, przejdź do kolejnej części tej instrukcji.</p>
                    </div>
                </div>

                <!-- Accordion Item 4 -->
                <div class="accordion-item">
                    <button class="accordion-header" type="button">
                        <span class="accordion-title">4. Włączenie automatyzacji i integracja z Ministerstwem</span>
                        <span class="accordion-icon">▼</span>
                    </button>
                    <div class="accordion-content">
                        <div class="important">
                            <p><strong>UWAGA:</strong> Ta część instrukcji dotyczy tylko pełnej wersji wtyczki. Jeśli jeszcze jej nie masz, wróć do poprzedniej sekcji.</p>
                        </div>

                        <p class="intro">
                            Po zakupie i zainstalowaniu pełnej wersji wtyczki, musisz wykonać dwa kluczowe kroki:<br>
                            1. Włączyć automatyczne generowanie plików<br>
                            2. Zgłosić adresy plików do Ministerstwa, aby mogło je pobierać
                        </p>
                        <p>To jednorazowy proces - wystarczy zrobić to raz, a później wszystko działa automatycznie.</p>

                        <div class="step">
                            <h3>Krok 1: Włącz automatyzację</h3>
                            <ol>
                                <li>W menu wtyczki przejdź do zakładki <strong>"Pulpit"</strong></li>
                                <li>Znajdź kafelek <strong>"Automatyzacja"</strong></li>
                                <li>Zobaczysz status: <strong>"Nieaktywny"</strong></li>
                                <li>Kliknij przycisk <strong>"Włącz External Cron"</strong></li>
                                <li>Poczekaj chwilę - zobaczysz komunikat o powodzeniu</li>
                                <li>Status zmieni się na: <strong>"Aktywny"</strong></li>
                                <li>Pod statusem zobaczysz: <strong>"Częstotliwość: Co 24 godziny"</strong></li>
                            </ol>
                            <p><strong>Co się właśnie stało?</strong><br>
                            Wtyczka będzie teraz automatycznie generować pliki XML i MD5 co 24 godziny. Nie musisz już nic robić - pliki będą zawsze aktualne.</p>
                            <p class="note">Jeśli kiedykolwiek zechcesz wyłączyć automatyzację, kliknij przycisk "Wyłącz External Cron".</p>
                        </div>

                        <div class="step">
                            <h3>Krok 2: Skopiuj adresy plików XML i MD5</h3>
                            <ol>
                                <li>W menu wtyczki przejdź do zakładki <strong>"Publikacja Danych"</strong></li>
                                <li>W prawym górnym rogu zobaczysz ramkę <strong>"Publiczne adresy plików"</strong></li>
                                <li>Znajdziesz tam dwa adresy (Plik XML i Plik MD5)</li>
                                <li>Skopiuj oba adresy (zaznacz i naciśnij Ctrl+C lub Cmd+C)</li>
                            </ol>
                            <p class="note">Te adresy są unikalne dla Twojej strony. Ministerstwo będzie z nich pobierać dane codziennie.</p>
                        </div>

                        <div class="step">
                            <h3>Krok 3: Pobierz formularz dla Ministerstwa</h3>
                            <p>Przygotowaliśmy gotowy formularz, który musisz wypełnić i wysłać do Ministerstwa.</p>
                            <div class="download">
                                <a href="<?php echo esc_url(JAWNECENY_PLUGIN_URL . 'assets/documents/Dane_niezbedne_do_uruchomienia_automatyzacji.docx'); ?>"
                                   class="button button-primary button-large"
                                   download>
                                    Pobierz formularz (plik DOCX)
                                </a>
                            </div>
                        </div>

                        <div class="step">
                            <h3>Krok 4: Wypełnij formularz krok po kroku</h3>
                            <p>Otwórz pobrany plik w programie Word lub Google Docs i uzupełnij wszystkie pola:</p>

                            <table class="form-table">
                                <thead>
                                    <tr>
                                        <th>Pole w formularzu</th>
                                        <th>Co wpisać</th>
                                        <th>Przykład</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Imię i nazwisko osoby zgłaszającej wniosek</td>
                                        <td>Twoje imię i nazwisko</td>
                                        <td>Jan Kowalski</td>
                                    </tr>
                                    <tr>
                                        <td>Nazwa dostawcy</td>
                                        <td>Nazwa Twojej firmy (dokładnie taka sama, jak w zakładce "Dane dostawcy")</td>
                                        <td>ABC Development Sp. z o.o.</td>
                                    </tr>
                                    <tr>
                                        <td>Adres URL pliku XML</td>
                                        <td>Wklej adres XML skopiowany w Kroku 2</td>
                                        <td><code>https://twoja-strona.pl/.../katalog-danych.xml</code></td>
                                    </tr>
                                    <tr>
                                        <td>Adres URL pliku MD5</td>
                                        <td>Wklej adres MD5 skopiowany w Kroku 2</td>
                                        <td><code>https://twoja-strona.pl/.../katalog-danych.md5</code></td>
                                    </tr>
                                    <tr>
                                        <td>Adres e-mail osoby do kontaktu</td>
                                        <td>Twój służbowy e-mail</td>
                                        <td>jan.kowalski@abcdevelopment.pl</td>
                                    </tr>
                                    <tr>
                                        <td>Częstotliwość aktualizacji</td>
                                        <td>Zostaw domyślną wartość</td>
                                        <td>codziennie</td>
                                    </tr>
                                </tbody>
                            </table>

                            <p class="note">Sprawdź dwukrotnie adresy URL - literówka spowoduje, że ministerstwo nie będzie mogło pobrać danych.</p>
                        </div>

                        <div class="step">
                            <h3>Krok 5: Wyślij formularz do Ministerstwa</h3>
                            <p>W polu <strong>"Do:"</strong> wpisz: <code>kontakt@dane.gov.pl</code></p>
                            <p>W polu <strong>"Temat:"</strong> wpisz:<br>
                            <code>"Wniosek o uruchomienie automatyzacji procesu udostępniania danych - [Nazwa Twojej firmy]"</code></p>

                            <p>W treści e-maila napisz:</p>
                            <div class="email-template">
                                <p>Dzień dobry,</p>
                                <p>Przesyłam wypełniony formularz z danymi niezbędnymi do uruchomienia automatyzacji procesu udostępniania danych o cenach mieszkań.</p>
                                <p>W załączniku znajduje się wypełniony dokument z adresami URL do plików XML i MD5.</p>
                                <p>Proszę o konfigurację automatycznego pobierania danych.</p>
                                <p>Pozdrawiam,<br>
                                [Twoje imię i nazwisko]<br>
                                [Nazwa firmy]<br>
                                [Telefon kontaktowy]</p>
                            </div>

                            <p>Załącz wypełniony formularz (plik DOCX), sprawdź wszystko jeszcze raz i wyślij e-mail.</p>
                        </div>

                        <div class="success">
                            <h3>Gotowe! Co teraz?</h3>
                            <p>Od tego momentu <strong>nie musisz nic robić</strong>:</p>
                            <ul>
                                <li>Wtyczka automatycznie generuje pliki co 24 godziny</li>
                                <li>Ministerstwo pobiera dane z podanych adresów</li>
                                <li>Ty tylko aktualizuj dane o mieszkaniach w zakładce "Zasoby"</li>
                                <li>Wszystko działa automatycznie</li>
                            </ul>
                            <p><strong>Jesteś zgodny z prawem i masz spokój!</strong></p>
                        </div>
                    </div>
                </div>

                <!-- Help -->
                <div class="help-section">
                    <h2>Potrzebujesz pomocy?</h2>
                    <p>
                        Jeśli masz jakiekolwiek pytania lub problemy z konfiguracją wtyczki,
                        skontaktuj się z nami poprzez stronę <a href="https://www.deweloperjawneceny.pl/?utm_source=wordpress&utm_medium=instruction&utm_campaign=promotion" target="_blank">https://www.deweloperjawneceny.pl</a>
                    </p>
                </div>

            </div>
        </div>
        <?php
    }
}
