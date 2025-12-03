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

require_once 'gameshark_api.php';
require_once 'wishlist_manager.php'; 

// 4. Get data from POST request
$gameId = $_POST['game_id'] ?? null;
$userId = $_SESSION['user_id'];

// Basic validation
if (empty($gameId)) {
    echo json_encode(['success' => false, 'error' => 'Missing game ID.']);
    exit;
}

// 5. Instantiate and call WishlistManager
try {
    $wishlistManager = getWishlistManager();
    
    // Call the removeFromWishlist method
    $result = $wishlistManager->removeFromWishlist($userId, $gameId);
    
    echo json_encode($result);

} catch (Exception $e) {
    // Catch any unexpected errors
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>
