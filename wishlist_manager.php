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
    public function addToWishlist($userId, $gameId, $storeId = null, $targetPrice = null, $notes = null) {
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
            
            // Always fetch game details from API
            $gameDetails = $this->api->getGameDetails($gameId);
            if (isset($gameDetails['error']) || !isset($gameDetails['info'])) {
                return ['success' => false, 'error' => 'Game not found'];
            }

            // Check if game is already in database
            $gameStmt = $this->pdo->prepare("
                SELECT id FROM games WHERE game_id = ?
            ");
            $gameStmt->execute([$gameId]);

            if (!$gameStmt->fetch()) {
                // Extract game information
                $title = $gameDetails['info']['title'] ?? 'Unknown Game';
                $thumb = $gameDetails['info']['thumb'] ?? '';
                $steamRating = $gameDetails['info']['steamRatingPercent'] ?? 0;

                // Extract pricing information from deals (use specific store's deal if storeId provided, else cheapest)
                $normalPrice = null;
                $salePrice = null;
                $savings = null;

                if (isset($gameDetails['deals']) && is_array($gameDetails['deals']) && count($gameDetails['deals']) > 0) {
                    $selectedDeal = null;
                    if ($storeId !== null) {
                        // Find deal for specific store
                        foreach ($gameDetails['deals'] as $deal) {
                            if (($deal['storeID'] ?? null) == $storeId) {
                                $selectedDeal = $deal;
                                break;
                            }
                        }
                    }
                    if (!$selectedDeal) {
                        // Fallback to cheapest if no store-specific deal found or storeId not provided
                        usort($gameDetails['deals'], function($a, $b) {
                            return ($a['price'] ?? 999999) <=> ($b['price'] ?? 999999);
                        });
                        $selectedDeal = $gameDetails['deals'][0];
                    }
                    $normalPrice = $selectedDeal['retailPrice'] ?? null;
                    $salePrice = $selectedDeal['price'] ?? null;
                    $savings = $selectedDeal['savings'] ?? null;
                }

                // Add game to database with pricing information
                $insertGameStmt = $this->pdo->prepare("
                    INSERT INTO games (game_id, title, normal_price, sale_price, savings, steam_rating, thumb)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $insertGameStmt->execute([
                    $gameId,
                    $title,
                    $normalPrice,
                    $salePrice,
                    $savings,
                    $steamRating,
                    $thumb
                ]);
            }

            // Always insert/update deal data for the relevant stores
            if (isset($gameDetails['deals']) && is_array($gameDetails['deals'])) {
                $dealsToInsert = $gameDetails['deals'];
                if ($storeId !== null) {
                    // Only insert deals for the specific store
                    $dealsToInsert = array_filter($dealsToInsert, function($deal) use ($storeId) {
                        return ($deal['storeID'] ?? null) == $storeId;
                    });
                }

                foreach ($dealsToInsert as $deal) {
                    $dealStmt = $this->pdo->prepare("
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
                        $gameId,
                        $deal['storeID'] ?? null,
                        $deal['dealID'] ?? '',
                        $deal['price'] ?? null,
                        $deal['retailPrice'] ?? null,
                        $deal['savings'] ?? 0,
                        1  // Mark as on sale since these are current deals
                    ]);
                }
            }
            
            // Add to wishlist
            $insertStmt = $this->pdo->prepare("
                INSERT INTO user_wishlist (user_id, game_id, store_id, target_price, notes)
                VALUES (?, ?, ?, ?, ?)
            ");
            $insertStmt->execute([$userId, $gameId, $storeId, $targetPrice, $notes]);
            
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
                    d.last_updated,
                    d.deal_id
                FROM user_wishlist w
                JOIN games g ON w.game_id = g.game_id
                LEFT JOIN deals d ON w.game_id = d.game_id AND w.store_id = d.store_id
                WHERE w.user_id = ?
                ORDER BY w.created_at DESC
            ";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$userId]);
            $wishlist = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['success' => true, 'wishlist' => $wishlist];

        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
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
     * Update notes for wishlist item
     */
    public function updateNotes($userId, $gameId, $notes) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE user_wishlist
                SET notes = ?
                WHERE user_id = ? AND game_id = ?
            ");
            $stmt->execute([$notes, $userId, $gameId]);

            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Notes updated'];
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
