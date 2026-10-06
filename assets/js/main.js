/**
 * CareerPulse - Main Frontend JavaScript (Vanilla JS)
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Menu Toggle
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const mobileNavMenu = document.getElementById('mobileNavMenu');

    if (mobileMenuToggle && mobileNavMenu) {
        mobileMenuToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            const isHidden = mobileNavMenu.hasAttribute('hidden');
            if (isHidden) {
                mobileNavMenu.removeAttribute('hidden');
                mobileMenuToggle.setAttribute('aria-expanded', 'true');
            } else {
                mobileNavMenu.setAttribute('hidden', '');
                mobileMenuToggle.setAttribute('aria-expanded', 'false');
            }
        });

        // Close on clicking outside
        document.addEventListener('click', (e) => {
            if (!mobileNavMenu.contains(e.target) && !mobileMenuToggle.contains(e.target) && !mobileNavMenu.hasAttribute('hidden')) {
                mobileNavMenu.setAttribute('hidden', '');
                mobileMenuToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // 1b. Mobile Search Toggle
    const mobileSearchBtn = document.getElementById('mobileSearchBtn');
    const mobileSearchBar = document.getElementById('mobileSearchBar');
    if (mobileSearchBtn && mobileSearchBar) {
        mobileSearchBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isHidden = mobileSearchBar.hasAttribute('hidden');
            if (isHidden) {
                mobileSearchBar.removeAttribute('hidden');
                const input = mobileSearchBar.querySelector('input');
                if (input) input.focus();
            } else {
                mobileSearchBar.setAttribute('hidden', '');
            }
        });
    }

    // 1c. Topics Dropdown Click (for Touch Devices)
    const topicsMenuBtn = document.getElementById('topicsMenuBtn');
    const topicsMenuDropdown = document.getElementById('topicsMenuDropdown');
    const topicsDropdownGroup = document.querySelector('.pine-dropdown-group');
    if (topicsMenuBtn && topicsMenuDropdown && topicsDropdownGroup) {
        topicsMenuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = topicsDropdownGroup.classList.toggle('is-open');
            topicsMenuBtn.setAttribute('aria-expanded', isOpen);
        });

        document.addEventListener('click', (e) => {
            if (!topicsDropdownGroup.contains(e.target)) {
                topicsDropdownGroup.classList.remove('is-open');
                topicsMenuBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // 2. Back To Top Button
    const backToTopBtn = document.getElementById('backToTop');
    if (backToTopBtn) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 350) {
                backToTopBtn.classList.add('show');
            } else {
                backToTopBtn.classList.remove('show');
            }
        });

        backToTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    // 3. Search Form Client-Side Validation
    const searchForms = document.querySelectorAll('form[role="search"], .hero-search-box form');
    searchForms.forEach(form => {
        form.addEventListener('submit', (e) => {
            const input = form.querySelector('input[name="q"]');
            if (input && input.value.trim() === '') {
                e.preventDefault();
                input.focus();
            }
        });
    });

    // 4. Auto Dismiss Flash Alerts after 6 seconds
    const flashAlerts = document.querySelectorAll('.alert');
    flashAlerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.4s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 400);
        }, 6000);
    });
});
