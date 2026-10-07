<?php
/**
 * Public Website - Editorial & Job Opportunities Disclaimer
 * Establishes that CareerNexaa is an educational informational portal, not a recruitment agency.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = "Disclaimer | " . SITE_NAME;
$metaDescription = "Important disclaimer regarding job opportunities, career guidance, and third-party recruitment links on CareerNexaa.";

include __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
    <div class="site-container">
        <h1>Editorial & Employment Disclaimer</h1>
        <p>Important transparency disclosures regarding our articles, career guidance, and job postings.</p>
    </div>
</section>

<section class="page-section">
    <div class="site-container" style="max-width: 1360px;">
        <div class="legal-content">
            <h2>1. Not a Recruitment Agency or Employer</h2>
            <p>
                <strong><?= SITE_NAME ?> is strictly an educational career blog and informational guide.</strong> We are NOT a recruitment firm, staffing agency, employment consultancy, or government testing agency.
            </p>
            <p>
                Any specific job vacancies, warehouse roles, or corporate openings discussed on this site are published purely for informational notice and public interest. We do not participate in the recruitment, interview scheduling, or candidate selection processes of any company mentioned.
            </p>

            <h2>2. No Application Fees Policy</h2>
            <p>
                <strong>Important Warning to Job Seekers:</strong> Genuine employers and government recruitment boards will never ask candidates for application processing fees, security deposits, or advance training payments via wire transfer, cryptocurrency, or personal payment apps.
            </p>
            <p>
                If you encounter any individual claiming to represent <?= SITE_NAME ?> requesting money in exchange for employment guarantees or interview slots, please report it immediately to <a href="mailto:<?= SITE_EMAIL ?>"><?= SITE_EMAIL ?></a> and local law enforcement authorities.
            </p>

            <h2>3. Accuracy and Timeliness of Information</h2>
            <p>
                While we make every effort to verify notifications, eligibility criteria, exam schedules, and salary benchmarks, hiring companies and government departments may change guidelines, cancel vacancies, or modify deadlines without prior notification to this website.
            </p>
            <p>
                Job seekers are strongly urged to verify all critical application requirements directly with the employer's official website or official gazettes before taking irreversible career decisions.
            </p>

            <h2>4. External Links Disclaimer</h2>
            <p>
                Our articles frequently link to external corporate career pages, government commission portals, and testing agencies. While we only link to reputable, verified domains, we have no control over the nature, content, or privacy practices of these external sites. The inclusion of any link does not imply endorsement by <?= SITE_NAME ?>.
            </p>

            <h2>5. Professional Career Advice Disclaimer</h2>
            <p>
                The information provided on <?= SITE_NAME ?> is for general educational purposes and does not constitute formal legal, financial, or certified career counseling advice. Consult certified HR or legal professionals for specialized individual situations.
            </p>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
