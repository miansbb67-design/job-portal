<?php
/**
 * Public Website - Privacy Policy Page
 * Fully compliant with Google AdSense, GDPR, CCPA, and digital privacy regulations.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = "Privacy Policy";
$metaDescription = "Privacy Policy for CareerNexaa explaining cookies, Google AdSense third-party vendor advertising, log files, and user data rights.";
$metaKeywords = "privacy policy, data protection, GDPR, CCPA, cookies, Google AdSense, " . SITE_NAME;
$canonicalUrl = BASE_URL . '/privacy-policy';

include __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
    <div class="site-container">
        <h1>Privacy Policy</h1>
        <p>Last updated: October 2026. Your privacy and transparency are our highest priority.</p>
    </div>
</section>

<section class="page-section">
    <div class="site-container" style="max-width: 1360px;">
        <div class="legal-content">
            <p>
                At <strong><?= SITE_NAME ?></strong>, accessible from <?= BASE_URL ?>, one of our main priorities is the privacy of our visitors. This Privacy Policy document contains types of information that is collected and recorded by <?= SITE_NAME ?> and how we use it.
            </p>
            <p>
                If you have additional questions or require more information about our Privacy Policy, do not hesitate to contact us at <a href="mailto:<?= SITE_EMAIL ?>"><?= SITE_EMAIL ?></a>.
            </p>

            <h2>Log Files</h2>
            <p>
                <?= SITE_NAME ?> follows a standard procedure of using log files. These files log visitors when they visit websites. All hosting companies do this as part of hosting services' analytics. The information collected by log files includes internet protocol (IP) addresses, browser type, Internet Service Provider (ISP), date and time stamp, referring/exit pages, and possibly the number of clicks. These are not linked to any information that is personally identifiable. The purpose of the information is for analyzing trends, administering the site, tracking users' movement on the website, and gathering demographic information.
            </p>

            <h2>Cookies and Web Beacons</h2>
            <p>
                Like any other website, <?= SITE_NAME ?> uses "cookies". These cookies are used to store information including visitors' preferences, and the pages on the website that the visitor accessed or visited. The information is used to optimize the users' experience by customizing our web page content based on visitors' browser type and/or other information.
            </p>

            <h2>Google DoubleClick DART Cookie & Third-Party Advertising</h2>
            <p>
                Google is one of a third-party vendor on our site. It also uses cookies, known as DART cookies, to serve ads to our site visitors based upon their visit to <?= BASE_URL ?> and other sites on the internet. However, visitors may choose to decline the use of DART cookies by visiting the Google ad and content network Privacy Policy at the following URL: <a href="https://policies.google.com/technologies/ads" target="_blank" rel="noopener noreferrer">https://policies.google.com/technologies/ads</a>.
            </p>
            <p>
                Our advertising partners may include:
            </p>
            <ul>
                <li><strong>Google AdSense:</strong> <a href="https://policies.google.com/technologies/ads" target="_blank" rel="noopener noreferrer">Google Privacy Policy</a></li>
            </ul>
            <p>
                These third-party ad servers or ad networks use technologies like cookies, JavaScript, or Web Beacons that are used in their respective advertisements and links that appear on <?= SITE_NAME ?>, which are sent directly to users' browser. They automatically receive your IP address when this occurs. These technologies are used to measure the effectiveness of their advertising campaigns and/or to personalize the advertising content that you see on websites that you visit.
            </p>
            <p>
                <em>Note: <?= SITE_NAME ?> has no access to or control over these cookies that are used by third-party advertisers.</em>
            </p>

            <h2>CCPA Privacy Rights (Do Not Sell My Personal Information)</h2>
            <p>Under the CCPA, California consumers have the right to:</p>
            <ul>
                <li>Request that a business disclose the categories and specific pieces of personal data that a business has collected about consumers.</li>
                <li>Request that a business delete any personal data about the consumer that a business has collected.</li>
                <li>Request that a business that sells a consumer's personal data, not sell the consumer's personal data. (Note: We do not sell personal data).</li>
            </ul>

            <h2>GDPR Data Protection Rights</h2>
            <p>We want to make sure you are fully aware of all of your data protection rights:</p>
            <ul>
                <li><strong>The right to access:</strong> You have the right to request copies of your personal data.</li>
                <li><strong>The right to rectification:</strong> You have the right to request that we correct any information you believe is inaccurate.</li>
                <li><strong>The right to erasure:</strong> You have the right to request that we erase your personal data, under certain conditions.</li>
                <li><strong>The right to restrict processing:</strong> You have the right to request that we restrict the processing of your personal data.</li>
            </ul>

            <h2>Children's Information (COPPA Compliance)</h2>
            <p>
                Another part of our priority is adding protection for children while using the internet. We encourage parents and guardians to observe, participate in, and/or monitor and guide their online activity.
            </p>
            <p>
                <?= SITE_NAME ?> does not knowingly collect any Personal Identifiable Information from children under the age of 13. If you think that your child provided this kind of information on our website, we strongly encourage you to contact us immediately and we will do our best efforts to promptly remove such information from our records.
            </p>

            <h2>Consent</h2>
            <p>
                By using our website, you hereby consent to our Privacy Policy and agree to its terms.
            </p>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
