<?php
require_once 'db.php';

class GameSharkAPI {
    private $apiKey;
    private $baseUrl = 'https://www.cheapshark.com/api/1.0/';
    
    public function __construct($apiKey = '') {
        $this->apiKey = $apiKey;
    }
    
    /**
     * Search for games by title
     */
    public function searchGames($title, $limit = 20) {
        $url = $this->baseUrl . "games?title=" . urlencode($title) . "&limit=" . $limit;
        return $this->makeRequest($url);
    }
    
    /**
     * Get game deals
     */
    public function getDeals($params = []) {
        $defaultParams = [
            'storeID' => 1, // Steam
            'pageNumber' => 0,
            'pageSize' => 20,
            'sortBy' => 'Deal Rating',
            'desc' => true,
            'lowerPrice' => 0,
            'upperPrice' => 50,
            'metacritic' => 0,
            'steamRating' => 0,
            'onSale' => true
        ];
        
        $queryParams = array_merge($defaultParams, $params);
        $url = $this->baseUrl . "deals?" . http_build_query($queryParams);
        return $this->makeRequest($url);
    }
    
    /**
     * Get game details by ID
     */
    public function getGameDetails($gameId) {
        $url = $this->baseUrl . "games?id=" . $gameId;
        return $this->makeRequest($url);
    }
    
    /**
     * Get stores list
     */
    public function getStores() {
        $url = $this->baseUrl . "stores";
        return $this->makeRequest($url);
    }
    
    /**
     * Make HTTP request to API
     */
    private function makeRequest($url) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: GameDealsWebsite/1.0'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            return json_decode($response, true);
        }
        
        return ['error' => 'Failed to fetch data from API', 'code' => $httpCode];
    }
    
    /**
     * Sync game deals to database
     */
    public function syncDealsToDatabase($deals = null) {
        global $pdo;
        
        if (!$deals) {
            $deals = $this->getDeals(['pageSize' => 50]);
        }
        
        if (isset($deals['error'])) {
            return ['success' => false, 'error' => $deals['error']];
        }
        
        $synced = 0;
        $errors = 0;
        
        foreach ($deals as $deal) {
            try {
                // Insert or update game
                $gameStmt = $pdo->prepare("
                    INSERT INTO games (game_id, title, normal_price, sale_price, savings, steam_rating, thumb) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    title = VALUES(title), 
                    normal_price = VALUES(normal_price), 
                    sale_price = VALUES(sale_price), 
                    savings = VALUES(savings), 
                    steam_rating = VALUES(steam_rating), 
                    thumb = VALUES(thumb)
                ");
                
                $gameStmt->execute([
                    $deal['gameID'],
                    $deal['title'],
                    $deal['normalPrice'],
                    $deal['salePrice'],
                    $deal['savings'],
                    $deal['steamRatingPercent'] ?? null,
                    $deal['thumb']
                ]);
                
                // Insert or update deal
                $dealStmt = $pdo->prepare("
                    INSERT INTO deals (game_id, store_id, deal_id, price, retail_price, savings, is_on_sale) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    price = VALUES(price), 
                    retail_price = VALUES(retail_price), 
                    savings = VALUES(savings), 
                    is_on_sale = VALUES(is_on_sale),
                    last_updated = CURRENT_TIMESTAMP
                ");
                
                $dealStmt->execute([
                    $deal['gameID'],
                    $deal['storeID'],
                    $deal['dealID'],
                    $deal['salePrice'],
                    $deal['normalPrice'],
                    $deal['savings'],
                    $deal['isOnSale'] ? 1 : 0
                ]);
                
                $synced++;
            } catch (PDOException $e) {
                $errors++;
                error_log("Error syncing deal: " . $e->getMessage());
            }
        }
        
        return [
            'success' => true,
            'synced' => $synced,
            'errors' => $errors,
            'total' => count($deals)
        ];
    }
}

// Helper function to get API instance
function getGameSharkAPI() {
    return new GameSharkAPI();
}
