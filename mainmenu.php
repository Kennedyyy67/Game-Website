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

   

        <div class="top-bar">
            <div class="logowrapper">
                <img src="Important/gglogo.png" alt="GG Deals Logo" class="logoimg">
            
            <div class="logo" onclick="searchMode ? exitSearchMode() : location.reload()">Deals</div>
            </div>
            <div class="search-wrapper">
                <input class="search-container" type="text" id="search" placeholder="Search for games...">
            </div>
            <div class="user-actions">
               
                <button class="btn-black" onclick="window.location='wishlist.php'">
                    <img src="Important/favorite.png" alt="GG Deals Logo" class="btnimg">Wishlist</button>
                
              
                    
                <button class="btn-black" onclick="window.location='log in/logout.php'">
                    <img src="Important/profile.png" alt="GG Deals Logo" class="btnimg">Logout</button> 
          
            </div>

        </div>
        

        <div class="about">
            
            <!-- <img src="Important/aboutpic.jpg" alt="GG Deals Logo" class="about-img"> -->
            <div class="textabout">
            <h2>Welcome User!</h2>
            <p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Suscipit a nobis, adipisci voluptas nihil error iure nostrum ullam dolorem mollitia expedita facere dolor corporis odio laudantium eos minima quod asperiores.</p>
            </div>
        </div>
           

        </div>

        <nav class="stores">
            <div class="hstores">
                <h3>Supported by One of the most known Game Stores</h3>
            </div>
            
            <nav class="store-nav">
            <a href="#" class="store-link active" data-id="1">Steam</a>
            <a href="#" class="store-link" data-id="2">GamersGate</a>
            <a href="#" class="store-link" data-id="15">Fanatical</a>
            <a href="#" class="store-link" data-id="7">GoG</a>
            <a href="#" class="store-link" data-id="8">Origin</a>
            </nav>
        </nav>

        <div class="sort-container">
            <label for="sort-select">Sort by:</label>
            <select id="sort-select">
                <option value="Deal Rating">Deal Rating</option>
                <option value="Title">Title</option>
                <option value="Savings">Savings</option>
                <option value="Price">Price</option>
                <option value="Metacritic">Metacritic</option>
                <option value="Reviews">Reviews</option>
                <option value="Recent">Recent</option>
                <option value="Release">Release</option>
            </select>
            <button id="sort-direction-btn" title="Toggle sort direction">▼</button>
        </div>
    </header>

    <main>
        <div class="grid-container" id="deals-grid">
            </div>

        <div class="pagination">
            <button id="prevBtn">Previous</button>
            <button id="nextBtn">Next</button>
        </div>

        <div class="loader-container">
            <div class="loader" id="loader"></div>
        </div>
    </main>

    <script>
        // --- Configuration ---
        // Removed trailing slash, using query param routing for stability
        const API_BASE = 'api.php';
        const pageSize = 16;
        let currentStoreId = 1;
        let currentPage = 0;
        let currentSort = 'Deal Rating';
        let sortDescending = true;
        let isLoading = false;
        let hasNext = true;
        let hasPrev = false;
        let searchMode = false;

        const grid = document.getElementById('deals-grid');
        const loader = document.getElementById('loader');
        const navLinks = document.querySelectorAll('.store-link');
        const searchInput = document.getElementById('search');
        const sortSelect = document.getElementById('sort-select');
        const sortDirectionBtn = document.getElementById('sort-direction-btn');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');

        function updateBtnStates() {
            prevBtn.disabled = !hasPrev;
            nextBtn.disabled = !hasNext;
        }

        
          // --- Wishlist Functions ---
        async function addToWishlist(event, gameId, targetPrice = null) {
            event.stopPropagation();
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
                            gameID: game.gameID,
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
                hasNext = true;
                hasPrev = false;
                fetchDeals();
            }
        }

        async function fetchDeals() {
            if (searchMode || isLoading) return;

            isLoading = true;
            loader.classList.add('show');

            try {
                const url = `${API_BASE}?endpoint=deals&storeID=${currentStoreId}&pageNumber=${currentPage}&pageSize=${pageSize}&sortBy=${encodeURIComponent(currentSort)}&desc=${sortDescending}`;
                const response = await fetch(url);
                const data = await response.json();

                if (data.success && Array.isArray(data.deals)) {
                    renderDeals(data.deals);
                    hasNext = data.deals.length == pageSize;
                    hasPrev = currentPage > 0;
                    updateBtnStates();
                } else {
                    hasNext = false;
                    updateBtnStates();
                }
            } catch (error) {
                console.error('Error fetching deals:', error);
                hasNext = false;
                updateBtnStates();
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
                         <button class="btn-black add-to-wishlist-btn" data-game-id="${game.gameID}" onclick="addToWishlist(event,'${game.gameID}')">Add to Wishlist</button>
                    </div>
                `;

                if (!searchMode && game.dealID) {
                    card.style.cursor = 'pointer';
                    card.onclick = () => {
                        try {
                            window.open(`https://www.cheapshark.com/redirect?dealID=${game.dealID}`, '_blank');
                        } catch (error) {
                            console.error('Failed to open redirect:', error);
                        }
                    };
                }

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
            hasNext = true; // Reset availability
            hasPrev = false;
            grid.innerHTML = '';
            fetchDeals(); // Trigger fetch immediately
        }

        window.addEventListener('DOMContentLoaded', () => {
            fetchDeals();
            updateBtnStates();
        });

        prevBtn.addEventListener('click', () => {
            if (hasPrev) {
                currentPage--;
                grid.innerHTML = '';
                fetchDeals();
            }
        });

        nextBtn.addEventListener('click', () => {
            if (hasNext) {
                currentPage++;
                grid.innerHTML = '';
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

        sortSelect.addEventListener('change', (event) => {
            const newSort = event.target.value;
            if (currentSort === newSort) return;

            currentSort = newSort;
            currentPage = 0; // Reset to first page when sorting changes
            hasNext = true;
            hasPrev = false;
            grid.innerHTML = '';
            fetchDeals();
        });

        sortDirectionBtn.addEventListener('click', () => {
            sortDescending = !sortDescending;
            sortDirectionBtn.textContent = sortDescending ? '▼' : '▲';
            currentPage = 0; // Reset to first page when direction changes
            hasNext = true;
            hasPrev = false;
            grid.innerHTML = '';
            fetchDeals();
        });
    </script>
</body>
</html>
