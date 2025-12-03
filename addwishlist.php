<?php
session_start();
header('Content-Type: application/json');

// 1. Check for authenticated user
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in.']);
    exit;
}

// 2. Check for POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

require_once 'db.php';
require_once 'gameshark_api.php';
require_once 'wishlist_manager.php'; 

// 4. Get data from POST request
$gameId = $_POST['game_id'] ?? null;
$storeId = $_POST['store_id'] ?? null;
$targetPrice = $_POST['target_price'] ?? null;
$userId = $_SESSION['user_id'];

// Basic validation
if (empty($gameId)) {
    echo json_encode(['success' => false, 'error' => 'Missing game ID.']);
    exit;
}

// 5. Instantiate and call WishlistManager
try {
    $wishlistManager = getWishlistManager();
    
    // The targetPrice might be an empty string from the form, convert to null if so.
    $targetPrice = $targetPrice === '' ? null : (float)$targetPrice;

    $result = $wishlistManager->addToWishlist($userId, $gameId, $storeId, $targetPrice);
    
    echo json_encode($result);

} catch (Exception $e) {
    // Catch any unexpected errors
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>
