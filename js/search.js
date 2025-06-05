/**
 * GK Lab - Search Functionality
 * This script handles the search functionality for filtering cards based on user input
 * Works across index.html, tests.php, and checkups.php
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get the search input and search button
    const searchInput = document.getElementById('search-input');
    const searchButton = document.querySelector('.search-icon-btn');
    
    if (!searchInput || !searchButton) {
        return; // Exit if search elements don't exist
    }
    
    // Determine which page we're on
    const isHomePage = window.location.pathname.endsWith('index.html') || window.location.pathname.endsWith('/');
    const isTestsPage = window.location.pathname.includes('tests.php');
    const isCheckupsPage = window.location.pathname.includes('checkups.php');
    
    // Handle search submission (both button click and enter key)
    searchButton.addEventListener('click', performSearch);
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            performSearch();
        }
    });
    
    // Helper function to check if a card contains the search term
    function cardContainsSearchTerm(card, searchTerm) {
        // Convert search term to lowercase for case-insensitive search
        searchTerm = searchTerm.toLowerCase();
        
        // Get the card title (most important search field)
        const titleElement = card.querySelector('.checkup-title, .test-title');
        if (!titleElement) return false;
        
        const title = titleElement.textContent.toLowerCase();
        
        // If title contains search term, this is a direct match
        if (title.includes(searchTerm)) {
            return true;
        }
        
        // Also search in other text content of the card
        const cardText = card.textContent.toLowerCase();
        return cardText.includes(searchTerm);
    }
    
    // Function to perform the search
    function performSearch() {
        const searchTerm = searchInput.value.trim();
        
        let cardsToSearch = [];
        let firstMatchedCard = null;
        
        // Determine which cards to search based on the current page
        if (isHomePage) {
            // On home page, search both test and checkup cards
            cardsToSearch = document.querySelectorAll('.checkup-card');
            
            // If search term is empty, reset the display of all cards
            if (!searchTerm) {
                resetSearch();
                return;
            }
        } else if (isTestsPage) {
            // On tests page, search test cards
            cardsToSearch = document.querySelectorAll('.test-card');
            
            // If search term is empty on tests page, delegate to the filter functionality
            if (!searchTerm) {
                if (typeof updateTestCards === 'function') {
                    updateTestCards();
                } else {
                    resetSearch();
                }
                return;
            }
        } else if (isCheckupsPage) {
            // On checkups page, search checkup cards
            cardsToSearch = document.querySelectorAll('.checkup-card');
            
            // If search term is empty on checkups page, delegate to the filter functionality
            if (!searchTerm) {
                if (typeof updateCheckupCards === 'function') {
                    updateCheckupCards();
                } else {
                    resetSearch();
                }
                return;
            }
        }
        
        let matchCount = 0;
        
        // Filter the cards based on search term
        cardsToSearch.forEach(card => {
            // Check if this card is already hidden by category/price filters (on tests or checkups page)
            const hiddenByFilter = window.getComputedStyle(card).display === 'none' && 
                                  searchTerm && (isTestsPage || isCheckupsPage);
            
            if (cardContainsSearchTerm(card, searchTerm)) {
                if (!hiddenByFilter) {
                    card.style.display = ''; // Show matching card (that's not hidden by filters)
                    matchCount++;
                    
                    // Keep track of the first matched card for scrolling
                    if (!firstMatchedCard) {
                        firstMatchedCard = card;
                    }
                }
            } else {
                card.style.display = 'none'; // Hide non-matching card
            }
        });
        
        // Show a message if no matches found
        updateNoResultsMessage(matchCount);
        
        // Scroll to the first matched card if available
        if (firstMatchedCard) {
            scrollToCard(firstMatchedCard);
        }
        
        // If we're on the home page and there are matches, also scroll to the relevant section
        if (isHomePage && firstMatchedCard) {
            // Determine if it's in tests or checkups section
            const isInTestsSection = firstMatchedCard.closest('#tests-slider-container');
            const isInCheckupsSection = firstMatchedCard.closest('#checkups-slider-container');
            
            if (isInTestsSection) {
                document.querySelector('.checkups-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
            } else if (isInCheckupsSection) {
                document.querySelectorAll('.checkups-section')[1].scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    }
    
    // Function to reset the search and show all cards
    function resetSearch() {
        let allCards;
        
        if (isHomePage) {
            allCards = document.querySelectorAll('.checkup-card');
        } else if (isTestsPage) {
            allCards = document.querySelectorAll('.test-card');
        } else if (isCheckupsPage) {
            allCards = document.querySelectorAll('.checkup-card');
        }
        
        allCards.forEach(card => {
            card.style.display = ''; // Show all cards
        });
        
        // Remove any "no results" message
        removeNoResultsMessage();
    }
    
    // Function to scroll to a specific card
    function scrollToCard(card) {
        // Smooth scroll to the matched card
        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    // Function to update or create the "no results" message
    function updateNoResultsMessage(matchCount) {
        removeNoResultsMessage(); // Remove any existing message
        
        if (matchCount === 0) {
            // Create and show "no results" message
            const noResultsMsg = document.createElement('div');
            noResultsMsg.id = 'no-results-message';
            noResultsMsg.style.textAlign = 'center';
            noResultsMsg.style.margin = '30px 0';
            noResultsMsg.style.padding = '20px';
            noResultsMsg.style.backgroundColor = '#f8f9fa';
            noResultsMsg.style.borderRadius = '8px';
            noResultsMsg.innerHTML = `
                <i class="fas fa-search" style="font-size: 40px; color: #ccc; margin-bottom: 10px;"></i>
                <h3 style="color: #333;">No results found</h3>
                <p style="color: #666;">Try a different search term or browse all items</p>
                <button id="clear-search" style="margin-top: 15px; padding: 8px 20px; background-color: #16A085; color: white; border: none; border-radius: 4px; cursor: pointer;">Clear Search</button>
            `;
            
            // Determine where to insert the message
            let container;
            
            if (isHomePage) {
                container = document.querySelector('.checkups-section .container');
            } else if (isTestsPage) {
                container = document.querySelector('.popular-tests-section .container');
            } else if (isCheckupsPage) {
                container = document.querySelector('.popular-tests-section .container');
            }
            
            if (container) {
                container.appendChild(noResultsMsg);
                
                // Add event listener to clear search button
                document.getElementById('clear-search').addEventListener('click', function() {
                    searchInput.value = '';
                    resetSearch();
                    
                    // If we're on tests or checkups page, trigger the appropriate filter update
                    if (isTestsPage && typeof updateTestCards === 'function') {
                        updateTestCards();
                    } else if (isCheckupsPage && typeof updateCheckupCards === 'function') {
                        updateCheckupCards();
                    }
                });
            }
        }
    }
    
    // Function to remove the "no results" message
    function removeNoResultsMessage() {
        const noResultsMsg = document.getElementById('no-results-message');
        if (noResultsMsg) {
            noResultsMsg.remove();
        }
    }
    
    // Handle the reset filter button if present
    const resetFilterBtn = document.querySelector('.btn-reset-filter');
    if (resetFilterBtn) {
        const originalClickHandlers = resetFilterBtn.onclick;
        
        resetFilterBtn.addEventListener('click', function() {
            searchInput.value = ''; // Clear search input
            removeNoResultsMessage(); // Remove any no results message
        });
    }
    
    // Search button in test filter section - make it redirect to the right page
    const searchButtons = document.querySelectorAll('.search-buttons .btn-search');
    if (searchButtons.length > 0) {
        searchButtons.forEach(button => {
            button.addEventListener('click', function() {
                const searchTerm = searchInput.value.trim();
                if (!searchTerm) return;
                
                const isTestButton = this.querySelector('i.fa-microscope');
                const isCheckupButton = this.querySelector('i.fa-box');
                
                if (isTestButton && !isTestsPage) {
                    window.location.href = `${isHomePage ? '' : '../'}tests.php?search=${encodeURIComponent(searchTerm)}`;
                } else if (isCheckupButton && !isCheckupsPage) {
                    window.location.href = `${isHomePage ? '' : '../'}checkups.php?search=${encodeURIComponent(searchTerm)}`;
                } else {
                    // If already on the right page, just perform the search
                    performSearch();
                }
            });
        });
    }
    
    // Check for search parameter in URL when page loads
    const urlParams = new URLSearchParams(window.location.search);
    const searchParam = urlParams.get('search');
    
    if (searchParam) {
        searchInput.value = searchParam;
        // Delay search to ensure page is fully loaded
        setTimeout(performSearch, 300);
    }
    
    // Define the missing updateTestCards and updateCheckupCards functions if needed
    if (isTestsPage && typeof updateTestCards === 'undefined') {
        window.updateTestCards = function() {
            const activeCategory = document.querySelector('.filter-btn.active')?.dataset.category || 'all';
            const maxPrice = parseInt(document.getElementById('price-slider')?.value || '5000');
            
            const testCards = document.querySelectorAll('.test-card');
            testCards.forEach(card => {
                const cardCategory = card.dataset.category;
                const cardPrice = parseInt(card.querySelector('.discounted-price')?.textContent.replace(/[^0-9]/g, '') || '0');
                
                let showCard = true;
                
                // Check category filter
                if (activeCategory !== 'all' && cardCategory !== activeCategory) {
                    showCard = false;
                }
                
                // Check price filter
                if (cardPrice > maxPrice) {
                    showCard = false;
                }
                
                card.style.display = showCard ? 'block' : 'none';
            });
        };
    }
    
    if (isCheckupsPage && typeof updateCheckupCards === 'undefined') {
        window.updateCheckupCards = function() {
            const activeCategory = document.querySelector('.filter-btn.active')?.dataset.category || 'all';
            const maxPrice = parseInt(document.getElementById('price-slider')?.value || '10000');
            
            const checkupCards = document.querySelectorAll('.checkup-card');
            checkupCards.forEach(card => {
                const cardCategory = card.dataset.category;
                const cardPrice = parseInt(card.querySelector('.discounted-price')?.textContent.replace(/[^0-9]/g, '') || '0');
                
                let showCard = true;
                
                // Check category filter
                if (activeCategory !== 'all' && cardCategory !== activeCategory) {
                    showCard = false;
                }
                
                // Check price filter
                if (cardPrice > maxPrice) {
                    showCard = false;
                }
                
                card.style.display = showCard ? 'block' : 'none';
            });
        };
    }
}); 