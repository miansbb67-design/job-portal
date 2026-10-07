    </main>
    <!-- Main Content Area Ends -->

<?php
$footerSiteName = get_site_name();
$footerLogoUrl  = get_site_logo_url();
$footerAboutText = get_setting('site_footer_text', $footerSiteName . ' is your trusted portal for verified job vacancies, competitive exam guides, and career growth strategies.');
$footerCopyrightText = get_setting('site_copyright', '© ' . date('Y') . ' ' . $footerSiteName . '. All rights reserved.');
?>
    <!-- Site Footer -->
    <?php if (is_section_visible('footer')): ?>
    <footer class="site-footer">
        <div class="site-container">
            <div class="footer-top-grid">
                <!-- Col 1: Brand & Mission -->
                <div class="footer-col brand-col">
                    <a href="<?= BASE_URL ?>/" class="site-brand footer-logo" style="display:inline-flex; align-items:center; text-decoration:none;">
                        <?php if (!empty($footerLogoUrl)): ?>
                            <img src="<?= h($footerLogoUrl) ?>" alt="<?= h($footerSiteName) ?>" style="max-height:38px; width:auto; object-fit:contain; display:block;">
                        <?php else: ?>
                            <div class="brand-icon">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                                </svg>
                            </div>
                            <span class="brand-name"><?= format_brand_name($footerSiteName) ?></span>
                        <?php endif; ?>
                    </a>
                    <p class="footer-text">
                        <?= h($footerAboutText) ?>
                    </p>
                    <div class="footer-badges">
                        <span class="trust-badge">&#10003; 100% Free Career Resource</span>
                        <span class="trust-badge">&#10003; Verified Job Openings</span>
                    </div>
                </div>

                <!-- Col 2: Navigation -->
                <div class="footer-col">
                    <h3 class="footer-heading">Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="<?= BASE_URL ?>/">Home</a></li>
                        <li><a href="<?= BASE_URL ?>/articles.php">All Jobs & Articles</a></li>
                        <li><a href="<?= BASE_URL ?>/categories.php">Job Categories</a></li>
                        <li><a href="<?= BASE_URL ?>/about.php">About Us</a></li>
                        <li><a href="<?= BASE_URL ?>/contact.php">Contact Us</a></li>
                    </ul>
                </div>

                <!-- Col 3: Categories -->
                <div class="footer-col">
                    <h3 class="footer-heading">Top Categories</h3>
                    <ul class="footer-links">
                        <?php 
                        $footerCats = array_slice($headerCategories, 0, 5);
                        foreach ($footerCats as $fcat): 
                        ?>
                            <li>
                                <a href="<?= BASE_URL ?>/categories.php?slug=<?= h($fcat['slug']) ?>">
                                    <?= h($fcat['name']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Col 4: Legal & Policy Compliance -->
                <div class="footer-col">
                    <h3 class="footer-heading">Policy & Legal</h3>
                    <p class="footer-disclaimer-snippet">
                        Committed to transparent hiring alerts and verified career advice. Review our policies:
                    </p>
                    <ul class="footer-links legal-links">
                        <li><a href="<?= BASE_URL ?>/privacy-policy.php">Privacy Policy</a></li>
                        <li><a href="<?= BASE_URL ?>/cookie-policy.php">Cookie Policy</a></li>
                        <li><a href="<?= BASE_URL ?>/terms.php">Terms of Service</a></li>
                        <li><a href="<?= BASE_URL ?>/disclaimer.php">Editorial Disclaimer</a></li>
                        <li><a href="<?= BASE_URL ?>/contact.php">Report an Issue</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-divider"></div>

            <div class="footer-bottom">
                <p class="copyright-text">
                    <?= h($footerCopyrightText) ?>
                </p>
                <div class="footer-legal-inline">
                    <a href="<?= BASE_URL ?>/privacy-policy.php">Privacy</a> &bull; 
                    <a href="<?= BASE_URL ?>/cookie-policy.php">Cookies</a> &bull; 
                    <a href="<?= BASE_URL ?>/terms.php">Terms</a> &bull; 
                    <a href="<?= BASE_URL ?>/disclaimer.php">Disclaimer</a> &bull; 
                    <button type="button" onclick="showCookieBanner()" class="cookie-manage-btn">Cookie Settings</button>
                </div>
            </div>
        </div>
    </footer>
    <?php endif; ?>

    <!-- Back To Top Button -->
    <button id="backToTop" class="back-to-top" aria-label="Back to top of page">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"></polyline></svg>
    </button>

    <!-- Cookie Consent & Privacy Conditions Banner -->
    <?php include __DIR__ . '/cookie-consent.php'; ?>

    <!-- Main JavaScript File -->
    <script src="<?= BASE_URL ?>/assets/js/main.js?v=<?= filemtime(__DIR__ . '/../assets/js/main.js') ?>"></script>
</body>
</html>
