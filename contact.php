<?php
/**
 * Public Website - Contact Us Page
 * Validated contact form with CSRF token verification, input sanitization,
 * honeypot anti-spam trap, submission time-gate, rate limiting, and interactive math CAPTCHA.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$siteName = get_site_name();
$contactEmail = get_setting('contact_email', defined('SITE_EMAIL') ? SITE_EMAIL : 'contact@careernexaa.com');

$pageTitle = "Contact " . $siteName . " | Inquiries & Editorial Support";
$metaDescription = "Get in touch with the " . $siteName . " editorial desk for career inquiries, feedback, or corrections.";

$errors = [];
$successMessage = '';

// 1. Rate Limiting: max 4 submissions per 10 minutes
if (!isset($_SESSION['contact_submissions'])) {
    $_SESSION['contact_submissions'] = [];
}
$now = time();
$_SESSION['contact_submissions'] = array_filter($_SESSION['contact_submissions'], function($ts) use ($now) {
    return ($now - $ts) < 600; // 10 minutes window
});

// 2. Initialize or Refresh Interactive Math CAPTCHA
if (empty($_SESSION['contact_captcha_a']) || empty($_SESSION['contact_captcha_b'])) {
    $_SESSION['contact_captcha_a'] = rand(2, 9);
    $_SESSION['contact_captcha_b'] = rand(1, 8);
    $_SESSION['contact_captcha_ans'] = $_SESSION['contact_captcha_a'] + $_SESSION['contact_captcha_b'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // A. CSRF Protection
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = "Security token expired or invalid. Please refresh the page and try again.";
    }

    // B. Invisible Honeypot Anti-Spam Trap
    if (!empty($_POST['website_url_hp'])) {
        // Silent bot discard: simulate success to prevent bot adaptation
        $successMessage = "Thank you! Your message has been received.";
        $_POST = [];
    }

    // C. Submission Time-Gate (Bots submit instantly in under 2 seconds)
    $formTimestamp = isset($_POST['form_timestamp']) ? (int)$_POST['form_timestamp'] : 0;
    if ($formTimestamp > 0 && ($now - $formTimestamp) < 2) {
        $errors[] = "Submission occurred too quickly. Automated submission blocked.";
    }

    // D. Rate Limiting Check
    if (empty($successMessage) && count($_SESSION['contact_submissions']) >= 4) {
        $errors[] = "Rate limit reached: You have submitted multiple messages recently. Please wait a few minutes before trying again.";
    }

    // E. Interactive Math CAPTCHA Validation
    $userCaptcha = isset($_POST['captcha_answer']) && $_POST['captcha_answer'] !== '' ? (int)$_POST['captcha_answer'] : null;
    $expectedCaptcha = (int)($_SESSION['contact_captcha_ans'] ?? -999);
    if ($userCaptcha === null || $userCaptcha !== $expectedCaptcha) {
        $errors[] = "Security check failed: Incorrect math answer. Please try again.";
    }

    // Regenerate CAPTCHA for subsequent attempts
    $_SESSION['contact_captcha_a'] = rand(2, 9);
    $_SESSION['contact_captcha_b'] = rand(1, 8);
    $_SESSION['contact_captcha_ans'] = $_SESSION['contact_captcha_a'] + $_SESSION['contact_captcha_b'];

    if (empty($successMessage) && empty($errors)) {
        // F. Input Extraction & Boundary Clamping
        $rawName    = (string)($_POST['name'] ?? '');
        $rawEmail   = (string)($_POST['email'] ?? '');
        $rawSubject = (string)($_POST['subject'] ?? '');
        $rawMessage = (string)($_POST['message'] ?? '');

        // Remove newlines and carriage returns from single-line fields (CRLF / Header Injection Defense)
        $cleanName    = trim(preg_replace('/[\r\n\t]+/', ' ', strip_tags($rawName)));
        $cleanEmail   = trim(preg_replace('/[\r\n\t]+/', '', $rawEmail));
        $cleanSubject = trim(preg_replace('/[\r\n\t]+/', ' ', strip_tags($rawSubject)));
        $cleanMessage = trim(strip_tags($rawMessage));

        // Clamping to strict safe bounds
        $name    = mb_substr($cleanName, 0, 80);
        $email   = mb_substr($cleanEmail, 0, 254);
        $subject = mb_substr($cleanSubject, 0, 120);
        $message = mb_substr($cleanMessage, 0, 3000);

        // G. URL & Link Spam Protection
        if (preg_match('/https?:\/\/|www\./i', $cleanName)) {
            $errors[] = "Web links and URLs are not permitted in the Full Name field.";
        }
        if (preg_match('/https?:\/\/|www\./i', $cleanSubject)) {
            $errors[] = "Web links and URLs are not permitted in the Subject field.";
        }
        if (preg_match_all('/https?:\/\//i', $cleanMessage, $matches) > 2) {
            $errors[] = "Too many external links detected in message (maximum 2 permitted).";
        }

        // H. Malicious Script & Payload Inspection
        if (preg_match('/(<script|javascript:|data:text\/html|onload=|onerror=|<iframe|<embed|union\s+select)/i', $rawMessage . ' ' . $rawName . ' ' . $rawSubject)) {
            $errors[] = "Security alert: Malicious script tags or disallowed keywords detected.";
        }

        // I. Standard Validations
        if (empty($name) || mb_strlen($name) < 2) {
            $errors[] = "Please provide your full name (between 2 and 80 characters).";
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address.";
        }
        if (empty($subject) || mb_strlen($subject) < 3) {
            $errors[] = "Please specify a subject for your inquiry (at least 3 characters).";
        }
        if (empty($message) || mb_strlen($message) < 15) {
            $errors[] = "Your message is too short. Please provide at least 15 characters of detail.";
        }

        if (empty($errors)) {
            // Record rate limit timestamp
            $_SESSION['contact_submissions'][] = $now;

            // Log contact activity safely
            if (function_exists('log_activity')) {
                log_activity('CONTACT_INQUIRY', 'Contact Us', "Verified inquiry submitted by " . substr($name, 0, 30) . " <" . substr($email, 0, 40) . ">");
            }

            $successMessage = "Thank you, " . h($name) . "! Your message regarding \"" . h($subject) . "\" has been safely received. Our editorial team will review and respond within 24-48 business hours.";
            
            // Clear input values after success
            $name = '';
            $email = '';
            $subject = '';
            $message = '';
        }
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
                    <div style="font-weight:600; color:var(--primary); font-size:0.95rem;"><?= h($contactEmail) ?></div>
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <div style="font-size:0.8rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Editorial Corrections</div>
                    <div style="font-weight:600; color:var(--primary); font-size:0.95rem;">editorial@careernexaa.com</div>
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <div style="font-size:0.8rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Expected Turnaround</div>
                    <div style="font-weight:500; color:var(--text-secondary); font-size:0.95rem;">Within 24 - 48 Hours</div>
                </div>

                <hr style="border:none; border-top:1px solid #e2e8f0; margin:1.5rem 0;">

                <div style="font-size:0.825rem; color:var(--text-muted); line-height:1.5;">
                    <strong style="color:var(--secondary);">Note for Job Applicants:</strong> <?= h($siteName) ?> is an educational guide. We do not accept resumes directly for positions listed on this platform; please apply using the official employer portals referenced in the articles.
                </div>
            </div>

            <!-- Contact Form Card -->
            <div class="form-card">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.5rem; flex-wrap:wrap; gap:0.5rem;">
                    <div>
                        <h2 style="font-size:1.5rem; color:var(--secondary); margin-bottom:0.25rem;">Send a Message</h2>
                        <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1rem;">Fill out the form below and we will get back to you promptly.</p>
                    </div>
                    <span style="display:inline-flex; align-items:center; gap:0.35rem; background:#ecfdf5; color:#047857; font-size:0.75rem; font-weight:700; padding:0.35rem 0.65rem; border-radius:20px; border:1px solid #a7f3d0;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        SSL Encrypted
                    </span>
                </div>

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

                <form action="<?= BASE_URL ?>/contact.php" method="POST" id="contactForm" autocomplete="on">
                    <?= csrf_field(); ?>

                    <!-- Anti-Bot Timestamp -->
                    <input type="hidden" name="form_timestamp" value="<?= time() ?>">

                    <!-- Invisible Anti-Spam Honeypot Field -->
                    <div style="display:none !important; visibility:hidden; position:absolute; left:-9999px;" aria-hidden="true">
                        <label for="website_url_hp">Leave this field blank</label>
                        <input type="text" id="website_url_hp" name="website_url_hp" value="" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label for="contact-name" class="form-label">Full Name *</label>
                        <input type="text" id="contact-name" name="name" class="form-control" required maxlength="80" value="<?= isset($name) ? h($name) : '' ?>" placeholder="Jane Doe">
                    </div>

                    <div class="form-group">
                        <label for="contact-email" class="form-label">Email Address *</label>
                        <input type="email" id="contact-email" name="email" class="form-control" required maxlength="254" value="<?= isset($email) ? h($email) : '' ?>" placeholder="jane@example.com">
                        <div class="form-text">We respect your privacy. No spam guaranteed.</div>
                    </div>

                    <div class="form-group">
                        <label for="contact-subject" class="form-label">Subject *</label>
                        <input type="text" id="contact-subject" name="subject" class="form-control" required maxlength="120" value="<?= isset($subject) ? h($subject) : '' ?>" placeholder="e.g. Question regarding ATS Resume Guide">
                    </div>

                    <div class="form-group">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.35rem;">
                            <label for="contact-message" class="form-label" style="margin-bottom:0;">Message Content *</label>
                            <span id="charCounter" style="font-size:0.75rem; color:var(--text-muted);">0 / 3,000</span>
                        </div>
                        <textarea id="contact-message" name="message" class="form-control" rows="5" required maxlength="3000" placeholder="Write your question, suggestion, or message here..."><?= isset($message) ? h($message) : '' ?></textarea>
                    </div>

                    <!-- Interactive Security Verification Check -->
                    <div class="form-group" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:1rem 1.15rem; margin-bottom:1.5rem;">
                        <label for="captcha-input" class="form-label" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:0.5rem;">
                            <span style="display:flex; align-items:center; gap:0.4rem; font-weight:700; color:#0f172a; font-size:0.9rem;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                Security Verification *
                            </span>
                            <span style="font-size:0.75rem; color:#059669; font-weight:600;">Anti-Bot Check</span>
                        </label>
                        <div style="display:flex; align-items:center; gap:0.75rem;">
                            <div style="background:#ffffff; border:1px solid #cbd5e1; border-radius:8px; padding:0.55rem 0.9rem; font-family:monospace; font-weight:800; font-size:1.1rem; color:#0f172a; user-select:none; letter-spacing:1px; box-shadow:inset 0 1px 2px rgba(0,0,0,0.05);">
                                <?= (int)$_SESSION['contact_captcha_a'] ?> + <?= (int)$_SESSION['contact_captcha_b'] ?> = ?
                            </div>
                            <input type="number" id="captcha-input" name="captcha_answer" class="form-control" style="max-width:130px; font-weight:700; font-size:1rem;" required placeholder="Answer" min="1" max="99" autocomplete="off">
                        </div>
                        <div class="form-text" style="margin-top:0.35rem; font-size:0.775rem;">Solve this simple math question to confirm you are human.</div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" id="btnSubmitContact">
                        Send Message &rarr;
                    </button>

                    <!-- Security Trust Badge -->
                    <div style="margin-top:1.25rem; padding:0.65rem 0.9rem; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; display:flex; align-items:center; gap:0.6rem; font-size:0.78rem; color:#166534;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.2" style="flex-shrink:0;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        <span><strong>CareerNexaa Security Shield Active:</strong> CSRF protection, anti-spam honeypot &amp; real-time payload filtering.</span>
                    </div>
                </form>

                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const msgEl = document.getElementById('contact-message');
                    const counterEl = document.getElementById('charCounter');
                    if (msgEl && counterEl) {
                        const updateCount = () => {
                            const len = msgEl.value.length;
                            counterEl.textContent = len.toLocaleString() + ' / 3,000';
                            counterEl.style.color = len >= 2900 ? '#ef4444' : 'var(--text-muted)';
                        };
                        msgEl.addEventListener('input', updateCount);
                        updateCount();
                    }

                    // Anti-Double Submit Protection
                    const form = document.getElementById('contactForm');
                    const submitBtn = document.getElementById('btnSubmitContact');
                    if (form && submitBtn) {
                        form.addEventListener('submit', function() {
                            submitBtn.style.opacity = '0.75';
                            submitBtn.style.pointerEvents = 'none';
                            submitBtn.innerHTML = 'Securing & Sending...';
                        });
                    }
                });
                </script>
            </div>

        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
