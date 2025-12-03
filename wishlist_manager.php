<?php
require_once 'db.php';
require_once 'gameshark_api.php';

class WishlistManager {
    private $pdo;
    private $api;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->api = getGameSharkAPI();
    }
    
    /**
     * Add game to user's wishlist
     */
    public function addToWishlist($userId, $gameId, $targetPrice = null) {
        try {
            // Check if already in wishlist
            $checkStmt = $this->pdo->prepare("
                SELECT id FROM user_wishlist 
                WHERE user_id = ? AND game_id = ?
            ");
            $checkStmt->execute([$userId, $gameId]);
            
            if ($checkStmt->fetch()) {
                return ['success' => false, 'error' => 'Game already in wishlist'];
            }
            
            // Get game details from API if not in database
            $gameStmt = $this->pdo->prepare("
                SELECT id FROM games WHERE game_id = ?
            ");
            $gameStmt->execute([$gameId]);
            
            if (!$gameStmt->fetch()) {
                $gameDetails = $this->api->getGameDetails($gameId);
                if (isset($gameDetails['error']) || !isset($gameDetails['info'])) {
                    return ['success' => false, 'error' => 'Game not found'];
                }
                
                // Add game to database
                $insertGameStmt = $this->pdo->prepare("
                    INSERT INTO games (game_id, title, thumb) 
                    VALUES (?, ?, ?)
                ");
                $insertGameStmt->execute([
                    $gameId,
                    $gameDetails['info']['title'] ?? 'Unknown Game',
                    $gameDetails['info']['thumb'] ?? ''
                ]);
            }
            
            // Add to wishlist
            $insertStmt = $this->pdo->prepare("
                INSERT INTO user_wishlist (user_id, game_id, target_price) 
                VALUES (?, ?, ?)
            ");
            $insertStmt->execute([$userId, $gameId, $targetPrice]);
            
            return ['success' => true, 'message' => 'Game added to wishlist'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Remove game from user's wishlist
     */
    public function removeFromWishlist($userId, $gameId) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM user_wishlist 
                WHERE user_id = ? AND game_id = ?
            ");
            $stmt->execute([$userId, $gameId]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Game removed from wishlist'];
            } else {
                return ['success' => false, 'error' => 'Game not found in wishlist'];
            }
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get user's wishlist with current prices
     */
    public function getUserWishlist($userId) {
        try {
            $query = "
                SELECT 
                    w.*,
                    g.title,
                    g.thumb,
                    g.steam_rating,
                    d.price as current_price,
                    d.retail_price,
                    d.savings,
                    d.is_on_sale,
                    d.last_updated
                FROM user_wishlist w
                JOIN games g ON w.game_id = g.game_id
                LEFT JOIN deals d ON w.game_id = d.game_id
                WHERE w.user_id = ?
                ORDER BY w.created_at DESC
            ";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$userId]);
            $wishlist = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Check for price alerts
            foreach ($wishlist as &$item) {
                $item['price_alert'] = $this->checkPriceAlert($item);
            }
            
            return ['success' => true, 'wishlist' => $wishlist];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Check if current price meets target price
     */
    private function checkPriceAlert($wishlistItem) {
        if (!$wishlistItem['target_price'] || !$wishlistItem['current_price']) {
            return false;
        }
        
        return $wishlistItem['current_price'] <= $wishlistItem['target_price'];
    }
    
    /**
     * Update target price for wishlist item
     */
    public function updateTargetPrice($userId, $gameId, $targetPrice) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE user_wishlist 
                SET target_price = ? 
                WHERE user_id = ? AND game_id = ?
            ");
            $stmt->execute([$targetPrice, $userId, $gameId]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Target price updated'];
            } else {
                return ['success' => false, 'error' => 'Wishlist item not found'];
            }
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get price alerts for user
     */
    public function getPriceAlerts($userId) {
        try {
            $query = "
                SELECT 
                    w.*,
                    g.title,
                    g.thumb,
                    d.price as current_price,
                    d.retail_price
                FROM user_wishlist w
                JOIN games g ON w.game_id = g.game_id
                JOIN deals d ON w.game_id = d.game_id
                WHERE w.user_id = ? 
                AND w.target_price IS NOT NULL
                AND d.price <= w.target_price
                AND d.is_on_sale = 1
            ";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$userId]);
            $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'alerts' => $alerts];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }
}

// Helper function to get wishlist manager instance
function getWishlistManager() {
    return new WishlistManager();
}
?>
