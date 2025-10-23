# Guida all'Installazione - Mlab Price History

## Requisiti di Sistema

- PrestaShop 1.7.x o superiore (compatibile con PrestaShop 8.x)
- PHP 7.2 o superiore
- MySQL 5.6 o superiore
- Accesso al Back Office con privilegi di amministratore

## Installazione

### Metodo 1: Upload tramite Back Office (Consigliato)

1. **Comprimere il modulo**
   ```bash
   cd modules
   zip -r dolcezampa_price_history.zip dolcezampa_price_history/
   ```

2. **Accedere al Back Office**
   - Andare su: **Moduli > Module Manager**
   - Cliccare su "Carica un modulo" in alto a destra
   - Trascinare il file `dolcezampa_price_history.zip` o selezionarlo
   - Attendere il caricamento

3. **Installare il modulo**
   - Cercare "Mlab Price History" nella lista moduli
   - Cliccare su "Installa"
   - Confermare l'installazione

### Metodo 2: Upload FTP/SFTP

1. **Caricare i file**
   - Connettersi al server via FTP/SFTP
   - Navigare nella cartella `/modules/`
   - Caricare l'intera cartella `dolcezampa_price_history`

2. **Impostare i permessi**
   ```bash
   chmod 755 dolcezampa_price_history
   chmod 644 dolcezampa_price_history/*.php
   ```

3. **Installare dal Back Office**
   - Andare su: **Moduli > Module Manager**
   - Cercare "Mlab Price History"
   - Cliccare su "Installa"

### Metodo 3: Linea di Comando (Avanzato)

```bash
# Dalla root di PrestaShop
cd modules

# Se non è già presente, copiare la cartella del modulo
# cp -r /path/to/dolcezampa_price_history ./

# Impostare permessi
chmod -R 755 dolcezampa_price_history

# Installare via CLI (se disponibile)
php bin/console prestashop:module install dolcezampa_price_history
```

## Verifica Installazione

### 1. Verificare le Tabelle Database

Dopo l'installazione, verificare che le seguenti tabelle siano state create:

```sql
SHOW TABLES LIKE 'ps_dolcezampa_%';
```

Dovrebbero apparire:
- `ps_dolcezampa_price_history`
- `ps_dolcezampa_lowest_price_30d`

### 2. Verificare gli Hook

Nel Back Office:
- Andare su: **Design > Posizioni**
- Cercare "dolcezampa_price_history"
- Verificare che sia agganciato agli hook:
  - `actionProductUpdate`
  - `actionObjectSpecificPriceAddAfter`
  - `actionObjectSpecificPriceUpdateAfter`
  - `actionObjectSpecificPriceDeleteAfter`
  - `displayProductPriceBlock`

### 3. Verificare il Tab Admin

- Andare su: **Catalogo**
- Dovrebbe apparire una nuova voce "Storico Prezzi"
- Cliccandoci si accede alla lista degli storici prezzi

## Configurazione Post-Installazione

### Inizializzare i Dati

Se si sta installando il modulo su un negozio esistente con prodotti già presenti, è consigliabile inizializzare i dati storici:

1. **Creare uno script di inizializzazione**
   Creare il file `/modules/dolcezampa_price_history/init_data.php`:

   ```php
   <?php
   require_once dirname(__FILE__) . '/../../config/config.inc.php';
   
   $module = Module::getInstanceByName('dolcezampa_price_history');
   if (!$module) {
       die('Modulo non trovato');
   }
   
   $products = Product::getProducts(
       (int)Context::getContext()->language->id, 
       0, 
       0, 
       'id_product', 
       'ASC', 
       false, 
       true
   );
   
   foreach ($products as $productData) {
       $product = new Product($productData['id_product']);
       $module->hookActionProductUpdate(['product' => $product]);
   }
   
   echo "Inizializzazione completata! Prodotti processati: " . count($products);
   ?>
   ```

2. **Eseguire lo script**
   ```bash
   php modules/dolcezampa_price_history/init_data.php
   ```

### Integrare nel Template

Per mostrare il prezzo minimo nella pagina prodotto, il modulo si aggancia automaticamente. Se necessario personalizzare:

1. **Copiare il template**
   ```bash
   cp modules/dolcezampa_price_history/views/templates/hook/lowest_price_display.tpl \
      themes/your-theme/modules/dolcezampa_price_history/
   ```

2. **Modificare il template** secondo le proprie esigenze

## Test del Modulo

### 1. Test Cambio Prezzo Manuale

1. Andare su: **Catalogo > Prodotti**
2. Modificare un prodotto
3. Cambiare il prezzo
4. Salvare
5. Verificare in **Catalogo > Storico Prezzi** che il cambio sia stato registrato

### 2. Test Promozione

1. Andare su: **Catalogo > Sconti > Regole carrello**
2. Creare una nuova promozione per un prodotto specifico
3. Salvare
4. Verificare che il cambio sia registrato nello storico
5. Andare sulla pagina del prodotto nel frontend
6. Verificare che venga mostrato il prezzo minimo degli ultimi 30 giorni

### 3. Test AJAX

Aprire la console del browser e testare:

```javascript
fetch('/modules/dolcezampa_price_history/ajax.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'action=getLowestPrice&id_product=1'
})
.then(response => response.json())
.then(data => console.log(data));
```

## Risoluzione Problemi

### Problema: Tabelle non create

**Soluzione:**
```sql
-- Eseguire manualmente le query SQL
source modules/dolcezampa_price_history/sql/install.sql
```

Ricordarsi di sostituire `PREFIX_` con il prefisso del database (solitamente `ps_`).

### Problema: Hook non funzionano

**Soluzione:**
1. Disinstallare il modulo
2. Reinstallare il modulo
3. Svuotare la cache: **Parametri avanzati > Prestazioni > Svuota cache**

### Problema: Tab Admin non appare

**Soluzione:**
```php
// Eseguire questo script nella root di PrestaShop
<?php
require_once 'config/config.inc.php';

$module = Module::getInstanceByName('dolcezampa_price_history');
if ($module) {
    // Rimuovi vecchio tab se esiste
    $idTab = (int)Tab::getIdFromClassName('AdminMlabPriceHistory');
    if ($idTab) {
        $tab = new Tab($idTab);
        $tab->delete();
    }
    
    // Crea nuovo tab
    $tab = new Tab();
    $tab->active = 1;
    $tab->class_name = 'AdminMlabPriceHistory';
    $tab->name = [];
    foreach (Language::getLanguages(true) as $lang) {
        $tab->name[$lang['id_lang']] = 'Storico Prezzi';
    }
    $tab->id_parent = (int)Tab::getIdFromClassName('AdminCatalog');
    $tab->module = 'dolcezampa_price_history';
    $tab->add();
    
    echo "Tab creato con successo!";
}
?>
```

### Problema: Dati non si salvano

**Soluzione:**
1. Verificare i permessi del database
2. Verificare che l'utente MySQL abbia i privilegi INSERT e UPDATE
3. Controllare i log di errore di PrestaShop in `/var/logs/`
4. Attivare la modalità debug in `config/defines.inc.php`:
   ```php
   define('_PS_MODE_DEV_', true);
   ```

## Disinstallazione

### Disinstallazione Standard

1. Andare su: **Moduli > Module Manager**
2. Cercare "Mlab Price History"
3. Cliccare su "Disinstalla"
4. Confermare la disinstallazione

**ATTENZIONE:** Questo eliminerà tutte le tabelle e i dati storici!

### Disinstallazione Manuale

Se necessario rimuovere manualmente:

```sql
-- Eliminare le tabelle
DROP TABLE IF EXISTS ps_dolcezampa_price_history;
DROP TABLE IF EXISTS ps_dolcezampa_lowest_price_30d;

-- Rimuovere il tab
DELETE FROM ps_tab WHERE class_name = 'AdminMlabPriceHistory';
DELETE FROM ps_tab_lang WHERE id_tab NOT IN (SELECT id_tab FROM ps_tab);

-- Rimuovere gli hook
DELETE FROM ps_hook_module WHERE id_module = (
    SELECT id_module FROM ps_module WHERE name = 'dolcezampa_price_history'
);

-- Rimuovere il modulo
DELETE FROM ps_module WHERE name = 'dolcezampa_price_history';
```

Poi rimuovere la cartella via FTP:
```bash
rm -rf modules/dolcezampa_price_history
```

## Backup dei Dati

Prima di aggiornare o disinstallare, è consigliabile fare un backup:

```bash
# Backup delle tabelle
mysqldump -u username -p database_name \
    ps_dolcezampa_price_history \
    ps_dolcezampa_lowest_price_30d \
    > dolcezampa_backup.sql

# Per ripristinare
mysql -u username -p database_name < dolcezampa_backup.sql
```

## Supporto

Per supporto o segnalazione bug:
- Email: support@dolcezampa.it
- Repository: [URL del repository se disponibile]

## Changelog

### Versione 1.0.0
- Prima release
- Tracciamento storico prezzi
- Calcolo prezzo minimo 30 giorni
- Interfaccia admin
- API AJAX
- Helper functions
