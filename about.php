<?php
/**
 * Public Website - About Us Page
 * Explains platform mission, editorial standards, and commitment to free career education.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = "About CareerNexaa | Our Mission & Editorial Standards";
$metaDescription = "Learn about CareerNexaa, an independent career and jobs guidance platform helping job seekers navigate modern hiring, interviews, and civil exams.";
$canonicalUrl = BASE_URL . '/about';

include __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
    <div class="site-container">
        <h1>About <?= SITE_NAME ?></h1>
        <p>Democratizing professional employment guidance, hiring intelligence, and career development.</p>
    </div>
</section>

<section class="page-section">
    <div class="site-container" style="max-width: 1360px;">
        <div class="legal-content">
            <h2>Who We Are</h2>
            <p>
                Founded in 2026, <strong><?= SITE_NAME ?></strong> is an independent digital publication and career advisory hub dedicated to helping professionals, fresh graduates, and career switchers navigate the rapidly changing employment landscape.
            </p>
            <p>
                From understanding applicant tracking algorithms (ATS) and remote work negotiations to passing public sector examinations, our guides are designed to cut through corporate jargon and provide clear, tactical steps to landing meaningful employment.
            </p>

            <h2>Our Editorial Philosophy & Integrity</h2>
            <p>
                We believe job seekers deserve accurate, timely, and non-exploitative guidance. That's why every article and job opening listed on <?= SITE_NAME ?> is governed by our strict editorial pillars:
            </p>
            <ul>
                <li><strong>100% Free Access:</strong> We never charge job seekers for interview advice, templates, or access to job listings.</li>
                <li><strong>Verified Sources:</strong> All hiring announcements, government notification details, and salary benchmarks are cross-referenced with primary employer releases and official gazettes.</li>
                <li><strong>No Deceptive Recruitment:</strong> We strictly prohibit and filter out MLM schemes, fee-charging recruiters, or unverified work-from-home scams.</li>
                <li><strong>Regular Content Audits:</strong> Outdated deadlines and changed hiring criteria are updated or clearly flagged by our editorial team.</li>
            </ul>

            <h2>What We Cover</h2>
            <p>Our platform covers six dedicated employment verticals:</p>
            <ul>
                <li><strong>Government & Civil Examinations:</strong> Eligibility requirements, syllabus roadmaps, and official portal navigation.</li>
                <li><strong>Private Tech & Corporate Careers:</strong> Software engineering, marketing, operations, and emerging industry hiring drives.</li>
                <li><strong>Remote & Asynchronous Work:</strong> Legitimate work-from-home positions, home-office workflows, and international contractor tips.</li>
                <li><strong>ATS Resumes & Cover Letters:</strong> Modern formatting standards, keyword placement, and recruiter-tested templates.</li>
                <li><strong>Behavioral & Technical Interviews:</strong> The STAR method, salary negotiation scripts, and commonly asked scenario questions.</li>
                <li><strong>Internships & Entry-Level:</strong> Practical advice for university students and beginners seeking their first professional break.</li>
            </ul>

            <h2>Get in Touch</h2>
            <p>
                Have feedback, questions about a job listing, or a suggestion for a guide? We'd love to hear from you. Visit our <a href="<?= BASE_URL ?>/contact">Contact Us page</a> or email our editorial desk directly at <a href="mailto:<?= SITE_EMAIL ?>"><?= SITE_EMAIL ?></a>.
            </p>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
