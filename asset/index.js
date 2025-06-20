 console.log('COMIC BYTE Short Comics Dashboard Loaded');

        // Dark mode toggle functionality - Synced across all toggle buttons
        const themeToggleHeader = document.getElementById('theme-toggle-header');
        const themeToggleFooter = document.getElementById('theme-toggle-footer');
        const html = document.documentElement;

        // Check for saved theme preference or default to light mode
        const currentTheme = localStorage.getItem('theme') || 'light';
        html.classList.toggle('dark', currentTheme === 'dark');

        function toggleTheme() {
            html.classList.toggle('dark');
            const theme = html.classList.contains('dark') ? 'dark' : 'light';
            localStorage.setItem('theme', theme);
        }

        // Add event listeners to both theme toggle buttons
        themeToggleHeader.addEventListener('click', toggleTheme);
        themeToggleFooter.addEventListener('click', toggleTheme);

        // Mobile sidebar functionality
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        const closeSidebar = document.getElementById('close-sidebar');

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            sidebarOverlay.classList.remove('hidden');
        }

        function closeSidebarFn() {
            sidebar.classList.add('-translate-x-full');
            sidebarOverlay.classList.add('hidden');
            // Also close the user dropdown if it exists
            const sidebarUserDropdown = document.getElementById('sidebar-user-dropdown');
            const sidebarUserMenuArrow = document.getElementById('sidebar-user-menu-arrow');
            if (sidebarUserDropdown) {
                sidebarUserDropdown.classList.add('hidden');
            }
            if (sidebarUserMenuArrow) {
                sidebarUserMenuArrow.classList.remove('rotate-180');
            }
        }

        mobileMenuBtn.addEventListener('click', openSidebar);
        if (closeSidebar) {
            closeSidebar.addEventListener('click', closeSidebarFn);
        }
        sidebarOverlay.addEventListener('click', closeSidebarFn);

        // Sidebar user menu dropdown functionality (only if user is logged in)
        const sidebarUserMenuBtn = document.getElementById('sidebar-user-menu-btn');
        const sidebarUserDropdown = document.getElementById('sidebar-user-dropdown');
        const sidebarUserMenuArrow = document.getElementById('sidebar-user-menu-arrow');

        if (sidebarUserMenuBtn && sidebarUserDropdown && sidebarUserMenuArrow) {
            sidebarUserMenuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const isHidden = sidebarUserDropdown.classList.contains('hidden');
                sidebarUserDropdown.classList.toggle('hidden', !isHidden);
                sidebarUserMenuArrow.classList.toggle('rotate-180', isHidden);
            });

            // Close sidebar dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!sidebarUserMenuBtn.contains(e.target) && !sidebarUserDropdown.contains(e.target)) {
                    sidebarUserDropdown.classList.add('hidden');
                    sidebarUserMenuArrow.classList.remove('rotate-180');
                }
            });
        }

        // Navigation functionality - Update breadcrumb based on clicked sidebar item
        // Navigation functionality - Update breadcrumb based on clicked sidebar item
const navLinks = document.querySelectorAll('.nav-link');
const currentPageElement = document.getElementById('current-page');

navLinks.forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        
        // Get the page name from the link
        const pageUrl = link.getAttribute('href');
        
        // Update the URL without reloading (for SPA-like behavior)
        history.pushState(null, null, pageUrl);
        
        // Update the active state
        updateActiveLink(link);
        
        // Load the content (you would typically use AJAX here, but for simplicity we'll reload)
        window.location.href = pageUrl;
        
        // Close sidebar on mobile after navigation
        if (window.innerWidth < 1024) {
            closeSidebarFn();
        }
    });
});

function updateActiveLink(activeLink) {
    // Remove active class from all nav links
    navLinks.forEach(l => {
        l.classList.remove('bg-sidebar-hover', 'text-white');
        l.classList.add('text-gray-300', 'hover:bg-sidebar-hover', 'hover:text-white');
    });

    // Add active class to clicked link
    activeLink.classList.add('bg-sidebar-hover', 'text-white');
    activeLink.classList.remove('text-gray-300', 'hover:bg-sidebar-hover', 'hover:text-white');

    // Update breadcrumb
    const pageName = activeLink.textContent.trim();
    if (currentPageElement) {
        currentPageElement.textContent = pageName;
    }
}

// Handle browser back/forward buttons
window.addEventListener('popstate', function() {
    // Get the current page from the URL
    const urlParams = new URLSearchParams(window.location.search);
    const currentPage = urlParams.get('page') || 'dashboard';
    
    // Find the corresponding link and update the UI
    const activeLink = document.querySelector(`.nav-link[data-page="${currentPage}"]`);
    if (activeLink) {
        updateActiveLink(activeLink);
    }
});