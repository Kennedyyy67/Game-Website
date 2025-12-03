<?php
session_start();
// Check for authenticated user, redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: log in/main.php");
    exit;
}

require_once 'db.php';
require_once 'gameshark_api.php';
require_once 'wishlist_manager.php';

// --- Main Logic ---
$userId = $_SESSION['user_id'];
$wishlistManager = getWishlistManager();
$result = $wishlistManager->getUserWishlist($userId);
$wishlist = $result['success'] ? $result['wishlist'] : [];
$error = $result['success'] ? null : $result['error'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wishlist</title>
</head>
<body>

    <!-- Header Area -->
    <div>
        <h1>Wishlist</h1>
        <button onclick="window.location='mainmenu.php'">Return</button>
    </div>

    <!-- Main Content Area -->
    <div>
        <?php if ($error): ?>
            <p>Error loading wishlist: <?= htmlspecialchars($error) ?></p>
        <?php elseif (empty($wishlist)): ?>
            <p>Your wishlist is empty. Go back to the <a href="mainmenu.php">Deals page</a> to add some games!</p>
        <?php else: ?>
            
            <div>
                
                <?php foreach ($wishlist as $item): 
                    $title = htmlspecialchars($item['title'] ?? 'Unknown Title');
                    $thumb = htmlspecialchars($item['thumb'] ?? 'placeholder.jpg');
                    $gameId = htmlspecialchars($item['game_id']);
                    $createdAt = date('M d, Y', strtotime($item['created_at']));
                ?>
                    <!-- Wishlist Item Card -->
                    <div>
                        <img src="<?= $thumb ?>" alt="<?= $title ?>" style="width: 100%; height: auto; max-height: 150px; object-fit: cover;">
                        <h3><?= $title ?></h3>
                        <div class="price-info">
                        <p><strong>Added On:</strong> <?= $createdAt ?></p>
                        <button onclick="removeFromWishlist('<?= $gameId ?>')">Remove</button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function removeFromWishlist(gameId) {
            if (confirm("Are you sure you want to remove this game from your wishlist?")) {
               const formData = new FormData();
               formData.append('game_id', gameId);

            fetch('deletewish.php', {
               method: 'POST',
               body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert(result.message);
                    window.location.reload(); // Reload the page to update the list
                } else {
                    alert('Error removing game: ' + (result.error || 'Unknown error.'));
                }
             })
            .catch(error => {
                console.error('Network error:', error);
                alert('An error occurred while communicating with the server.');
              }); 
            } 
        }
    </script>
</body>
</html>

