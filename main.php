<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: log in/main.php");
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GG - Infinite Deals</title>
    <link rel="stylesheet" href="Important/main.css">
</head>
<body>

    <header>
        <div class="top-bar">

            <div class="logo" onclick="searchMode ? exitSearchMode() : location.reload()">GG Deals</div>
            <div class="search-wrapper">
                <input class="search-container" type="text" id="search" placeholder="Search for games...">
            </div>
            <div class="user-actions">
                <button class="btn-black" onclick="window.location='wishlist.php'">Wishlist</button>
                <button class="btn-black" onclick="window.location='log in/logout.php'">Logout</button> 
            </div>
           

        </div>

        <nav class="store-nav">
            <a href="#" class="store-link active" data-id="1">Steam</a>
            <a href="#" class="store-link" data-id="2">GamersGate</a>
            <a href="#" class="store-link" data-id="15">Fanatical</a>
            <a href="#" class="store-link" data-id="7">GoG</a>
            <a href="#" class="store-link" data-id="8">Origin</a>
        </nav>

       
    </header>

    <main>
        <div class="grid-container" id="deals-grid">
        
        </div>
        
        <div class="loader-container">
            <div class="loader" id="loader"></div>
        </div>
    </main>

    <script>
        // --- Configuration ---
        // Removed trailing slash, using query param routing for stability
        const API_BASE = 'api.php'; 
        let currentStoreId = 1; 
        let currentPage = 0;    
        let isLoading = false;  
        let hasMoreDeals = true; 
        let searchMode = false; 

        const grid = document.getElementById('deals-grid');
        const loader = document.getElementById('loader');
        const navLinks = document.querySelectorAll('.store-link');
        const searchInput = document.getElementById('search');

          // --- Wishlist Functions ---

        /**
         * Handles the API call to add a game to the user's wishlist.
         * @param {string} gameId - The unique ID of the game.
         * @param {number} targetPrice - The target price for the alert (optional).
         */

        async function addToWishlist(gameId, targetPrice = null) {
            const button = document.querySelector(`button[data-game-id="${gameId}"]`);
            if (button) {
                button.disabled = true;
                button.textContent = 'Adding...';
            }

            const formData = new FormData();
            formData.append('game_id', gameId);
            if (targetPrice !== null) {
                formData.append('target_price', targetPrice);
            }

            try {
                const response = await fetch('addwishlist.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    alert('Success: ' + result.message);
                    if (button) {
                        button.textContent = 'Added!';
                        button.classList.add('added');
                    }
                } else {
                    alert('Error: ' + (result.error || 'Failed to add game to wishlist.'));
                    if (button) {
                        button.disabled = false;
                        button.textContent = 'Add to Wishlist';
                    }
                }
            } catch (error) {
                console.error('Network error:', error);
                alert('An error occurred while adding to the wishlist.');
                if (button) {
                    button.disabled = false;
                    button.textContent = 'Add to Wishlist';
                }
            }
        }

        // --- Core Functions ---

        async function performSearch() {
            const query = searchInput.value.trim();
            if (!query) return;

            searchMode = true;
            grid.innerHTML = ''; 
            loader.classList.add('show');

            try {
                //Updated URL structure to use endpoint parameter
                const url = `${API_BASE}?endpoint=search&q=${encodeURIComponent(query)}&limit=20`;
                const response = await fetch(url);
                const data = await response.json();

                // Check for data.results (Backend returns { success: true, results: [...] })
                if (data.success && data.results && data.results.length > 0) {
                    const processedResults = data.results.map(game => {
                        const title = game.title || game.external || 'Unknown Title';
                        const salePrice = game.cheapest || (game.cheapestPriceEver && game.cheapestPriceEver.price) || game.price || 'N/A';
                        const normalPrice = game.normalPrice || (game.retailPrice ? game.retailPrice : salePrice);
                        const savings = Math.round(game.savings || 0);
                        return {
                            title,
                            thumb: game.thumb,
                            normalPrice,
                            salePrice,
                            savings
                        };
                    });
                    renderDeals(processedResults);
                } else {
                    grid.innerHTML = '<div class="no-results">No games found matching your search.</div>';
                }
            } catch (error) {
                console.error('Error performing search:', error);
                grid.innerHTML = '<div class="no-results">Search failed. Please try again.</div>';
            } finally {
                loader.classList.remove('show');
            }
        }



        function exitSearchMode() {
            if (searchMode) {
                searchMode = false;
                searchInput.value = '';
                grid.innerHTML = '';
                currentPage = 0;
                hasMoreDeals = true;
                fetchDeals();
            }
        }

        async function fetchDeals() {
            if (searchMode || isLoading || !hasMoreDeals) return;

            isLoading = true;
            loader.classList.add('show');

            try {
                // Updated URL structure to use endpoint parameter
                const url = `${API_BASE}?endpoint=deals&storeID=${currentStoreId}&pageNumber=${currentPage}&pageSize=12`;
                const response = await fetch(url);
                const data = await response.json();

                // The API returns { success: true, deals: [...] }, NOT just [...]
                if (data.success && Array.isArray(data.deals)) {
                    if (data.deals.length === 0) {
                        hasMoreDeals = false;
                    } else {
                        renderDeals(data.deals);
                        currentPage++;
                    }
                } else if (data.length === 0) {
                    // Fallback in case API structure changes to raw array
                    hasMoreDeals = false;
                }

            } catch (error) {
                console.error('Error fetching deals:', error);
            } finally {
                isLoading = false;
                loader.classList.remove('show');
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
                        <h3>${game.title}</h3>
                        <div class="price">
                            <span class="original">$${game.normalPrice}</span>
                            <span class="sale">$${game.salePrice}</span>
                            <span style="font-size:12px; color:#ff4444; margin-left:5px;">-${savings}%</span>
                        </div>
                         <button class="btn-black add-to-wishlist-btn" data-game-id="${game.gameID}" onclick="addToWishlist('${game.gameID}')">Add to Wishlist</button>
                    </div>
                `;
                grid.appendChild(card);
            });
        }

        function switchStore(event) {
            event.preventDefault();
            const target = event.target;
            const newStoreId = target.getAttribute('data-id');
            if (currentStoreId == newStoreId) return;

            if (searchMode) {
                exitSearchMode();
            }

            navLinks.forEach(link => link.classList.remove('active'));
            target.classList.add('active');

            currentStoreId = newStoreId;
            currentPage = 0; // Reset page on store switch
            hasMoreDeals = true; // Reset availability
            grid.innerHTML = ''; 
            fetchDeals(); // Trigger fetch immediately
        }

        window.addEventListener('DOMContentLoaded', fetchDeals);

        window.addEventListener('scroll', () => {
            const { scrollTop, scrollHeight, clientHeight } = document.documentElement;
            if (scrollTop + clientHeight >= scrollHeight - 100) {
                fetchDeals();
            }
        });

        navLinks.forEach(link => {
            link.addEventListener('click', switchStore);
        });

        searchInput.addEventListener('keypress', (event) => {
            if (event.key === 'Enter') {
                performSearch();
            }
        });

    </script>
</body>
</html>
