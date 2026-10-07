<?php
/**
 * Public Website - Cookie Policy Page
 * Explains cookie usage, Google AdSense tracking, analytics, and user consent controls.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = "Cookie Policy";
$metaDescription = "Learn how " . SITE_NAME . " uses cookies, local storage, and tracking technologies to ensure website security, analytics, and relevant advertising.";
$metaKeywords = "cookie policy, cookies, privacy, tracking, Google AdSense cookies, analytics, data protection, " . SITE_NAME;

include __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
    <div class="site-container">
        <h1>Cookie Policy & Conditions</h1>
        <p>Last Updated: <?= date('F j, Y') ?> &bull; Transparent guidance on how we utilize cookies and data</p>
    </div>
</section>

<section class="page-section">
    <div class="site-container" style="max-width: 1360px;">
        <div class="legal-content">
            <p class="article-lead" style="font-size:1.1rem; line-height:1.75; color:var(--text-secondary); margin-bottom:2rem;">
                This Cookie Policy explains how <strong><?= SITE_NAME ?></strong> ("we", "us", or "our") uses cookies, web beacons, tracking pixels, and similar browser storage technologies when you visit our website at <a href="<?= BASE_URL ?>"><?= BASE_URL ?></a>. This policy should be read alongside our <a href="<?= BASE_URL ?>/privacy-policy.php">Privacy Policy</a> and <a href="<?= BASE_URL ?>/terms.php">Terms and Conditions</a>.
            </p>

            <h2>1. What Are Cookies?</h2>
            <p>
                Cookies are small text files containing strings of letters and numbers that a website places on your computer, smartphone, or tablet when you visit. They are widely used by web developers to make websites work efficiently, protect user accounts, remember your preferences, and provide aggregated analytics to site administrators.
            </p>
            <p>
                Cookies may be either "persistent" cookies (which remain on your device until they expire or you delete them) or "session" cookies (which are deleted automatically once you close your web browser).
            </p>

            <h2>2. Why We Use Cookies & Tracking Technologies</h2>
            <p>
                <?= SITE_NAME ?> utilizes cookies for several essential and operational reasons:
            </p>
            <ul>
                <li><strong>Security & Authentication:</strong> Protecting visitors against Cross-Site Request Forgery (CSRF) attacks, authenticating administrative personnel, and preventing brute-force submission spam.</li>
                <li><strong>Site Preferences & Functionality:</strong> Remembering your chosen display preferences, consent choices, and recently filtered job categories.</li>
                <li><strong>Performance & Analytics:</strong> Measuring page load speeds, identifying popular career articles, and monitoring server health to ensure maximum uptime.</li>
                <li><strong>Advertising & Revenue:</strong> Supporting our 100% free educational mission through Google AdSense and authorized third-party ad networks by displaying non-intrusive, relevant career sponsorships.</li>
            </ul>

            <h2>3. Categories of Cookies We Deploy</h2>
            
            <h3>A. Strictly Necessary & Security Cookies (Mandatory)</h3>
            <p>
                These cookies are indispensable for the core operation of our platform. Without these cookies, services like secure contact forms, session security, and anti-spam verifications cannot function. Because they are strictly technical, they do not require prior consent under GDPR and ePrivacy regulations.
            </p>
            <ul>
                <li><code>PHPSESSID</code>: Manages encrypted session states and security handshakes between your browser and our web server. (Duration: Session)</li>
                <li><code>csrf_token</code>: Validates that form submissions originate from authentic users rather than malicious scripts. (Duration: Session)</li>
                <li><code>careernexaa_cookie_consent</code>: Stores your accepted cookie preferences so the notification banner does not repeat on every page load. (Duration: 1 Year)</li>
            </ul>

            <h3>B. Performance & Analytics Cookies</h3>
            <p>
                These cookies collect aggregated, anonymous data on how visitors interact with our job guides, articles, and navigation menus. This information helps us optimize reading layouts, fix broken links, and identify which employment sectors are in highest demand. All analytical data is anonymized and never linked to personally identifiable information.
            </p>

            <h3>C. Advertising & Google AdSense Cookies</h3>
            <p>
                To keep our career roadmaps, exam syllabi, and job listings free for all job seekers, we partner with <strong>Google AdSense</strong> and certified Google Ad Technology Providers.
            </p>
            <p>
                Google uses cookies (such as the <code>__gads</code> and <code>__gpi</code> cookies) to serve ads based on prior visits to our website or other websites on the Internet:
            </p>
            <ul>
                <li>Google's use of advertising cookies enables it and its partners to serve ads to users based on their visit to <?= SITE_NAME ?> and/or other sites on the Internet.</li>
                <li>Users may opt out of personalized advertising by visiting Google's official <a href="https://www.google.com/settings/ads" target="_blank" rel="noopener noreferrer">Ads Settings</a>.</li>
                <li>Alternatively, you can opt out of third-party vendor cookies for personalized advertising by visiting <a href="https://www.aboutads.info/choices/" target="_blank" rel="noopener noreferrer">AboutAds.info</a> or <a href="https://www.youronlinechoices.com/" target="_blank" rel="noopener noreferrer">Your Online Choices</a>.</li>
            </ul>

            <h2>4. Third-Party Cookies & Social Media Plugins</h2>
            <p>
                Some third-party tools embedded within our website may independently set cookies on your browser. For example, our social share buttons (Twitter / X, LinkedIn, Facebook) connect directly with those external platforms. We have no direct control over the cookies deployed by external social networks. Please review their respective privacy and cookie disclosures.
            </p>

            <h2>5. How You Can Control and Disable Cookies</h2>
            <p>
                You have the full right to accept, reject, or customize cookie usage on your device at any time.
            </p>
            <h3>A. Through Our On-Site Cookie Preferences</h3>
            <p>
                You can adjust your cookie settings on <?= SITE_NAME ?> at any time by clicking the <strong>"Cookie Settings"</strong> button in our website footer or in our consent banner.
            </p>
            <h3>B. Through Your Web Browser Settings</h3>
            <p>
                Most modern web browsers allow you to modify cookie preferences, block third-party cookies, or clear all stored cookies. Consult the official support resources for your browser:
            </p>
            <ul>
                <li><a href="https://support.google.com/chrome/answer/95647" target="_blank" rel="noopener noreferrer">Google Chrome Cookie Management</a></li>
                <li><a href="https://support.mozilla.org/en-US/kb/enhanced-tracking-protection-firefox-desktop" target="_blank" rel="noopener noreferrer">Mozilla Firefox Privacy & Cookies</a></li>
                <li><a href="https://support.apple.com/guide/safari/manage-cookies-sfri11471/mac" target="_blank" rel="noopener noreferrer">Apple Safari Cookie Controls</a></li>
                <li><a href="https://support.microsoft.com/en-us/microsoft-edge/delete-cookies-in-microsoft-edge-63947406-40ac-c3b8-57b9-2a946a29ae09" target="_blank" rel="noopener noreferrer">Microsoft Edge Cookie Settings</a></li>
            </ul>
            <p>
                <em>Please note that disabling strictly necessary cookies may impair website security, form submissions, and layout formatting.</em>
            </p>

            <h2>6. Policy Conditions & Revisions</h2>
            <p>
                We may revise this Cookie Policy periodically to reflect technological advancements, updated legal requirements under GDPR/CCPA, or changes in our advertising partnerships. The "Last Updated" date at the top of this page indicates the most recent modification. Continued use of <?= SITE_NAME ?> after modifications constitutes acceptance of the revised conditions.
            </p>

            <h2>7. Contact Our Data Protection Team</h2>
            <p>
                If you have questions, feedback, or concerns regarding our use of cookies or your digital privacy rights, please contact our editorial and compliance team:
            </p>
            <ul>
                <li><strong>Website Contact Page:</strong> <a href="<?= BASE_URL ?>/contact.php"><?= BASE_URL ?>/contact.php</a></li>
                <li><strong>Direct Privacy Inquiries:</strong> <a href="mailto:<?= SITE_EMAIL ?>"><?= SITE_EMAIL ?></a></li>
                <li><strong>Operating Organization:</strong> <?= SITE_NAME ?> Digital Editorial Desk</li>
            </ul>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
