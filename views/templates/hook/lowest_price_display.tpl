{*
* Template for displaying lowest price in last 30 days
* For Omnibus directive compliance
*}

{block name='product_lowestprice'}
{if isset($lowest_price_data) && $lowest_price_data}
    {assign var="current" value=$lowest_price_data.current_price}
    {assign var="lowest" value=$lowest_price_data.lowest_price}
    {assign var="lowest_date" value=$lowest_price_data.lowest_price_date}
    
    {if $current != $lowest}
        <div class="dolcezampa-lowest-price-info" style="margin: 10px 0; padding: 10px; background-color: #f8f9fa; border-left: 3px solid #007bff;">
            <div style="font-size: 0.9em; color: #495057;">
                <i class="material-icons" style="font-size: 16px; vertical-align: middle;">info</i>
                <strong>Prezzo più basso negli ultimi 30 giorni:</strong>
                <span style="font-weight: bold; color: #007bff;">{$lowest|string_format:"%.2f"} €</span>
                <span style="font-size: 0.85em; color: #6c757d;">
                    (il {$lowest_date|date_format:"%d/%m/%Y"})
                </span>
            </div>
        </div>
    {/if}
{/if}
{/block}