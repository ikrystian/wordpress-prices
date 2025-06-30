# WordPress Prices - Developer API

## Przegląd

Plugin WordPress Prices udostępnia API dla deweloperów do programowego zarządzania marżami produktów.

## Główne klasy

### WP_Prices_Meta_Fields

Klasa odpowiedzialna za obsługę meta pól produktów.

#### Metody statyczne

```php
// Pobierz kategorię marży produktu
$category = WP_Prices_Meta_Fields::get_product_margin_category($product_id);

// Pobierz procentową marżę produktu
$margin_percentage = WP_Prices_Meta_Fields::get_product_margin_percentage($product_id);

// Sprawdź czy produkt ma ustawioną marżę
$has_margin = WP_Prices_Meta_Fields::product_has_margin($product_id);
```

### WP_Prices_Margin_Calculator

Klasa do obliczania marży i cen.

#### Metody statyczne

```php
// Oblicz cenę bez marży
$price_without_margin = WP_Prices_Margin_Calculator::calculate_price_without_margin($price_with_margin, $margin_percentage);

// Oblicz cenę z marżą
$price_with_margin = WP_Prices_Margin_Calculator::calculate_price_with_margin($price_without_margin, $margin_percentage);

// Pobierz pełne informacje o marży produktu
$margin_info = WP_Prices_Margin_Calculator::get_product_margin_info($product_id);

// Sformatuj informacje o marży do wyświetlenia
$formatted_info = WP_Prices_Margin_Calculator::format_margin_info($product_id, 'inline');

// Pobierz informacje o marży jako tablicę
$margin_array = WP_Prices_Margin_Calculator::get_margin_info_array($product_id);

// Oblicz marże dla wielu produktów
$bulk_margins = WP_Prices_Margin_Calculator::calculate_bulk_margins($product_ids);

// Pobierz całkowitą marżę zamówienia
$order_margin = WP_Prices_Margin_Calculator::get_order_total_margin($order_id);

// Pobierz szczegółowe informacje o marży zamówienia
$order_details = WP_Prices_Margin_Calculator::get_order_margin_details($order_id);

// Pobierz podsumowanie marży zamówienia (dla API/AJAX)
$order_summary = WP_Prices_Order_List::get_order_margin_summary($order_id);
```

## Przykłady użycia

### 1. Wyświetlanie informacji o marży w szablonie

```php
// W pliku szablonu (np. single-product.php)
$product_id = get_the_ID();
$margin_info = WP_Prices_Margin_Calculator::get_product_margin_info($product_id);

if ($margin_info) {
    echo '<div class="product-margin-info">';
    echo '<p>Marża: ' . $margin_info['margin_percentage'] . '%</p>';
    echo '<p>Cena bez marży: ' . wc_price($margin_info['price_without_margin']) . '</p>';
    echo '</div>';
}
```

### 2. Programowe ustawianie marży produktu

```php
// Ustaw kategorię marży dla produktu
$product_id = 123;
$meta_key = get_option('wp_prices_meta_key', 'margin_category');
update_post_meta($product_id, $meta_key, 'premium');
```

### 3. Pobieranie produktów z określoną marżą

```php
function get_products_by_margin_category($category) {
    $meta_key = get_option('wp_prices_meta_key', 'margin_category');

    $args = array(
        'post_type' => 'product',
        'meta_query' => array(
            array(
                'key' => $meta_key,
                'value' => $category,
                'compare' => '='
            )
        )
    );

    return get_posts($args);
}

// Użycie
$premium_products = get_products_by_margin_category('premium');
```

### 4. Obliczanie całkowitej marży koszyka

```php
function calculate_cart_total_margin() {
    $total_margin = 0;

    foreach (WC()->cart->get_cart() as $cart_item) {
        $product_id = $cart_item['product_id'];
        $quantity = $cart_item['quantity'];

        $margin_info = WP_Prices_Margin_Calculator::get_product_margin_info($product_id);

        if ($margin_info) {
            $total_margin += $margin_info['margin_amount'] * $quantity;
        }
    }

    return $total_margin;
}
```

### 5. Wyświetlanie informacji o marży zamówienia

```php
// Pobierz szczegółowe informacje o marży zamówienia
$order_id = 123;
$margin_details = WP_Prices_Margin_Calculator::get_order_margin_details($order_id);

if ($margin_details) {
    echo '<div class="order-margin-info">';
    echo '<h3>Informacje o marży zamówienia</h3>';
    echo '<p>Łączna marża: ' . wc_price($margin_details['total_margin']) . '</p>';
    echo '<p>Średnia marża: ' . number_format($margin_details['average_margin_percentage'], 1) . '%</p>';
    echo '<p>Produktów z marżą: ' . $margin_details['products_with_margin'] . ' z ' . $margin_details['total_products'] . '</p>';

    // Szczegóły produktów
    echo '<h4>Szczegóły produktów:</h4>';
    foreach ($margin_details['details'] as $detail) {
        echo '<p>' . $detail['product_name'] . ' (x' . $detail['quantity'] . '): ' . wc_price($detail['total_margin']) . '</p>';
    }
    echo '</div>';
}
```

### 6. Sprawdzanie czy zamówienie ma produkty z marżą

```php
function order_has_margin($order_id) {
    $total_margin = WP_Prices_Margin_Calculator::get_order_total_margin($order_id);
    return $total_margin > 0;
}

// Użycie
if (order_has_margin($order_id)) {
    echo 'To zamówienie zawiera produkty z marżą';
}
```

### 5. Hook do modyfikacji wyświetlania ceny

```php
// Dodaj informację o marży do ceny produktu
add_filter('woocommerce_get_price_html', 'add_margin_info_to_price', 10, 2);

function add_margin_info_to_price($price_html, $product) {
    $margin_info = WP_Prices_Margin_Calculator::get_product_margin_info($product->get_id());

    if ($margin_info) {
        $margin_text = sprintf(
            '<small class="margin-info">(Marża: %s%% | Bez marży: %s)</small>',
            $margin_info['margin_percentage'],
            wc_price($margin_info['price_without_margin'])
        );

        $price_html .= '<br>' . $margin_text;
    }

    return $price_html;
}
```

## Hooks i filtry

### Dostępne filtry

#### wp_prices_margin_info

Modyfikuje informacje o marży przed wyświetleniem.

```php
add_filter('wp_prices_margin_info', 'custom_margin_info', 10, 2);

function custom_margin_info($margin_info, $product_id) {
    // Dodaj dodatkowe informacje
    $margin_info['custom_field'] = 'custom_value';

    return $margin_info;
}
```

#### wp_prices_display_format

Zmienia format wyświetlania marży.

```php
add_filter('wp_prices_display_format', 'custom_display_format', 10, 3);

function custom_display_format($formatted_info, $margin_info, $format) {
    if ($format === 'custom') {
        return sprintf(
            'Marża %s%% = %s',
            $margin_info['margin_percentage'],
            wc_price($margin_info['margin_amount'])
        );
    }

    return $formatted_info;
}
```

#### wp_prices_calculation_method

Zmienia metodę obliczania marży.

```php
add_filter('wp_prices_calculation_method', 'custom_calculation_method', 10, 3);

function custom_calculation_method($price_without_margin, $price_with_margin, $margin_percentage) {
    // Własna logika obliczania
    return $price_with_margin * 0.8; // Przykład
}
```

### Dostępne akcje

#### wp_prices_margin_updated

Wywoływana po aktualizacji marży produktu.

```php
add_action('wp_prices_margin_updated', 'on_margin_updated', 10, 3);

function on_margin_updated($product_id, $old_category, $new_category) {
    // Logika po zmianie marży
    error_log("Marża produktu {$product_id} zmieniona z {$old_category} na {$new_category}");
}
```

#### wp_prices_settings_saved

Wywoływana po zapisaniu ustawień pluginu.

```php
add_action('wp_prices_settings_saved', 'on_settings_saved', 10, 1);

function on_settings_saved($settings) {
    // Logika po zapisaniu ustawień
    do_action('custom_cache_clear');
}
```

## REST API

### Endpoint: /wp-json/wp-prices/v1/margin/{product_id}

Pobiera informacje o marży produktu.

```javascript
// JavaScript
fetch("/wp-json/wp-prices/v1/margin/123")
  .then((response) => response.json())
  .then((data) => {
    console.log("Margin info:", data);
  });
```

### Endpoint: /wp-json/wp-prices/v1/bulk-margin

Pobiera informacje o marży dla wielu produktów.

```javascript
// JavaScript
fetch("/wp-json/wp-prices/v1/bulk-margin", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
  },
  body: JSON.stringify({
    product_ids: [123, 456, 789],
  }),
})
  .then((response) => response.json())
  .then((data) => {
    console.log("Bulk margin info:", data);
  });
```

## Baza danych

### Meta pola produktów

Plugin używa meta pól produktów do przechowywania kategorii marży:

```sql
-- Struktura meta pola
meta_key: 'margin_category' (domyślnie, konfigurowalne)
meta_value: 'premium' | 'standard' | 'basic' | ... (konfigurowalne)
```

### Opcje WordPress

Plugin przechowuje ustawienia w opcjach WordPress:

```php
// Marże dla kategorii
get_option('wp_prices_margins'); // array('premium' => 30, 'standard' => 20, ...)

// Klucz meta pola
get_option('wp_prices_meta_key'); // 'margin_category'

// Opcje wyświetlania
get_option('wp_prices_display_options'); // array('show_margin_percentage' => 1, ...)
```

## Rozszerzanie funkcjonalności

### Dodawanie nowych typów marży

```php
// Dodaj nowy typ marży
add_filter('wp_prices_margin_types', 'add_custom_margin_types');

function add_custom_margin_types($types) {
    $types['vip'] = array(
        'label' => 'VIP',
        'percentage' => 50,
        'description' => 'Marża dla klientów VIP'
    );

    return $types;
}
```

### Integracja z innymi pluginami

```php
// Integracja z pluginem członkostwa
add_filter('wp_prices_margin_percentage', 'adjust_margin_for_members', 10, 2);

function adjust_margin_for_members($margin_percentage, $product_id) {
    if (is_user_logged_in() && user_has_membership()) {
        return $margin_percentage * 0.8; // 20% zniżki dla członków
    }

    return $margin_percentage;
}
```

## Debugowanie

### Włączanie logów

```php
// W wp-config.php
define('WP_PRICES_DEBUG', true);

// Logi będą zapisywane w /wp-content/debug.log
```

### Sprawdzanie obliczeń

```php
// Funkcja pomocnicza do debugowania
function debug_margin_calculation($product_id) {
    $margin_info = WP_Prices_Margin_Calculator::get_product_margin_info($product_id);

    error_log('Margin debug for product ' . $product_id . ': ' . print_r($margin_info, true));

    return $margin_info;
}
```
