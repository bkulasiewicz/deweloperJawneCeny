# DeweloperJawneCeny

WordPress plugin do automatyzacji procesu dostarczania danych zgodnie z wymogami Ustawy z dnia 21 maja 2025 r. o zmianie ustawy o ochronie praw nabywcy lokalu mieszkalnego.

## Opis

Plugin automatyzuje proces tworzenia i dostarczania danych cenowych zgodnie z nowymi wymogami prawnymi dotyczącymi jawności cen mieszkań przez deweloperów.

## Wersja

Aktualna wersja: **1.11.0**

## Funkcjonalności

- ✅ Zarządzanie danymi deweloperskimi
- ✅ Automatyczne generowanie plików XML zgodnych z wymogami ustawy
- ✅ Eksport danych do formatu CSV
- ✅ Panel administracyjny z intuicyjnym interfejsem
- ✅ System wersjonowania bazy danych
- ✅ Automatyczne publikowanie danych zgodnie z harmonogramem
- ✅ Historia zmian cen mieszkań

## Wymagania

- WordPress 5.0 lub nowszy
- PHP 7.4 lub nowszy
- MySQL 5.6 lub nowszy

## Instalacja

1. Pobierz plugin jako plik ZIP
2. Przejdź do panelu administracyjnego WordPress
3. Wybierz **Wtyczki** → **Dodaj nową** → **Wyślij wtyczkę na serwer**
4. Wybierz plik ZIP i kliknij **Zainstaluj**
5. Aktywuj plugin

## Struktura projektu

```
/
├── assets/                 # Pliki CSS i JavaScript
├── includes/              # Główny kod PHP
│   ├── UseCases/         # Przypadki użycia
│   ├── controllers/      # Kontrolery
│   ├── core/            # Podstawowe klasy systemu
│   ├── models/          # Modele danych
│   ├── repositories/    # Repozytoria danych
│   ├── services/        # Usługi pomocnicze
│   └── views/           # Widoki (admin i frontend)
├── materialydlaAI/       # Dokumentacja i przykłady
├── uninstall.php        # Skrypt deinstalacji
└── ustawa-jawnosci-cen.php # Główny plik plugin
```

## Komponenty

### Kontrolery
- `UJC_CSV_Generator` - Generowanie plików CSV
- `UJC_XML_Generator` - Generowanie plików XML
- `UJC_Automated_Generator` - Automatyczne publikowanie

### Repozytoria
- `UJC_Developer_Repository` - Zarządzanie danymi deweloperów
- `UJC_Investment_Repository` - Zarządzanie inwestycjami
- `UJC_Resource_Repository` - Zarządzanie zasobami
- `UJC_Price_History_Repository` - Historia cen

### Usługi
- `UJC_Date_Helper` - Pomocnik do dat
- `UJC_Resource_Importer` - Import zasobów
- `UJC_Schema_Manager` - Zarządzanie schematem bazy danych

## Konfiguracja

Po aktywacji plugin automatycznie:
- Tworzy niezbędne tabele w bazie danych
- Tworzy katalog `/wp-content/uploads/ujc-data/` do przechowywania plików
- Dodaje menu w panelu administracyjnym

## Autor

Deweloper

## Licencja

Plugin stworzony zgodnie z wymogami polskiego prawa dotyczącego jawności cen mieszkań.