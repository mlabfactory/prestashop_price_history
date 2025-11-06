{*
* Template for displaying lowest price in last 30 days
* For Omnibus directive compliance
*}

{if isset($lowest_price_data) && $product.has_discount}
    {assign var="lowest" value=$lowest_price_data.lowest_price}
    <div class="mlab-lowest-price-info">
        <div class="lowest-price-content">
                <div class="price-info-tooltip">
                <span class="info-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">
                        <path fill="#333" d="M32 6C17.64 6 6 17.64 6 32s11.64 26 26 26 26-11.64 26-26S46.36 6 32 6Zm0 48.75C19.44 54.75 9.25 44.56 9.25 32S19.44 9.25 32 9.25 54.75 19.44 54.75 32 44.56 54.75 32 54.75Zm.32-31.15c.67 0 1.21-.2 1.64-.6s.64-.97.64-1.72-.21-1.28-.64-1.68-.97-.6-1.64-.6-1.25.2-1.68.6-.64.96-.64 1.68.21 1.32.64 1.72.99.6 1.68.6Zm-.04 23.88c1.25 0 1.88-.57 1.88-1.72V28.48c0-1.15-.63-1.72-1.88-1.72s-1.84.57-1.84 1.72v17.28c0 1.15.61 1.72 1.84 1.72Z"></path>
                    </svg>
                </span>
                <div class="tooltip-content">
                    <h4>Informazioni sul prezzo</h4>
                    <div class="tooltip-section">
                        <p><strong>Prezzo barrato</strong></p>
                        <p>Questo valore indica il prezzo prima dell'applicazione dello sconto o della promozione. La percentuale si riferisce allo sconto applicato sul prezzo barrato.</p>
                    </div>
                    <div class="tooltip-section">
                        <p><strong>Prezzo più basso</strong></p>
                        <p>Questo valore corrisponde al prezzo più basso che ha raggiunto il prodotto sulla nostra piattaforma nei 30 giorni antecedenti all'applicazione dello sconto e tiene conto anche di precedenti promozioni.</p>
                    </div>
                    <div class="tooltip-section">
                        <p>Su Dolce & Zampa, i prezzi vengono stabiliti con assoluta trasparenza, conformemente alle leggi europee e alla cosiddetta Direttiva Omnibus; visita la nostra pagina Condizioni di Vendita per ulteriori dettagli.</p>
                    </div>
                </div>
            </div>
                <strong>Prezzo più basso:</strong>
                <span class="lowest-price-value">{$lowest|string_format:"%.2f"} €</span>
        </div>
    </div>
{/if}