<?php
/**
 * Modern Cookie Consent & Conditions Banner
 * GDPR, CCPA & Google AdSense compliant interactive privacy notice.
 */
?>
<div id="cookieConsentBanner" class="cookie-consent-banner" role="dialog" aria-live="polite" aria-label="Cookie and Privacy Consent">
    <div class="cookie-consent-inner">
        <div class="cookie-icon-wrapper">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5"></path>
                <path d="M8.5 8.5v.01"></path>
                <path d="M16 15.5v.01"></path>
                <path d="M12 12v.01"></path>
                <path d="M11 17v.01"></path>
                <path d="M7 13v.01"></path>
            </svg>
        </div>
        
        <div class="cookie-text-content">
            <h4 class="cookie-heading">Cookie Policy & Conditions of Use</h4>
            <p class="cookie-desc">
                We use strictly necessary cookies to ensure site security, plus optional analytical and advertising cookies to deliver verified job alerts and relevant career recommendations. By clicking <strong>"Accept All"</strong>, you consent to our tracking conditions in accordance with our 
                <a href="<?= BASE_URL ?>/cookie-policy" class="cookie-link">Cookie Policy</a> and 
                <a href="<?= BASE_URL ?>/terms" class="cookie-link">Terms & Conditions</a>.
            </p>
        </div>

        <div class="cookie-actions">
            <button type="button" class="cookie-btn cookie-btn-accept" onclick="handleCookieConsent('all')">
                Accept All
            </button>
            <button type="button" class="cookie-btn cookie-btn-reject" onclick="handleCookieConsent('essential')">
                Essential Only
            </button>
            <a href="<?= BASE_URL ?>/cookie-policy" class="cookie-link-details">
                Preferences &rarr;
            </a>
        </div>
    </div>
</div>

<script>
(function() {
    function initCookieBanner() {
        var banner = document.getElementById('cookieConsentBanner');
        if (!banner) return;
        
        var consent = localStorage.getItem('careernexaa_cookie_consent');
        if (!consent) {
            setTimeout(function() {
                banner.classList.add('is-visible');
            }, 700);
        }
    }

    window.handleCookieConsent = function(type) {
        localStorage.setItem('careernexaa_cookie_consent', type);
        var banner = document.getElementById('cookieConsentBanner');
        if (banner) {
            banner.classList.remove('is-visible');
        }
    };

    window.showCookieBanner = function() {
        var banner = document.getElementById('cookieConsentBanner');
        if (banner) {
            banner.classList.add('is-visible');
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCookieBanner);
    } else {
        initCookieBanner();
    }
})();
</script>
