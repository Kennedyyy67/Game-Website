<?php
// update_wishlist.php
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

// 2. Validate input
$gameId = filter_input(INPUT_POST, 'game_id', FILTER_SANITIZE_STRING);
$targetPrice = filter_input(INPUT_POST, 'target_price', FILTER_VALIDATE_FLOAT);

if (!$gameId) {
    echo json_encode(['success' => false, 'error' => 'Missing game ID.']);
    exit;
}

if ($targetPrice === false || $targetPrice < 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid target price.']);
    exit;
}

$userId = $_SESSION['user_id'];
$wishlistManager = getWishlistManager();

// 3. Update target price
$result = $wishlistManager->updateTargetPrice($userId, $gameId, $targetPrice);

// 4. Send response
echo json_encode($result);
exit;
?>
