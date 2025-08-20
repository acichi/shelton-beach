/**
 * Enhanced Navbar JavaScript
 * Provides advanced functionality for the navigation bar
 */

(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        scrollThreshold: 50,
        debounceDelay: 100,
        animationDuration: 300,
        searchMinLength: 2,
        searchDebounce: 300
    };

    // DOM Elements
    const elements = {
        navbar: null,
        navLinks: null,
        searchInput: null,
        searchBtn: null,
        darkModeToggle: null,
        notificationBadge: null,
        languageSelector: null
    };

    // State
    let state = {
        isScrolled: false,
        isDarkMode: false,
        searchQuery: '',
        notifications: 0,
        currentLanguage: 'en'
    };

    // Initialize
    function init() {
        cacheElements();
        bindEvents();
        initializeFeatures();
        loadUserPreferences();
    }

    // Cache DOM elements
    function cacheElements() {
        elements.navbar = document.querySelector('.navbar-enhanced');
        elements.navLinks = document.querySelectorAll('.navbar-nav-enhanced .nav-link');
        elements.searchInput = document.querySelector('.search-input');
        elements.searchBtn = document.querySelector('.search-btn');
        elements.darkModeToggle = document.querySelector('.dark-mode-toggle');
        elements.notificationBadge = document.querySelector('.notification-badge');
        elements.languageSelector = document.querySelector('.language-selector');
    }

    // Bind events
    function bindEvents() {
        // Scroll events
        window.addEventListener('scroll', debounce(handleScroll, CONFIG.debounceDelay));
        
        // Navigation events
        elements.navLinks.forEach(link => {
            link.addEventListener('click', handleNavLinkClick);
        });
        
        // Search functionality
        if (elements.searchInput) {
            elements.searchInput.addEventListener('input', debounce(handleSearch, CONFIG.searchDebounce));
            elements.searchInput.addEventListener('keydown', handleSearchKeydown);
        }
        
        if (elements.searchBtn) {
            elements.searchBtn.addEventListener('click', handleSearchSubmit);
        }
        
        // Dark mode toggle
        if (elements.darkModeToggle) {
            elements.darkModeToggle.addEventListener('click', toggleDarkMode);
        }
        
        // Language selector
        if (elements.languageSelector) {
            elements.languageSelector.addEventListener('change', handleLanguageChange);
        }
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', handleSmoothScroll);
        });
        
        // Keyboard navigation
        document.addEventListener('keydown', handleKeyboardNavigation);
        
        // Resize events
        window.addEventListener('resize', debounce(handleResize, CONFIG.debounceDelay));
    }

    // Initialize features
    function initializeFeatures() {
        handleScroll();
        updateActiveNavLink();
        initializeSearch();
        initializeNotifications();
        updateMobileMenuPosition();
        
        // Add mobile menu close on outside click
        document.addEventListener('click', handleOutsideClick);
        
        // Add smooth mobile menu transitions
        initializeMobileMenuTransitions();
        
        // Initialize dropdowns
        initializeDropdowns();
        
        // Initialize logged-in state enhancements
        initializeLoggedInState();
    }

    // Handle scroll events
    function handleScroll() {
        const scrollY = window.scrollY;
        const shouldBeScrolled = scrollY > CONFIG.scrollThreshold;
        
        if (shouldBeScrolled !== state.isScrolled) {
            state.isScrolled = shouldBeScrolled;
            elements.navbar.classList.toggle('scrolled', state.isScrolled);
        }
    }

    // Handle navigation link clicks
    function handleNavLinkClick(e) {
        const href = this.getAttribute('href');
        
        // Handle external links
        if (href.startsWith('http') || href.startsWith('//')) {
            return;
        }
        
        // Handle anchor links - only if href is not just '#'
        if (href.startsWith('#') && href !== '#') {
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                smoothScrollTo(target);
            }
        }
        
        updateActiveNavLink();
    }

    // Update active navigation link
    function updateActiveNavLink() {
        const sections = document.querySelectorAll('section[id]');
        const scrollPos = window.scrollY + 100;
        
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            const sectionId = section.getAttribute('id');
            
            if (scrollPos >= sectionTop && scrollPos < sectionTop + sectionHeight) {
                elements.navLinks.forEach(link => {
                    link.classList.remove('active');
                    const href = link.getAttribute('href');
                    if (href && href === `#${sectionId}`) {
                        link.classList.add('active');
                    }
                });
            }
        });
    }

    // Search functionality
    function initializeSearch() {
        if (!elements.searchInput) return;
        
        elements.searchInput.setAttribute('placeholder', 'Search...');
        elements.searchInput.setAttribute('aria-label', 'Search');
    }

    function handleSearch(e) {
        const query = e.target.value.trim();
        
        if (query.length >= CONFIG.searchMinLength) {
            state.searchQuery = query;
            performSearch(query);
        } else {
            clearSearchResults();
        }
    }

    function handleSearchKeydown(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            handleSearchSubmit();
        }
    }

    function handleSearchSubmit() {
        const query = elements.searchInput.value.trim();
        if (query.length >= CONFIG.searchMinLength) {
            performSearch(query);
        }
    }

    function performSearch(query) {
        // Implement search functionality
        console.log('Searching for:', query);
        
        // Example: Filter navigation items
        const navItems = document.querySelectorAll('.navbar-nav-enhanced .nav-link');
        navItems.forEach(item => {
            const text = item.textContent.toLowerCase();
            const isMatch = text.includes(query.toLowerCase());
            item.style.display = isMatch ? 'flex' : 'none';
        });
        
        // Add search results to DOM
        displaySearchResults(query);
    }

    function displaySearchResults(query) {
        // Create search results container
        let resultsContainer = document.querySelector('.search-results');
        if (!resultsContainer) {
            resultsContainer = document.createElement('div');
            resultsContainer.className = 'search-results';
            resultsContainer.style.cssText = `
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                border-radius: 10px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.15);
                max-height: 300px;
                overflow-y: auto;
                z-index: 1001;
                display: none;
            `;
            elements.navbar.appendChild(resultsContainer);
        }
        
        // Populate results
        resultsContainer.innerHTML = `
            <div style="padding: 1rem;">
                <h6 style="margin: 0 0 0.5rem 0;">Search Results</h6>
                <p style="margin: 0; color: #666;">Searching for: ${query}</p>
            </div>
        `;
        
        resultsContainer.style.display = 'block';
    }

    function clearSearchResults() {
        const resultsContainer = document.querySelector('.search-results');
        if (resultsContainer) {
            resultsContainer.style.display = 'none';
        }
        
        // Reset navigation items
        const navItems = document.querySelectorAll('.navbar-nav-enhanced .nav-link');
        navItems.forEach(item => {
            item.style.display = 'flex';
        });
    }

    // Dark mode functionality
    function toggleDarkMode() {
        state.isDarkMode = !state.isDarkMode;
        document.body.classList.toggle('dark-mode', state.isDarkMode);
        localStorage.setItem('darkMode', state.isDarkMode);
        
        // Update toggle button
        if (elements.darkModeToggle) {
            elements.darkModeToggle.innerHTML = state.isDarkMode ? 
                '<i class="fas fa-sun"></i>' : 
                '<i class="fas fa-moon"></i>';
        }
    }

    // Language change handler
    function handleLanguageChange(e) {
        state.currentLanguage = e.target.value;
        localStorage.setItem('language', state.currentLanguage);
        
        // Implement language change
        console.log('Language changed to:', state.currentLanguage);
        
        // Example: Update text content
        updateLanguageContent(state.currentLanguage);
    }

    function updateLanguageContent(language) {
        const translations = {
            en: {
                search: 'Search',
                home: 'Home',
                about: 'About',
                services: 'Services',
                gallery: 'Gallery',
                contact: 'Contact'
            },
            es: {
                search: 'Buscar',
                home: 'Inicio',
                about: 'Acerca de',
                services: 'Servicios',
                gallery: 'Galería',
                contact: 'Contacto'
            }
        };
        
        const texts = translations[language] || translations.en;
        
        // Update search placeholder
        if (elements.searchInput) {
            elements.searchInput.setAttribute('placeholder', texts.search);
        }
        
        // Update navigation links
        elements.navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href && href === 'index.php') link.innerHTML = `<i class="fas fa-home"></i> ${texts.home}`;
            if (href && href === '#about-section') link.innerHTML = `<i class="fas fa-info-circle"></i> ${texts.about}`;
            if (href && href === '#offer') link.innerHTML = `<i class="fas fa-concierge-bell"></i> ${texts.services}`;
            if (href && href === '#gall') link.innerHTML = `<i class="fas fa-images"></i> ${texts.gallery}`;
            if (href && href === '#us') link.innerHTML = `<i class="fas fa-map-marker-alt"></i> ${texts.contact}`;
        });
    }

    // Smooth scroll functionality
    function handleSmoothScroll(e) {
        e.preventDefault();
        const href = this.getAttribute('href');
        
        // Only proceed if href is not just '#'
        if (href && href !== '#') {
            const target = document.querySelector(href);
            if (target) {
                smoothScrollTo(target);
            }
        }
    }

    function smoothScrollTo(target) {
        const navbarHeight = elements.navbar.offsetHeight;
        const targetPosition = target.offsetTop - navbarHeight - 20;
        
        window.scrollTo({
            top: targetPosition,
            behavior: 'smooth'
        });
    }

    // Keyboard navigation
    function handleKeyboardNavigation(e) {
        // Handle escape key to close mobile menu
        if (e.key === 'Escape') {
            const navbarCollapse = document.querySelector('.navbar-collapse');
            if (navbarCollapse && navbarCollapse.classList.contains('show')) {
                const navbarToggler = document.querySelector('.navbar-toggler-enhanced');
                if (navbarToggler) {
                    navbarToggler.click();
                }
            }
        }
    }

    // Handle resize events
    function handleResize() {
        // Close mobile menu on resize to desktop
        if (window.innerWidth >= 992) {
            const navbarCollapse = document.querySelector('.navbar-collapse');
            if (navbarCollapse && navbarCollapse.classList.contains('show')) {
                const navbarToggler = document.querySelector('.navbar-toggler-enhanced');
                if (navbarToggler) {
                    navbarToggler.click();
                }
            }
        }
        
        // Update mobile menu positioning
        updateMobileMenuPosition();
    }
    
    // Update mobile menu positioning for better UX
    function updateMobileMenuPosition() {
        const navbar = document.querySelector('.navbar-enhanced');
        const navbarCollapse = document.querySelector('.navbar-collapse');
        
        if (navbar && navbarCollapse) {
            const navbarHeight = navbar.offsetHeight;
            navbarCollapse.style.top = `${navbarHeight}px`;
        }
    }
    
    // Handle clicks outside mobile menu to close it
    function handleOutsideClick(e) {
        const navbar = document.querySelector('.navbar-enhanced');
        const navbarToggler = document.querySelector('.navbar-toggler-enhanced');
        const navbarCollapse = document.querySelector('.navbar-collapse');
        
        if (navbar && navbarCollapse && navbarCollapse.classList.contains('show')) {
            if (!navbar.contains(e.target) && !navbarToggler.contains(e.target)) {
                const bsCollapse = new bootstrap.Collapse(navbarCollapse, {
                    toggle: false
                });
                bsCollapse.hide();
            }
        }
    }
    
    // Initialize smooth mobile menu transitions
    function initializeMobileMenuTransitions() {
        const navbarCollapse = document.querySelector('.navbar-collapse');
        if (navbarCollapse) {
            // Add transition classes for smooth animations
            navbarCollapse.addEventListener('show.bs.collapse', function() {
                this.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
            });
            
            navbarCollapse.addEventListener('hide.bs.collapse', function() {
                this.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
            });
            
            // Smooth scroll to top when opening mobile menu
            navbarCollapse.addEventListener('shown.bs.collapse', function() {
                if (window.innerWidth < 992) {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                }
            });
        }
    }
    
    // Initialize logged-in state enhancements
    function initializeLoggedInState() {
        const navbar = document.querySelector('.navbar-enhanced');
        const userProfileLink = document.querySelector('.user-profile-link');
        
        if (navbar && navbar.classList.contains('logged-in') && userProfileLink) {
            // Add welcome animation
            setTimeout(() => {
                userProfileLink.style.animation = 'welcomePulse 2s ease-in-out';
            }, 1000);
            
            // Initialize Bootstrap dropdowns
            initializeDropdowns();
            
            // Enhanced dropdown behavior for logged-in users
            const dropdownMenu = userProfileLink.nextElementSibling;
            if (dropdownMenu) {
                dropdownMenu.addEventListener('show.bs.dropdown', function() {
                    // Add staggered animation to dropdown items
                    const items = this.querySelectorAll('.dropdown-item-enhanced');
                    items.forEach((item, index) => {
                        item.style.animationDelay = `${index * 0.1}s`;
                        item.style.animation = 'dropdownItemSlideIn 0.5s ease forwards';
                    });
                });
                
                dropdownMenu.addEventListener('hide.bs.dropdown', function() {
                    // Reset animations
                    const items = this.querySelectorAll('.dropdown-item-enhanced');
                    items.forEach(item => {
                        item.style.animation = '';
                        item.style.animationDelay = '';
                    });
                });
            }
            
            // Add user activity indicator
            addUserActivityIndicator();
        }
        
        // Add reservation link enhancement for guests
        initializeReservationLinkEnhancement();
    }
    
    // Initialize Bootstrap dropdowns
    function initializeDropdowns() {
        // Check if Bootstrap is available
        if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
            // Initialize all dropdowns
            const dropdownElementList = document.querySelectorAll('.dropdown-toggle');
            dropdownElementList.forEach(dropdownToggleEl => {
                new bootstrap.Dropdown(dropdownToggleEl, {
                    autoClose: true,
                    boundary: 'viewport'
                });
            });
            
            // Add custom dropdown behavior
            const dropdowns = document.querySelectorAll('.dropdown');
            dropdowns.forEach(dropdown => {
                const toggle = dropdown.querySelector('.dropdown-toggle');
                const menu = dropdown.querySelector('.dropdown-menu-enhanced');
                
                if (toggle && menu) {
                    // Handle click events
                    toggle.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        const isOpen = menu.classList.contains('show');
                        
                        // Close all other dropdowns
                        document.querySelectorAll('.dropdown-menu-enhanced.show').forEach(openMenu => {
                            if (openMenu !== menu) {
                                openMenu.classList.remove('show');
                            }
                        });
                        
                        // Toggle current dropdown
                        if (isOpen) {
                            menu.classList.remove('show');
                            toggle.setAttribute('aria-expanded', 'false');
                        } else {
                            menu.classList.add('show');
                            toggle.setAttribute('aria-expanded', 'true');
                        }
                    });
                    
                    // Close dropdown when clicking outside
                    document.addEventListener('click', function(e) {
                        if (!dropdown.contains(e.target)) {
                            menu.classList.remove('show');
                            toggle.setAttribute('aria-expanded', 'false');
                        }
                    });
                    
                    // Close dropdown on escape key
                    document.addEventListener('keydown', function(e) {
                        if (e.key === 'Escape' && menu.classList.contains('show')) {
                            menu.classList.remove('show');
                            toggle.setAttribute('aria-expanded', 'false');
                        }
                    });
                }
            });
        } else {
            // Fallback dropdown functionality if Bootstrap is not available
            initializeFallbackDropdowns();
        }
    }
    
    // Fallback dropdown functionality
    function initializeFallbackDropdowns() {
        const dropdowns = document.querySelectorAll('.dropdown');
        dropdowns.forEach(dropdown => {
            const toggle = dropdown.querySelector('.dropdown-toggle');
            const menu = dropdown.querySelector('.dropdown-menu-enhanced');
            
            if (toggle && menu) {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const isOpen = menu.classList.contains('show');
                    
                    // Close all other dropdowns
                    document.querySelectorAll('.dropdown-menu-enhanced.show').forEach(openMenu => {
                        if (openMenu !== menu) {
                            openMenu.classList.remove('show');
                        }
                    });
                    
                    // Toggle current dropdown
                    if (isOpen) {
                        menu.classList.remove('show');
                        toggle.setAttribute('aria-expanded', 'false');
                    } else {
                        menu.classList.add('show');
                        toggle.setAttribute('aria-expanded', 'true');
                    }
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!dropdown.contains(e.target)) {
                        menu.classList.remove('show');
                        toggle.setAttribute('aria-expanded', 'false');
                    }
                });
                
                // Close dropdown on escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && menu.classList.contains('show')) {
                        menu.classList.remove('show');
                        toggle.setAttribute('aria-expanded', 'false');
                    }
                });
            }
        });
    }
    
    // Initialize reservation link enhancement for guests
    function initializeReservationLinkEnhancement() {
        // Functionality removed - no more notification popup
        // Users can now click the Reserve link normally
    }
    
    // Add user activity indicator for logged-in users
    function addUserActivityIndicator() {
        const userProfileLink = document.querySelector('.user-profile-link');
        if (userProfileLink) {
            // Add online status indicator
            const onlineIndicator = document.createElement('div');
            onlineIndicator.className = 'online-indicator';
            onlineIndicator.innerHTML = '<span class="pulse"></span>';
            onlineIndicator.style.cssText = `
                position: absolute;
                top: 5px;
                right: 5px;
                width: 8px;
                height: 8px;
                background: #4CAF50;
                border-radius: 50%;
                border: 2px solid white;
                box-shadow: 0 0 10px rgba(76, 175, 80, 0.5);
            `;
            
            userProfileLink.appendChild(onlineIndicator);
            
            // Add pulse animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes welcomePulse {
                    0%, 100% { transform: scale(1); }
                    50% { transform: scale(1.05); }
                }
                
                @keyframes dropdownItemSlideIn {
                    from {
                        opacity: 0;
                        transform: translateX(-20px);
                    }
                    to {
                        opacity: 1;
                        transform: translateX(0);
                    }
                }
                
                .online-indicator .pulse {
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    border-radius: 50%;
                    background: #4CAF50;
                    animation: pulse 2s infinite;
                }
                
                @keyframes pulse {
                    0% {
                        transform: scale(1);
                        opacity: 1;
                    }
                    100% {
                        transform: scale(2);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        }
    }

    // Notifications
    function initializeNotifications() {
        // Example: Fetch notifications from server
        fetchNotifications();
    }

    function fetchNotifications() {
        // Simulate API call
        setTimeout(() => {
            state.notifications = 3; // Example: 3 new notifications
            updateNotificationBadge();
        }, 2000);
    }

    function updateNotificationBadge() {
        if (elements.notificationBadge) {
            elements.notificationBadge.textContent = state.notifications;
            elements.notificationBadge.style.display = state.notifications > 0 ? 'flex' : 'none';
        }
    }

    // User preferences
    function loadUserPreferences() {
        // Load dark mode preference
        const savedDarkMode = localStorage.getItem('darkMode');
        if (savedDarkMode !== null) {
            state.isDarkMode = savedDarkMode === 'true';
            document.body.classList.toggle('dark-mode', state.isDarkMode);
            if (elements.darkModeToggle) {
                elements.darkModeToggle.innerHTML = state.isDarkMode ? 
                    '<i class="fas fa-sun"></i>' : 
                    '<i class="fas fa-moon"></i>';
            }
        }

        // Load language preference
        const savedLanguage = localStorage.getItem('language');
        if (savedLanguage) {
            state.currentLanguage = savedLanguage;
            if (elements.languageSelector) {
                elements.languageSelector.value = savedLanguage;
            }
            updateLanguageContent(savedLanguage);
        }
    }

    // Utility functions
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Public API
    window.NavbarEnhanced = {
        updateNotifications: function(count) {
            state.notifications = count;
            updateNotificationBadge();
        },
        setLanguage: function(language) {
            state.currentLanguage = language;
            updateLanguageContent(language);
        },
        toggleDarkMode: toggleDarkMode
    };

})();
