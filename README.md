# Mlab Price History Module

## Descrizione

Modulo PrestaShop per tracciare lo storico dei cambi di prezzo quando vengono attivate le promozioni e mantenere il prezzo più basso degli ultimi 30 giorni.

## Funzionalità

### 1. Storico Prezzi
Il modulo salva automaticamente ogni variazione di prezzo in una tabella dedicata (`ps_dolcezampa_price_history`) che include:
- ID prodotto e combinazione
- Prezzo precedente e nuovo prezzo
- Tipo di prezzo (regular, sale, specific_price)
- Tipo e valore della riduzione (importo fisso o percentuale)
- Data e ora del cambio

### 2. Prezzo Minimo 30 Giorni
Mantiene una tabella separata (`ps_dolcezampa_lowest_price_30d`) con:
- Prezzo più basso registrato negli ultimi 30 giorni
- Data in cui è stato registrato il prezzo minimo
- Prezzo corrente del prodotto
- Aggiornamento automatico ad ogni cambio prezzo

## Installazione

1. Caricare la cartella `dolcezampa_price_history` nella directory `/modules/` di PrestaShop
2. Andare nel Back Office > Moduli > Module Manager
3. Cercare "Mlab Price History"
4. Cliccare su "Installa"

Durante l'installazione verranno create automaticamente le tabelle necessarie nel database.

## Struttura Database

### Tabella: ps_dolcezampa_price_history
```sql
- id_price_history (INT) - ID univoco
- id_product (INT) - ID del prodotto
- id_product_attribute (INT) - ID della combinazione (0 se prodotto semplice)
- id_shop (INT) - ID del negozio
- old_price (DECIMAL) - Prezzo precedente
- new_price (DECIMAL) - Nuovo prezzo
- price_type (VARCHAR) - Tipo: regular, sale, specific_price
- reduction_type (VARCHAR) - Tipo riduzione: amount, percentage
- reduction_value (DECIMAL) - Valore della riduzione
- date_add (DATETIME) - Data del cambio
```

### Tabella: ps_dolcezampa_lowest_price_30d
```sql
- id_lowest_price (INT) - ID univoco
- id_product (INT) - ID del prodotto
- id_product_attribute (INT) - ID della combinazione
- id_shop (INT) - ID del negozio
- lowest_price (DECIMAL) - Prezzo più basso
- lowest_price_date (DATETIME) - Data del prezzo più basso
- current_price (DECIMAL) - Prezzo corrente
- date_upd (DATETIME) - Ultimo aggiornamento
```

## Hook Utilizzati

Il modulo si aggancia automaticamente ai seguenti hook:
- `actionProductUpdate` - Quando un prodotto viene aggiornato
- `actionObjectSpecificPriceAddAfter` - Quando viene aggiunta una promozione
- `actionObjectSpecificPriceUpdateAfter` - Quando viene modificata una promozione
- `actionObjectSpecificPriceDeleteAfter` - Quando viene eliminata una promozione

## Utilizzo Programmatico

### Ottenere lo storico prezzi di un prodotto
```php
$module = Module::getInstanceByName('dolcezampa_price_history');
$history = $module->getPriceHistory($idProduct, $idProductAttribute, 50);

foreach ($history as $entry) {
    echo "Da " . $entry['old_price'] . "€ a " . $entry['new_price'] . "€ il " . $entry['date_add'];
}
```

### Ottenere il prezzo più basso degli ultimi 30 giorni
```php
$module = Module::getInstanceByName('dolcezampa_price_history');
$lowestPrice = $module->getLowestPrice30d($idProduct, $idProductAttribute);

if ($lowestPrice) {
    echo "Prezzo più basso: " . $lowestPrice['lowest_price'] . "€";
    echo "Registrato il: " . $lowestPrice['lowest_price_date'];
}
```

## Compatibilità

- PrestaShop 1.7.x e superiori
- PrestaShop 8.x
- PHP 7.2+

## Conformità Normativa

Questo modulo aiuta a rispettare le normative europee sulla trasparenza dei prezzi (Direttiva Omnibus) che richiedono di mostrare il prezzo più basso praticato negli ultimi 30 giorni quando si applica una riduzione di prezzo.

## Autore

Mlab

## Versione

1.0.0

## Licenza

Proprietaria
