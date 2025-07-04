# WordPress Prices - Margin Manager

Plugin WordPress/WooCommerce do zarządzania marżami produktów z możliwością ustawiania marży dla produktów z określonym meta.

## Funkcjonalności

- ✅ Dodawanie meta pól do produktów dla kategoryzacji marży
- ✅ Ustawianie procentowych marży dla różnych kategorii produktów
- ✅ Wyświetlanie informacji o marży na liście produktów w panelu administratora
- ✅ **Wyświetlanie sumarycznej marży na liście zamówień w panelu administratora**
- ✅ **Wyświetlanie szczegółowych informacji o marży w szczegółach zamówienia**
- ✅ Obliczanie ceny bez marży na podstawie ceny z marżą
- ✅ Obsługa szybkiej edycji (Quick Edit) i edycji masowej (Bulk Edit)
- ✅ Panel ustawień w sekcji WooCommerce
- ✅ Obsługa produktów zmiennych (variations)
- ✅ Obliczanie średniej marży procentowej dla zamówień

## Wymagania

- WordPress 5.0+
- WooCommerce 5.0+
- PHP 7.4+

## Instalacja

1. Skopiuj folder `wordpress-prices` do katalogu `/wp-content/plugins/`
2. Aktywuj plugin w panelu administratora WordPress
3. Przejdź do **WooCommerce > Marże Produktów** aby skonfigurować ustawienia
4. Skonfiguruj opcje wyświetlania dla listy produktów i zamówień

## Konfiguracja

### 1. Ustawienia podstawowe

W panelu **WooCommerce > Marże Produktów** możesz:

- **Klucz Meta Pola**: Ustaw nazwę meta pola używanego do identyfikacji kategorii marży (domyślnie: `margin_category`)
- **Marże dla Kategorii**: Dodaj kategorie marży z odpowiednimi procentami
- **Opcje Wyświetlania na liście produktów**: Wybierz co ma być wyświetlane na liście produktów
- **Opcje Wyświetlania na liście zamówień**: Konfiguruj wyświetlanie marży na liście zamówień:
  - Pokaż kolumnę marży zamówień
  - Pokaż średnią marżę procentową
  - Pokaż liczbę produktów z marżą

### 2. Przykładowa konfiguracja marży

```
Kategoria: premium | Marża: 30%
Kategoria: standard | Marża: 20%
Kategoria: basic | Marża: 10%
```

### 3. Przypisywanie marży do produktów

1. Edytuj produkt w panelu administratora
2. W sekcji **Dane produktu > Ogólne** znajdź pole **Kategoria Marży**
3. Wybierz odpowiednią kategorię marży
4. Zapisz produkt

## Jak to działa

### Obliczanie marży

Plugin oblicza cenę bez marży według wzoru:

```
Cena bez marży = Cena z marżą / (1 + marża%)
```

**Przykład:**

- Cena produktu: 120 zł
- Marża: 20%
- Cena bez marży: 120 / (1 + 0.20) = 100 zł
- Kwota marży: 120 - 100 = 20 zł

### Wyświetlanie na liście produktów

Na liście produktów w panelu administratora pojawi się nowa kolumna **Informacje o Marży** zawierająca:

- Nazwę kategorii marży
- Procentową marżę
- Cenę bez marży
- Kwotę marży

### Wyświetlanie na liście zamówień

Na liście zamówień w panelu administratora pojawi się nowa kolumna **Marża Zamówienia** zawierająca:

- **Łączną kwotę marży** z wszystkich produktów w zamówieniu
- **Średnią marżę procentową** (opcjonalnie)
- **Liczbę produktów z marżą** (opcjonalnie)

Kolumna automatycznie sumuje marże ze wszystkich produktów w zamówieniu, uwzględniając:

- Ilości produktów
- Produkty zmienne (variations)
- Różne kategorie marży w jednym zamówieniu

### Wyświetlanie w szczegółach zamówienia

W szczegółach zamówienia w panelu administratora pojawi się nowa sekcja **Informacje o marży zamówienia** zawierająca:

- **Łączną marżę zamówienia** - suma wszystkich marż z produktów
- **Średnią marżę procentową** - średnia ważona marża dla całego zamówienia
- **Pokrycie marży** - procent produktów w zamówieniu, które mają ustawioną marżę
- **Szczegółową tabelę marży produktów** - pokazuje marżę dla każdego produktu osobno

Dodatkowo w tabeli produktów zamówienia można włączyć **kolumnę marży**, która pokazuje:

- Procentową marżę produktu
- Łączną kwotę marży za dany produkt (uwzględniając ilość)

## Funkcje zaawansowane

### Quick Edit (Szybka edycja)

Możesz szybko zmieniać kategorię marży produktu używając funkcji Quick Edit na liście produktów.

### Bulk Edit (Edycja masowa)

Wybierz wiele produktów i zmień ich kategorię marży jednocześnie używając funkcji Bulk Edit.

### Produkty zmienne

Plugin obsługuje również produkty zmienne - możesz ustawić różne kategorie marży dla różnych wariacji produktu.

## Struktura plików

```
wordpress-prices/
├── wordpress-prices.php              # Główny plik pluginu
├── includes/
│   ├── class-wp-prices-admin.php     # Panel administratora
│   ├── class-wp-prices-meta-fields.php # Obsługa meta pól
│   ├── class-wp-prices-margin-calculator.php # Obliczenia marży
│   ├── class-wp-prices-product-list.php # Wyświetlanie na liście produktów
│   ├── class-wp-prices-order-list.php # Wyświetlanie na liście zamówień
│   └── class-wp-prices-order-details.php # Wyświetlanie w szczegółach zamówienia
├── assets/
│   ├── css/
│   │   ├── admin.css                 # Style panelu admin
│   │   ├── order-list.css            # Style listy zamówień
│   │   └── order-details.css         # Style szczegółów zamówienia
│   └── js/
│       ├── admin.js                  # JavaScript panelu admin
│       └── product-list.js           # JavaScript listy produktów
└── README.md                         # Ten plik
```

## Hooks i filtry

Plugin udostępnia następujące hooks dla deweloperów:

### Filtry

- `wp_prices_margin_info` - Modyfikacja informacji o marży
- `wp_prices_display_format` - Zmiana formatu wyświetlania marży
- `wp_prices_calculation_method` - Zmiana metody obliczania marży

### Akcje

- `wp_prices_margin_updated` - Wywoływana po aktualizacji marży produktu
- `wp_prices_settings_saved` - Wywoływana po zapisaniu ustawień

## Rozwiązywanie problemów

### Plugin nie wyświetla się w menu

Sprawdź czy WooCommerce jest aktywny. Plugin wymaga aktywnego WooCommerce.

### Marże nie są obliczane

1. Sprawdź czy produkt ma ustawioną cenę
2. Sprawdź czy produkt ma przypisaną kategorię marży
3. Sprawdź czy kategoria marży istnieje w ustawieniach

### Kolumna marży nie pojawia się na liście

Sprawdź czy jesteś na stronie listy produktów WooCommerce i czy masz odpowiednie uprawnienia.

## Wsparcie

W przypadku problemów lub pytań, sprawdź:

1. Czy wszystkie wymagania są spełnione
2. Czy nie ma konfliktów z innymi pluginami
3. Logi błędów WordPress

## Licencja

GPL v2 or later

## Changelog

### 1.0.0

- Pierwsza wersja pluginu
- Podstawowe funkcjonalności zarządzania marżami
- Panel administratora
- Wyświetlanie na liście produktów
- Obsługa Quick Edit i Bulk Edit
