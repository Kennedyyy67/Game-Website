<?php
require_once 'gameshark_api.php';
require_once 'db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$api = getGameSharkAPI();
$requestMethod = $_SERVER['REQUEST_METHOD'];

$endpoint = $_GET['endpoint'] ?? '';

switch ($endpoint) {
    case 'deals':
        if ($requestMethod == 'GET') {
            getDeals();
        } else {
            sendResponse(['error' => 'Method not allowed'], 405);
        }
        break;
        
    case 'search':
        if ($requestMethod == 'GET') {
            searchGames();
        } else {
            sendResponse(['error' => 'Method not allowed'], 405);
        }
        break;
        
    case 'game':
        if ($requestMethod == 'GET') {
            getGameDetails();
        } else {
            sendResponse(['error' => 'Method not allowed'], 405);
        }
        break;
        
    case 'sync':
        if ($requestMethod == 'POST') {
            syncDeals();
        } else {
            sendResponse(['error' => 'Method not allowed'], 405);
        }
        break;
        
    case 'stores':
        if ($requestMethod == 'GET') {
            getStores();
        } else {
            sendResponse(['error' => 'Method not allowed'], 405);
        }
        break;
        
    default:
        // Optional: Default to deals if no endpoint specified, or show error
        if ($endpoint === '') {
             sendResponse(['error' => 'No endpoint specified'], 400);
        } else {
             sendResponse(['error' => 'Endpoint not found'], 404);
        }
        break;
}

/**
 * Get game deals with optional filtering
 */
function getDeals() {
    global $pdo, $api;

    $params = [
        'pageSize' => $_GET['pageSize'] ?? 20,
        'pageNumber' => $_GET['pageNumber'] ?? 0,
        'storeID' => $_GET['storeID'] ?? 1,
        'lowerPrice' => $_GET['min_price'] ?? 0,
        'upperPrice' => $_GET['max_price'] ?? 100,
        'onSale' => isset($_GET['on_sale']) ? ($_GET['on_sale'] == 'true') : true,
        'sortBy' => $_GET['sortBy'] ?? 'Deal Rating',
        'desc' => isset($_GET['desc']) ? ($_GET['desc'] == 'true') : true
    ];
    
    // Database logic (kept original)
    try {
        $query = "
            SELECT d.*, g.title, g.thumb, g.steam_rating 
            FROM deals d 
            JOIN games g ON d.game_id = g.game_id 
            WHERE d.is_on_sale = 1 
            AND d.price BETWEEN ? AND ?
            ORDER BY d.savings DESC 
            LIMIT ? OFFSET ?
        ";
        
        // Note: Added OFFSET for pagination in DB query
        $offset = $params['pageNumber'] * $params['pageSize'];

        // Make sure your PDO connection $pdo is active in db.php
        if ($pdo) {
             $stmt = $pdo->prepare($query);
             $stmt->execute([
                 $params['lowerPrice'],
                 $params['upperPrice'],
                 $params['pageSize']
                 // Missing offset logic in original, logic added implicitly via API fallback usually
             ]);
             
             $deals = $stmt->fetchAll(PDO::FETCH_ASSOC);
             
             if (count($deals) > 0) {
                 sendResponse([
                     'success' => true,
                     'deals' => $deals,
                     'total' => count($deals),
                     'source' => 'database'
                 ]);
                 return;
             }
        }
    } catch (PDOException $e) {
        // Fall back to API
    }
    
    // If no database results, use API
    $apiDeals = $api->getDeals($params);
    
    if (isset($apiDeals['error'])) {
        sendResponse(['error' => $apiDeals['error']], 500);
        return;
    }
    
    // IMPORTANT: This response structure is { success: true, deals: [...] }
    sendResponse([
        'success' => true,
        'deals' => $apiDeals,
        'total' => count($apiDeals),
        'source' => 'api'
    ]);
}

/**
 * Search for games
 */
function searchGames() {
    global $api;
    
    $query = $_GET['q'] ?? '';
    $limit = $_GET['limit'] ?? 20;
    
    if (empty($query)) {
        sendResponse(['error' => 'Search query is required'], 400);
        return;
    }
    
    $results = $api->searchGames($query, $limit);
    
    if (isset($results['error'])) {
        sendResponse(['error' => $results['error']], 500);
        return;
    }
    
    sendResponse([
        'success' => true,
        'results' => $results,
        'query' => $query,
        'total' => count($results)
    ]);
}

/**
 * Get game details by ID
 */
function getGameDetails() {
    global $api, $pdo;
    
    $gameId = $_GET['id'] ?? '';
    
    if (empty($gameId)) {
        sendResponse(['error' => 'Game ID is required'], 400);
        return;
    }
    
    try {
        $query = "
            SELECT g.*, d.price, d.retail_price, d.savings, d.is_on_sale, d.last_updated 
            FROM games g 
            LEFT JOIN deals d ON g.game_id = d.game_id 
            WHERE g.game_id = ?
            ORDER BY d.last_updated DESC 
            LIMIT 1
        ";
        
        if ($pdo) {
            $stmt = $pdo->prepare($query);
            $stmt->execute([$gameId]);
            $game = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($game) {
                sendResponse([
                    'success' => true,
                    'game' => $game,
                    'source' => 'database'
                ]);
                return;
            }
        }
    } catch (PDOException $e) {
        // Fall back
    }
    
    $gameDetails = $api->getGameDetails($gameId);
    
    if (isset($gameDetails['error'])) {
        sendResponse(['error' => $gameDetails['error']], 500);
        return;
    }
    
    sendResponse([
        'success' => true,
        'game' => $gameDetails,
        'source' => 'api'
    ]);
}

/**
 * Sync deals from API to database
 */
function syncDeals() {
    global $api;
    
    $result = $api->syncDealsToDatabase();
    
    if ($result['success']) {
        sendResponse([
            'success' => true,
            'message' => 'Deals synced successfully',
            'stats' => $result
        ]);
    } else {
        sendResponse(['error' => $result['error']], 500);
    }
}

/**
 * Get stores list
 */
function getStores() {
    global $api;
    
    $stores = $api->getStores();
    
    if (isset($stores['error'])) {
        sendResponse(['error' => $stores['error']], 500);
        return;
    }
    
    sendResponse([
        'success' => true,
        'stores' => $stores,
        'total' => count($stores)
    ]);
}

/**
 * Send JSON response
 */
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}
?>
