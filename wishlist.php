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

$wishlistItems = array_map(function($item) {
    return [
        'title' => $item['title'] ?? 'Unknown Title',
        'gameID' => $item['game_id'],
        'thumb' => $item['thumb'] ?? 'placeholder.jpg',
        'normalPrice' => $item['retail_price'] ?? $item['current_price'] ?? 'N/A',
        'salePrice' => $item['current_price'] ?? 'N/A',
        'savings' => round($item['savings'] ?? 0),
        'createdAt' => date('M d, Y', strtotime($item['created_at'])),
        'dealID' => $item['deal_id'] ?? null
    ];
}, $wishlist);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wishlist</title>
    <link rel="stylesheet" href="Important/main.css">
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
            <div class="grid-container" id="wishlist-grid"></div>
        <?php endif; ?>
    </div>

    <script>
        const grid = document.getElementById('wishlist-grid');

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

        function renderDeals(deals) {
            deals.forEach(game => {
                const card = document.createElement('div');
                card.classList.add('card');

                const savings = Math.round(game.savings);

                card.innerHTML = `
                    <div class="card-image">
                        <img src="${game.thumb}" alt="${game.title}" loading="lazy">
                    </div>
                    <div class="card-info">
                        <p><strong>Added On:</strong> ${game.createdAt}</p>
                        <h3>${game.title}</h3>
                        <div class="price">
                            <span class="original">$${game.normalPrice}</span>
                            <span class="sale">$${game.salePrice}</span>
                            <span style="font-size:12px; color:#ff4444; margin-left:5px;">-${savings}%</span>
                        </div>
                        <button onclick="removeFromWishlist('${game.gameID}')">Remove</button>
                    </div>
                `;

                if (game.dealID) {
                    card.style.cursor = 'pointer';
                    card.onclick = (event) => {
                        // Prevent redirect if user clicks the remove button
                        if (!event.target.matches('button')) {
                            try {                             
                                window.open(`https://www.cheapshark.com/redirect?dealID=${game.dealID}`, '_blank');
                            } catch (error) {
                                console.error('Failed to open redirect:', error);
                            }
                        }
                    };
                }

                grid.appendChild(card);
            });
        }

        window.addEventListener('DOMContentLoaded', () => {
            const wishlistItems = <?php echo json_encode($wishlistItems); ?>;
            renderDeals(wishlistItems);
        });
    </script>
</body>
</html>