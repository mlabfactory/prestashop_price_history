# Esempi di Utilizzo - Mlab Price History

## 1. Visualizzare lo Storico Prezzi di un Prodotto

### Nel Controller
```php
// Nel tuo controller PrestaShop
$idProduct = 123; // ID del prodotto
$idProductAttribute = 0; // 0 per prodotto semplice, altrimenti ID combinazione

$module = Module::getInstanceByName('dolcezampa_price_history');
if ($module && $module->active) {
    $priceHistory = $module->getPriceHistory($idProduct, $idProductAttribute, 20);
    
    // Assegna a Smarty
    $this->context->smarty->assign([
        'price_history' => $priceHistory
    ]);
}
```

### Nel Template Smarty
```smarty
{if isset($price_history) && $price_history}
    <div class="price-history-section">
        <h3>Storico Prezzi</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Prezzo Precedente</th>
                    <th>Nuovo Prezzo</th>
                    <th>Tipo</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$price_history item=entry}
                    <tr>
                        <td>{$entry.date_add|date_format:"%d/%m/%Y %H:%M"}</td>
                        <td>{$entry.old_price|string_format:"%.2f"} €</td>
                        <td>{$entry.new_price|string_format:"%.2f"} €</td>
                        <td>{$entry.price_type}</td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    </div>
{/if}
```

## 2. Mostrare il Prezzo Minimo degli Ultimi 30 Giorni

### Metodo 1: Usando le Helper Functions
```php
// Include il file helper
require_once(_PS_MODULE_DIR_ . 'dolcezampa_price_history/helpers.php');

// Ottieni i dati del prezzo minimo
$idProduct = 123;
$lowestPriceData = dolcezampa_get_lowest_price_30d($idProduct);

// Mostra il badge del prezzo minimo
echo dolcezampa_display_lowest_price_badge($idProduct);

// Verifica se è al minimo storico
if (dolcezampa_is_at_historical_low($idProduct)) {
    echo '<span class="badge badge-success">Prezzo al minimo storico!</span>';
}
```

### Metodo 2: Direttamente dal Modulo
```php
$module = Module::getInstanceByName('dolcezampa_price_history');
if ($module && $module->active) {
    $lowestPrice = $module->getLowestPrice30d($idProduct, $idProductAttribute);
    
    if ($lowestPrice) {
        echo '<div class="lowest-price-info">';
        echo 'Prezzo più basso negli ultimi 30 giorni: ';
        echo '<strong>' . number_format($lowestPrice['lowest_price'], 2) . ' €</strong>';
        echo ' (il ' . date('d/m/Y', strtotime($lowestPrice['lowest_price_date'])) . ')';
        echo '</div>';
    }
}
```

## 3. Conformità Direttiva Omnibus

### Visualizzazione Automatica nella Pagina Prodotto
Il modulo si aggancia automaticamente all'hook `displayProductPriceBlock` per mostrare il prezzo minimo degli ultimi 30 giorni quando applicabile.

### Override Template Product.tpl
```smarty
{* Nel tuo tema, in themes/your-theme/templates/catalog/product.tpl *}

<div class="product-prices">
    {if $product.has_discount}
        <div class="product-discount">
            <span class="regular-price">{$product.regular_price}</span>
            <span class="discount-percentage">{$product.discount_percentage}</span>
        </div>
    {/if}
    
    <div class="current-price">
        <span itemprop="price" content="{$product.price_amount}">
            {$product.price}
        </span>
    </div>
    
    {* Mostra il prezzo minimo degli ultimi 30 giorni *}
    {hook h='displayProductPriceBlock' product=$product type='weight'}
</div>
```

## 4. Interrogare il Database Direttamente

### Query Esempio: Storico Prezzi
```sql
-- Ottieni gli ultimi 10 cambi di prezzo per un prodotto
SELECT 
    ph.*,
    pl.name as product_name
FROM ps_dolcezampa_price_history ph
LEFT JOIN ps_product_lang pl ON ph.id_product = pl.id_product
WHERE ph.id_product = 123
AND pl.id_lang = 1
ORDER BY ph.date_add DESC
LIMIT 10;
```

### Query Esempio: Prodotti con Sconto Maggiore del Minimo
```sql
-- Trova prodotti il cui prezzo attuale è superiore al minimo degli ultimi 30 giorni
SELECT 
    lp.*,
    pl.name as product_name,
    (lp.current_price - lp.lowest_price) as price_difference,
    ROUND(((lp.current_price - lp.lowest_price) / lp.lowest_price * 100), 2) as percentage_diff
FROM ps_dolcezampa_lowest_price_30d lp
LEFT JOIN ps_product_lang pl ON lp.id_product = pl.id_product
WHERE lp.current_price > lp.lowest_price
AND pl.id_lang = 1
ORDER BY price_difference DESC;
```

## 5. Integrare in un Widget Personalizzato

```php
<?php
/**
 * Widget per mostrare prodotti al prezzo minimo storico
 */
class LowestPriceWidget extends Module
{
    public function getProducts()
    {
        $sql = 'SELECT lp.*, pl.name, p.id_product
                FROM ' . _DB_PREFIX_ . 'dolcezampa_lowest_price_30d lp
                INNER JOIN ' . _DB_PREFIX_ . 'product p ON lp.id_product = p.id_product
                LEFT JOIN ' . _DB_PREFIX_ . 'product_lang pl 
                    ON (lp.id_product = pl.id_product AND pl.id_lang = ' . (int)$this->context->language->id . ')
                WHERE ABS(lp.current_price - lp.lowest_price) < 0.01
                AND p.active = 1
                ORDER BY lp.date_upd DESC
                LIMIT 10';
        
        return Db::getInstance()->executeS($sql);
    }
    
    public function renderWidget()
    {
        $products = $this->getProducts();
        
        $this->context->smarty->assign([
            'lowest_price_products' => $products
        ]);
        
        return $this->fetch('module:yourmodule/views/templates/widget.tpl');
    }
}
?>
```

## 6. Utilizzare in un Export CSV

```php
<?php
/**
 * Esporta lo storico prezzi in CSV
 */
function exportPriceHistoryToCsv($idProduct)
{
    $module = Module::getInstanceByName('dolcezampa_price_history');
    if (!$module) {
        return false;
    }
    
    $history = $module->getPriceHistory($idProduct, 0, 1000);
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=price_history_' . $idProduct . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // Intestazioni
    fputcsv($output, ['Data', 'Prezzo Precedente', 'Nuovo Prezzo', 'Tipo', 'Tipo Riduzione', 'Valore Riduzione']);
    
    // Dati
    foreach ($history as $entry) {
        fputcsv($output, [
            $entry['date_add'],
            $entry['old_price'],
            $entry['new_price'],
            $entry['price_type'],
            $entry['reduction_type'],
            $entry['reduction_value']
        ]);
    }
    
    fclose($output);
    exit;
}
?>
```

## 7. AJAX Request per Ottenere Dati Real-Time

### JavaScript
```javascript
// Richiesta AJAX per ottenere il prezzo minimo
function getLowestPrice(productId) {
    $.ajax({
        url: '/modules/dolcezampa_price_history/ajax.php',
        method: 'POST',
        data: {
            action: 'getLowestPrice',
            id_product: productId
        },
        success: function(response) {
            if (response.success) {
                $('#lowest-price-display').html(
                    'Prezzo minimo 30gg: <strong>' + 
                    response.data.lowest_price + ' €</strong>'
                );
            }
        }
    });
}
```

### ajax.php (da creare nel modulo)
```php
<?php
require_once(dirname(__FILE__) . '/../../config/config.inc.php');
require_once(dirname(__FILE__) . '/dolcezampa_price_history.php');

if (Tools::getValue('action') == 'getLowestPrice') {
    $idProduct = (int)Tools::getValue('id_product');
    $idProductAttribute = (int)Tools::getValue('id_product_attribute', 0);
    
    $module = new Dolcezampa_Price_History();
    $data = $module->getLowestPrice30d($idProduct, $idProductAttribute);
    
    if ($data) {
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Dati non trovati'
        ]);
    }
}
exit;
?>
```

## 8. Trigger Manuale per Aggiornare i Prezzi

```php
<?php
/**
 * Script per aggiornare manualmente lo storico prezzi di tutti i prodotti
 * Utile per la prima installazione o manutenzione
 */
require_once(dirname(__FILE__) . '/../../../config/config.inc.php');

$module = Module::getInstanceByName('dolcezampa_price_history');
if (!$module) {
    die('Modulo non trovato');
}

// Ottieni tutti i prodotti attivi
$products = Product::getProducts((int)Context::getContext()->language->id, 0, 0, 'id_product', 'ASC', false, true);

foreach ($products as $productData) {
    $product = new Product($productData['id_product']);
    
    // Simula l'update del prodotto per triggerare l'hook
    $module->hookActionProductUpdate(['product' => $product]);
    
    echo "Aggiornato prodotto ID: " . $product->id . " - " . $product->name . "\n";
}

echo "Completato!\n";
?>
```
