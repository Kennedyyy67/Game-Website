<?php
//wishlist.php

require_once 'db.php';
require_once 'gameshark_api.php';
require_once 'wishlist_manager.php';

// --- Configuration ---
//$testUserId = ; 

// 1. Get the WishlistManager instance
$wishlistManager = getWishlistManager();

// 2. Retrieve the user's wishlist
$result = $wishlistManager->getUserWishlist('user_id');

// 3. Display the results
echo "<h1>Wishlist</h1>\n";

if (!$result['success']) {
    echo "<p style='color: red;'>Error retrieving wishlist: " . htmlspecialchars($result['error']) . "</p>\n";
} else {
    $wishlist = $result['wishlist'];
    
    if (empty($wishlist)) {
        echo "<p>Your wishlist is currently empty.</p>\n";
    } else {
        echo "<table>\n";
        echo "  <thead>\n";
        echo "    <tr>\n";
        echo "      <th>Game ID</th>\n";
        echo "      <th>Target Price</th>\n";
        echo "      <th>Added On</th>\n";
        echo "    </tr>\n";
        echo "  </thead>\n";
        echo "  <tbody>\n";
        
        foreach ($wishlist as $item) {
            // use the game_id to fetch 
            // the full game details (name, image, etc.) from an external API 
            // or a local database cache.
            $gameId = htmlspecialchars($item['game_id']);
            $targetPrice = number_format($item['target_price'], 2);
            $addedOn = date('Y-m-d H:i:s', strtotime($item['added_on']));
            
            echo "    <tr>\n";
            echo "      <td>{$gameId}</td>\n";
            echo "      <td>\${$targetPrice}</td>\n";
            echo "      <td>{$addedOn}</td>\n";
            echo "    </tr>\n";
        }
        
        echo "  </tbody>\n";
        echo "</table>\n";
        
        /* if (count($wishlist) < 2) {
            $wishlistManager->addToWishlist($testUserId, 'TEST_GAME_1', 49.99);
            $wishlistManager->addToWishlist($testUserId, 'TEST_GAME_2', 19.99);
            echo "<p><em>Two test items were temporarily added. Please refresh to see them.</em></p>\n";
        } */
    }
}
