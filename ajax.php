<?php
/**
 * AJAX endpoint for Mlab Price History Module
 */

require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/price_history.php';

// Check if module is active
$module = Module::getInstanceByName('price_history');
if (!$module || !$module->active) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Module not active'
    ]);
    exit;
}

$action = Tools::getValue('action');

switch ($action) {
    case 'getLowestPrice':
        $idProduct = (int)Tools::getValue('id_product');
        $idProductAttribute = (int)Tools::getValue('id_product_attribute', 0);
        
        if (!$idProduct) {
            echo json_encode([
                'success' => false,
                'message' => 'Product ID required'
            ]);
            exit;
        }
        
        $data = $module->getLowestPrice30d($idProduct, $idProductAttribute);
        
        if ($data) {
            echo json_encode([
                'success' => true,
                'data' => [
                    'lowest_price' => number_format((float)$data['lowest_price'], 2, '.', ''),
                    'lowest_price_date' => $data['lowest_price_date'],
                    'current_price' => number_format((float)$data['current_price'], 2, '.', ''),
                    'formatted_lowest_price' => Tools::displayPrice($data['lowest_price']),
                    'formatted_current_price' => Tools::displayPrice($data['current_price']),
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No data found'
            ]);
        }
        break;
        
    case 'getPriceHistory':
        $idProduct = (int)Tools::getValue('id_product');
        $idProductAttribute = (int)Tools::getValue('id_product_attribute', 0);
        $limit = (int)Tools::getValue('limit', 20);
        
        if (!$idProduct) {
            echo json_encode([
                'success' => false,
                'message' => 'Product ID required'
            ]);
            exit;
        }
        
        if ($limit > 100) {
            $limit = 100;
        }
        
        $history = $module->getPriceHistory($idProduct, $idProductAttribute, $limit);
        
        if ($history) {
            // Format prices
            foreach ($history as &$entry) {
                $entry['formatted_old_price'] = Tools::displayPrice($entry['old_price']);
                $entry['formatted_new_price'] = Tools::displayPrice($entry['new_price']);
                $entry['old_price'] = number_format((float)$entry['old_price'], 2, '.', '');
                $entry['new_price'] = number_format((float)$entry['new_price'], 2, '.', '');
            }
            
            echo json_encode([
                'success' => true,
                'data' => $history,
                'count' => count($history)
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No history found'
            ]);
        }
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
        break;
}

exit;
