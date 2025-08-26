# Instrukcja wdrożenia strony na Netlify + OVH

## KROK 1: Utwórz konto Netlify
1. Idź na https://netlify.com
2. Kliknij "Sign up" 
3. Zarejestruj się przez email lub GitHub

## KROK 2: Wdróż stronę na Netlify
1. Po zalogowaniu kliknij "Deploy manually"
2. Przeciągnij folder `website` do okna przeglądarki
3. Poczekaj na wgranie (1-2 minuty)
4. Netlify da Ci tymczasowy adres typu: `https://random-name-123.netlify.app`

## KROK 3: Skonfiguruj domenę w OVH
1. Zaloguj się do panelu OVH: https://ovh.com/auth
2. Idź do "Domeny" → Twoja domena → "Strefa DNS"
3. **USUŃ wszystkie istniejące rekordy A i CNAME**
4. **DODAJ nowe rekordy:**
   - Typ: `CNAME`, Subdomena: `www`, Cel: `random-name-123.netlify.app`
   - Typ: `CNAME`, Subdomena: `@` (lub puste), Cel: `random-name-123.netlify.app`

## KROK 4: Podłącz domenę w Netlify
1. W Netlify idź do "Site settings" → "Domain management"
2. Kliknij "Add custom domain"
3. Wpisz swoją domenę (np. `twoja-domena.pl`)
4. Netlify sprawdzi DNS (może zająć do 24h)

## KROK 5: Włącz HTTPS
- Netlify automatycznie włączy HTTPS (Let's Encrypt)
- Może zająć 1-2 godziny

## Ważne numery telefonu wsparcia:
- OVH Polska: 71 750 02 00
- W razie problemów z DNS: sprawdź na https://whatsmydns.net

## Testowanie
Po 2-24h sprawdź:
- `http://twoja-domena.pl`  
- `https://twoja-domena.pl`
- `https://www.twoja-domena.pl`

Wszystko powinno działać i przekierowywać na HTTPS.