# Instrukcje Testowania - WordPress Prices

## Przygotowanie środowiska testowego

### 1. Wymagania
- WordPress 5.0+
- WooCommerce 5.0+
- PHP 7.4+

### 2. Instalacja pluginu
1. Skopiuj folder `wordpress-prices` do `/wp-content/plugins/`
2. Aktywuj plugin w panelu administratora
3. Sprawdź czy pojawił się komunikat o wymaganiu WooCommerce (jeśli WooCommerce nie jest aktywny)

## Scenariusze testowe

### Test 1: Konfiguracja podstawowa

**Kroki:**
1. Przejdź do **WooCommerce > Marże Produktów**
2. Sprawdź czy strona się ładuje poprawnie
3. Dodaj kategorie marży:
   - premium: 30%
   - standard: 20%
   - basic: 10%
4. Zapisz ustawienia
5. Sprawdź czy pojawił się komunikat o zapisaniu

**Oczekiwany rezultat:**
- Strona ustawień ładuje się bez błędów
- Można dodawać/usuwać kategorie marży
- Ustawienia są zapisywane poprawnie

### Test 2: Dodawanie marży do produktu

**Przygotowanie:**
- Utwórz testowy produkt z ceną 120 zł

**Kroki:**
1. Edytuj produkt
2. W sekcji **Dane produktu > Ogólne** znajdź pole **Kategoria Marży**
3. Wybierz "standard (20%)"
4. Sprawdź czy pojawiły się informacje o marży
5. Zapisz produkt

**Oczekiwany rezultat:**
- Pole kategorii marży jest widoczne
- Po wyborze kategorii pokazują się obliczenia:
  - Marża: 20%
  - Cena z marżą: 120 zł
  - Cena bez marży: 100 zł

### Test 3: Wyświetlanie na liście produktów

**Kroki:**
1. Przejdź do **Produkty > Wszystkie produkty**
2. Sprawdź czy pojawia się kolumna **Informacje o Marży**
3. Sprawdź czy produkty z marżą pokazują poprawne informacje
4. Sprawdź czy produkty bez marży pokazują "Brak marży"

**Oczekiwany rezultat:**
- Kolumna marży jest widoczna
- Informacje są poprawnie obliczone i wyświetlane
- Produkty bez marży są odpowiednio oznaczone

### Test 4: Quick Edit (Szybka edycja)

**Kroki:**
1. Na liście produktów kliknij **Szybka edycja** przy produkcie
2. Sprawdź czy pojawia się pole **Kategoria Marży**
3. Zmień kategorię marży
4. Zapisz zmiany
5. Sprawdź czy informacje o marży zostały zaktualizowane

**Oczekiwany rezultat:**
- Pole kategorii marży jest dostępne w Quick Edit
- Zmiany są zapisywane poprawnie
- Lista produktów pokazuje zaktualizowane informacje

### Test 5: Bulk Edit (Edycja masowa)

**Kroki:**
1. Zaznacz kilka produktów na liście
2. Wybierz **Edytuj** z menu akcji masowych
3. Sprawdź czy pojawia się pole **Kategoria Marży**
4. Wybierz kategorię marży
5. Kliknij **Aktualizuj**

**Oczekiwany rezultat:**
- Pole kategorii marży jest dostępne w Bulk Edit
- Wszystkie zaznaczone produkty otrzymują wybraną kategorię marży
- Lista produktów pokazuje zaktualizowane informacje

### Test 6: Produkty zmienne

**Przygotowanie:**
- Utwórz produkt zmienny z kilkoma wariacjami

**Kroki:**
1. Edytuj produkt zmienny
2. Przejdź do zakładki **Wariacje**
3. Rozwiń wariację
4. Sprawdź czy pojawia się pole **Kategoria Marży**
5. Ustaw różne kategorie marży dla różnych wariacji
6. Zapisz produkt

**Oczekiwany rezultat:**
- Każda wariacja może mieć własną kategorię marży
- Informacje o marży są obliczane osobno dla każdej wariacji

### Test 7: Walidacja danych

**Kroki:**
1. W ustawieniach marży spróbuj dodać:
   - Kategorię bez nazwy
   - Marżę bez kategorii
   - Marżę ujemną
   - Marżę powyżej 100%
   - Duplikaty kategorii

**Oczekiwany rezultat:**
- System waliduje dane wejściowe
- Nieprawidłowe dane są odrzucane lub korygowane
- Użytkownik otrzymuje odpowiednie komunikaty

### Test 8: Obliczenia matematyczne

**Dane testowe:**
- Cena produktu: 100 zł, marża: 20%
- Cena produktu: 150 zł, marża: 30%
- Cena produktu: 80 zł, marża: 10%

**Sprawdź obliczenia:**
1. 100 zł z marżą 20% → cena bez marży: 83.33 zł
2. 150 zł z marżą 30% → cena bez marży: 115.38 zł
3. 80 zł z marżą 10% → cena bez marży: 72.73 zł

**Wzór:** `Cena bez marży = Cena z marżą / (1 + marża%/100)`

### Test 9: Responsywność

**Kroki:**
1. Testuj plugin na różnych rozmiarach ekranu
2. Sprawdź czy interfejs jest czytelny na urządzeniach mobilnych
3. Sprawdź czy wszystkie funkcje działają na tabletach

### Test 10: Kompatybilność

**Kroki:**
1. Testuj z różnymi motywami WordPress
2. Sprawdź kompatybilność z popularnymi pluginami WooCommerce
3. Testuj z różnymi wersjami WooCommerce

## Dane testowe

### Przykładowe produkty do testów

```
Produkt 1: "Laptop Premium"
- Cena: 3000 zł
- Kategoria marży: premium (30%)
- Oczekiwana cena bez marży: 2307.69 zł

Produkt 2: "Mysz komputerowa"
- Cena: 60 zł
- Kategoria marży: standard (20%)
- Oczekiwana cena bez marży: 50 zł

Produkt 3: "Kabel USB"
- Cena: 22 zł
- Kategoria marży: basic (10%)
- Oczekiwana cena bez marży: 20 zł

Produkt 4: "Klawiatura"
- Cena: 200 zł
- Bez marży
- Oczekiwane wyświetlanie: "Brak marży"
```

## Raportowanie błędów

Podczas testowania zwróć uwagę na:

1. **Błędy JavaScript** - sprawdź konsolę przeglądarki
2. **Błędy PHP** - sprawdź logi WordPress
3. **Problemy z wydajnością** - sprawdź czas ładowania stron
4. **Problemy z interfejsem** - sprawdź czy wszystkie elementy są widoczne
5. **Błędy obliczeń** - sprawdź czy matematyka jest poprawna

## Checklist testów

- [ ] Instalacja i aktywacja pluginu
- [ ] Konfiguracja kategorii marży
- [ ] Dodawanie marży do produktów prostych
- [ ] Dodawanie marży do produktów zmiennych
- [ ] Wyświetlanie na liście produktów
- [ ] Quick Edit
- [ ] Bulk Edit
- [ ] Walidacja danych
- [ ] Obliczenia matematyczne
- [ ] Responsywność
- [ ] Kompatybilność z motywami
- [ ] Kompatybilność z innymi pluginami
- [ ] Tłumaczenia (jeśli dotyczy)
- [ ] Wydajność przy dużej liczbie produktów
