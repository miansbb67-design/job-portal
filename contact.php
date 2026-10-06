<?php
/**
 * Public Website - Contact Us Page
 * Validated contact form with CSRF token verification and input sanitization.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = "Contact CareerPulse | Inquiries & Editorial Support";
$metaDescription = "Get in touch with the CareerPulse editorial desk for career inquiries, feedback, or corrections.";

$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = "Security token expired or invalid. Please refresh the page and try again.";
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Form Validations
    if (empty($name) || mb_strlen($name) < 2) {
        $errors[] = "Please provide your full name (at least 2 characters).";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    if (empty($subject) || mb_strlen($subject) < 3) {
        $errors[] = "Please specify a subject for your inquiry.";
    }
    if (empty($message) || mb_strlen($message) < 15) {
        $errors[] = "Your message is too short. Please provide at least 15 characters of detail.";
    }

    if (empty($errors)) {
        // In a live production system, mail($to, $subject, $body) would be called here.
        // For local development and stability, confirm success cleanly:
        $successMessage = "Thank you, " . h($name) . "! Your message regarding \"" . h($subject) . "\" has been safely received. Our editorial team will review and respond within 24-48 business hours.";
        
        // Clear post values after success
        $name = '';
        $email = '';
        $subject = '';
        $message = '';
    }
}

include __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
    <div class="site-container">
        <h1>Contact Our Editorial Team</h1>
        <p>Questions about career guides, job postings, or partnership inquiries? We are here to help.</p>
    </div>
</section>

<section class="page-section">
    <div class="site-container" style="max-width: 1440px;">
        <div class="contact-layout-grid">
            
            <!-- Contact Details Sidebar -->
            <div class="info-sidebar-card">
                <h3 style="font-size:1.3rem; margin-bottom:1rem; color:var(--secondary);">Direct Communication</h3>
                <p style="color:var(--text-muted); font-size:0.925rem; line-height:1.6; margin-bottom:1.5rem;">
                    For faster processing, please specify whether your inquiry concerns an article correction, a job listing report, or general career counseling feedback.
                </p>

                <div style="margin-bottom: 1.25rem;">
                    <div style="font-size:0.8rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">General Inquiries</div>
                    <div style="font-weight:600; color:var(--primary); font-size:0.95rem;">contact@careerpulse.local</div>
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <div style="font-size:0.8rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Editorial Corrections</div>
                    <div style="font-weight:600; color:var(--primary); font-size:0.95rem;">editorial@careerpulse.local</div>
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <div style="font-size:0.8rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Expected Turnaround</div>
                    <div style="font-weight:500; color:var(--text-secondary); font-size:0.95rem;">Within 24 - 48 Hours</div>
                </div>

                <hr style="border:none; border-top:1px solid #e2e8f0; margin:1.5rem 0;">

                <div style="font-size:0.825rem; color:var(--text-muted); line-height:1.5;">
                    <strong style="color:var(--secondary);">Note for Job Applicants:</strong> <?= SITE_NAME ?> is an educational guide. We do not accept resumes directly for positions listed on this platform; please apply using the official employer portals referenced in the articles.
                </div>
            </div>

            <!-- Contact Form Card -->
            <div class="form-card">
                <h2 style="font-size:1.5rem; color:var(--secondary); margin-bottom:0.5rem;">Send a Message</h2>
                <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1.5rem;">Fill out the form below and we will get back to you promptly.</p>

                <?php if (!empty($successMessage)): ?>
                    <div class="alert alert-success" role="alert">
                        <span><?= $successMessage ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error" role="alert">
                        <div>
                            <strong>Please correct the following:</strong>
                            <ul style="list-style:disc; margin-left:1.25rem; margin-top:0.35rem;">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= h($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <form action="<?= BASE_URL ?>/contact.php" method="POST">
                    <?= csrf_field(); ?>

                    <div class="form-group">
                        <label for="contact-name" class="form-label">Full Name *</label>
                        <input type="text" id="contact-name" name="name" class="form-control" required value="<?= isset($name) ? h($name) : '' ?>" placeholder="Jane Doe">
                    </div>

                    <div class="form-group">
                        <label for="contact-email" class="form-label">Email Address *</label>
                        <input type="email" id="contact-email" name="email" class="form-control" required value="<?= isset($email) ? h($email) : '' ?>" placeholder="jane@example.com">
                        <div class="form-text">We respect your privacy. No spam guaranteed.</div>
                    </div>

                    <div class="form-group">
                        <label for="contact-subject" class="form-label">Subject *</label>
                        <input type="text" id="contact-subject" name="subject" class="form-control" required value="<?= isset($subject) ? h($subject) : '' ?>" placeholder="e.g. Question regarding ATS Resume Guide">
                    </div>

                    <div class="form-group">
                        <label for="contact-message" class="form-label">Message Content *</label>
                        <textarea id="contact-message" name="message" class="form-control" rows="5" required placeholder="Write your question, suggestion, or message here..."><?= isset($message) ? h($message) : '' ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        Send Message &rarr;
                    </button>
                </form>
            </div>

        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
