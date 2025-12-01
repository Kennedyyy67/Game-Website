<?php
//add_wishlist
session_start();
header('Content-Type: application/json');

// 1. Check for authenticated user
// The user's provided front-end code checks for this session variable.
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not authenticated.']);
    exit;
}

// 2. Check for required POST data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

// Sanitize and validate input
$gameId = filter_input(INPUT_POST, 'game_id', FILTER_SANITIZE_STRING);
$targetPrice = filter_input(INPUT_POST, 'target_price', FILTER_VALIDATE_FLOAT);

if (empty($gameId)) {
    echo json_encode(['success' => false, 'error' => 'Missing game_id parameter.']);
    exit;
}

// 3. Include necessary files and get manager
// These files were created/updated in the previous steps
require_once 'db.php';
require_once 'gameshark_api.php'; 
require_once 'wishlist_manager.php';

$userId = $_SESSION['user_id'];
$wishlistManager = getWishlistManager();

// 4. Call the addToWishlist method
// The manager handles the targetPrice being null if not provided
$result = $wishlistManager->addToWishlist($userId, $gameId, $targetPrice);

// 5. Return JSON response
if ($result['success']) {
    echo json_encode(['success' => true, 'message' => 'Game added to wishlist successfully.']);
} else {
    // Use the error message from the manager
    echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Failed to add game to wishlist.']);
}
?>
