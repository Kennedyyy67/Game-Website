<?php
// delete_wishlist.php
session_start();
header('Content-Type: application/json');

require_once 'wishlist_manager.php';

// 1. Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not authenticated.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

// 2. Validate inputs
$gameId = filter_input(INPUT_POST, 'game_id', FILTER_SANITIZE_STRING);

if (!$gameId) {
    echo json_encode(['success' => false, 'error' => 'Missing game ID.']);
    exit;
}

$userId = $_SESSION['user_id'];
$wishlistManager = getWishlistManager();

// 3. Call removeFromWishlist()
$result = $wishlistManager->removeFromWishlist($userId, $gameId);

// 4. Return JSON
echo json_encode($result);
exit;
?>
