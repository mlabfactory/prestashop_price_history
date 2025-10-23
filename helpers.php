<?php
/**
 * Helper functions for Mlab Price History Module
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Get price history for a product
 * 
 * @param int $idProduct Product ID
 * @param int $idProductAttribute Product attribute ID (default 0)
 * @param int $limit Maximum number of records to return (default 50)
 * @return array|false Array of price history records or false on error
 */
function dolcezampa_get_price_history($idProduct, $idProductAttribute = 0, $limit = 50)
{
    $module = Module::getInstanceByName('price_history');
    if (!$module || !$module->active) {
        return false;
    }
    
    return $module->getPriceHistory($idProduct, $idProductAttribute, $limit);
}

/**
 * Get lowest price in last 30 days for a product
 * 
 * @param int $idProduct Product ID
 * @param int $idProductAttribute Product attribute ID (default 0)
 * @return array|false Array with lowest price data or false if not found
 */
function dolcezampa_get_lowest_price_30d($idProduct, $idProductAttribute = 0)
{
    $module = Module::getInstanceByName('price_history');
    if (!$module || !$module->active) {
        return false;
    }
    
    return $module->getLowestPrice30d($idProduct, $idProductAttribute);
}

/**
 * Format price history for display
 * 
 * @param array $history Price history array
 * @return string Formatted HTML output
 */
function dolcezampa_format_price_history($history)
{
    if (empty($history)) {
        return '<p>Nessuno storico prezzi disponibile.</p>';
    }
    
    $html = '<div class="price-history">';
    $html .= '<table class="table table-bordered">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th>Data</th>';
    $html .= '<th>Prezzo Precedente</th>';
    $html .= '<th>Nuovo Prezzo</th>';
    $html .= '<th>Tipo</th>';
    $html .= '<th>Riduzione</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    
    foreach ($history as $entry) {
        $html .= '<tr>';
        $html .= '<td>' . date('d/m/Y H:i', strtotime($entry['date_add'])) . '</td>';
        $html .= '<td>' . number_format($entry['old_price'], 2, ',', '.') . ' €</td>';
        $html .= '<td>' . number_format($entry['new_price'], 2, ',', '.') . ' €</td>';
        $html .= '<td>' . htmlspecialchars($entry['price_type']) . '</td>';
        
        if ($entry['reduction_type'] && $entry['reduction_value']) {
            if ($entry['reduction_type'] == 'percentage') {
                $html .= '<td>-' . number_format($entry['reduction_value'], 0) . '%</td>';
            } else {
                $html .= '<td>-' . number_format($entry['reduction_value'], 2, ',', '.') . ' €</td>';
            }
        } else {
            $html .= '<td>-</td>';
        }
        
        $html .= '</tr>';
    }
    
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Display lowest price badge (for Omnibus directive compliance)
 * 
 * @param int $idProduct Product ID
 * @param int $idProductAttribute Product attribute ID (default 0)
 * @return string HTML badge with lowest price information
 */
function dolcezampa_display_lowest_price_badge($idProduct, $idProductAttribute = 0)
{
    $lowestPrice = dolcezampa_get_lowest_price_30d($idProduct, $idProductAttribute);
    
    if (!$lowestPrice) {
        return '';
    }
    
    $currentPrice = (float)$lowestPrice['current_price'];
    $lowest = (float)$lowestPrice['lowest_price'];
    
    // Don't display if current price is the lowest
    if (abs($currentPrice - $lowest) < 0.01) {
        return '';
    }
    
    $html = '<div class="lowest-price-30d" style="font-size: 0.9em; color: #666; margin-top: 5px;">';
    $html .= '<small>';
    $html .= 'Prezzo più basso negli ultimi 30 giorni: ';
    $html .= '<strong>' . number_format($lowest, 2, ',', '.') . ' €</strong>';
    $html .= '</small>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Check if current price is at historical low
 * 
 * @param int $idProduct Product ID
 * @param int $idProductAttribute Product attribute ID (default 0)
 * @return bool True if current price is the lowest
 */
function dolcezampa_is_at_historical_low($idProduct, $idProductAttribute = 0)
{
    $lowestPrice = dolcezampa_get_lowest_price_30d($idProduct, $idProductAttribute);
    
    if (!$lowestPrice) {
        return false;
    }
    
    $currentPrice = (float)$lowestPrice['current_price'];
    $lowest = (float)$lowestPrice['lowest_price'];
    
    return abs($currentPrice - $lowest) < 0.01;
}
