<?php
/**
 * Public Website - Terms & Conditions Page
 * Outlines acceptable website use, intellectual property, and content guidelines.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = "Terms and Conditions";
$metaDescription = "Terms and Conditions for accessing and using the CareerPulse website and career guidance resources.";
$metaKeywords = "terms and conditions, user agreement, terms of service, cookie conditions, acceptable use, " . SITE_NAME;

include __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
    <div class="site-container">
        <h1>Terms and Conditions</h1>
        <p>Please read these terms carefully before utilizing our services and articles.</p>
    </div>
</section>

<section class="page-section">
    <div class="site-container" style="max-width: 1360px;">
        <div class="legal-content">
            <p>
                Welcome to <strong><?= SITE_NAME ?></strong>. These Terms and Conditions govern your use of the website located at <?= BASE_URL ?> and any associated content, articles, newsletters, and services.
            </p>
            <p>
                By accessing this website, we assume you accept these terms and conditions. Do not continue to use <?= SITE_NAME ?> if you do not agree to take all of the terms and conditions stated on this page.
            </p>

            <h2>1. Informational & Educational Use Only</h2>
            <p>
                The articles, job openings, exam updates, templates, and interview frameworks provided on <?= SITE_NAME ?> are for general informational, educational, and guidance purposes only. While we make every attempt to ensure accuracy, employment terms, deadlines, vacancies, and application requirements are determined solely by hiring employers and government commissions.
            </p>

            <h2>2. Intellectual Property Rights</h2>
            <p>
                Unless otherwise stated, <?= SITE_NAME ?> and/or its licensors own the intellectual property rights for all editorial material, articles, and formatting published on <?= SITE_NAME ?>. All intellectual property rights are reserved. You may view and print pages from <?= BASE_URL ?> for your own personal, non-commercial use subject to restrictions set in these terms and conditions.
            </p>
            <p>You must not:</p>
            <ul>
                <li>Republish material from <?= SITE_NAME ?> without explicit canonical attribution.</li>
                <li>Sell, rent, or sub-license material from the website.</li>
                <li>Reproduce, duplicate, or copy material for commercial syndication.</li>
                <li>Redistribute content from <?= SITE_NAME ?> using automated scrapers or bots.</li>
            </ul>

            <h2>3. Hyperlinking to Our Content</h2>
            <p>
                Reputable educational institutions, career centers, public sector news services, and non-commercial career counselors may link to our home page or specific articles provided that the link is not deceptive, does not falsely imply sponsorship or endorsement, and fits within the context of the linking party's site.
            </p>

            <h2>4. Disclaimer of Liability</h2>
            <p>
                <?= SITE_NAME ?> shall not be held responsible for any hiring decisions, unsuccessful applications, changes in exam syllabi, or employment disputes between job seekers and hiring organizations. Users are solely responsible for verifying the authenticity of employers before submitting sensitive personal data or attending interviews.
            </p>

            <h2>5. Reservation of Rights</h2>
            <p>
                We reserve the right to request that you remove all links or any particular link to our website. We also reserve the right to amend these terms and conditions and its linking policy at any time.
            </p>

            <h2>6. Cookie Policy & Privacy Consent Conditions</h2>
            <p>
                By using <?= SITE_NAME ?>, you explicitly consent to the placement of essential and analytical cookies on your device in accordance with our <a href="<?= BASE_URL ?>/cookie-policy.php">Cookie Policy</a> and <a href="<?= BASE_URL ?>/privacy-policy.php">Privacy Policy</a>. If you reject non-essential cookies via our consent controls or browser settings, certain interactive features, custom bookmarks, and personalized recommendations may be limited.
            </p>

            <h2>7. Conditions of Access & User Conduct</h2>
            <p>
                As a condition of accessing our resources and contact mechanisms, you agree to refrain from:
            </p>
            <ul>
                <li>Using automated scraping, data-mining, or spider software to harvest job postings or editorial texts without express written permission.</li>
                <li>Transmitting unlawful, abusive, threatening, or fraudulent messages through our contact or feedback channels.</li>
                <li>Attempting to probe, scan, or breach the security of our web servers, database infrastructure, or administrative consoles.</li>
            </ul>

            <h2>8. Governing Conditions & Contact</h2>
            <p>
                These terms are governed by and construed in accordance with applicable digital laws. If any provision is deemed unenforceable, the remaining provisions shall remain in full force and effect. For any inquiries regarding these conditions, please visit our <a href="<?= BASE_URL ?>/contact.php">Contact Page</a>.
            </p>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
