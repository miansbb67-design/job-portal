<?php
/**
 * =============================================================================
 * Production-Ready Core PHP Script: Send Email via Gmail SMTP using PHPMailer
 * =============================================================================
 * GitHub Repository: https://github.com/PHPMailer/PHPMailer
 *
 * Requirements Met:
 * 1. Supports both Composer autoload and Manual include setup.
 * 2. Connects to smtp.gmail.com:587 with STARTTLS encryption and SMTPAuth.
 * 3. Accepts dynamic variables for sender, recipient, subject, HTML & plain body.
 * 4. Wrapped in try-catch block to handle exceptions with descriptive errors.
 * 5. Clean, comprehensive inline comments on each configuration step.
 * =============================================================================
 */

// -----------------------------------------------------------------------------
// STEP 1: Include PHPMailer (Autoload or Manual Include)
// -----------------------------------------------------------------------------
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    // Option A: If installed via Composer (`composer require phpmailer/phpmailer`)
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    // Option B: Manual include from the local PHPMailer library directory
    require_once __DIR__ . '/includes/PHPMailer/Exception.php';
    require_once __DIR__ . '/includes/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/includes/PHPMailer/SMTP.php';
}

// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Sends an email using Gmail SMTP with PHPMailer.
 *
 * @param string $senderEmail    Gmail address used to send the email
 * @param string $senderName     Display name for the sender
 * @param string $gmailAppPass   16-character Google App Password (NOT your account password)
 * @param string $recipientEmail Target email address
 * @param string $recipientName  Target recipient name
 * @param string $subject        Subject line
 * @param string $htmlBody       HTML formatted email body
 * @param string $plainTextBody  Optional plain text body (for non-HTML email clients)
 * @return array Result status array ['success' => bool, 'message' => string]
 */
function sendGmailSmtpEmail(
    string $senderEmail,
    string $senderName,
    string $gmailAppPass,
    string $recipientEmail,
    string $recipientName,
    string $subject,
    string $htmlBody,
    string $plainTextBody = ''
): array {

    // STEP 2: Instantiate PHPMailer with Exceptions enabled (passing `true`)
    $mail = new PHPMailer(true);

    try {
        // ---------------------------------------------------------------------
        // STEP 3: Server & SMTP Settings Configuration
        // ---------------------------------------------------------------------
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER;         // Uncomment for verbose debugging
        $mail->isSMTP();                                  // Set mailer to use SMTP transport
        $mail->Host       = 'smtp.gmail.com';             // Specify main Gmail SMTP server
        $mail->SMTPAuth   = true;                         // Enable SMTP authentication
        $mail->Username   = $senderEmail;                 // Your full Gmail address
        $mail->Password   = $gmailAppPass;                // 16-character Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;// Enforce STARTTLS encryption
        $mail->Port       = 587;                          // TCP port 587 (Standard for STARTTLS)
        $mail->CharSet    = 'UTF-8';                      // Ensure proper character encoding
        $mail->Encoding   = 'base64';                     // Transfer encoding

        // ANTI-SPAM DELIVERABILITY TWEAKS:
        $mail->Hostname   = 'mail.gmail.com';             // Valid Message-ID domain (prevents '@localhost' spam trigger)
        $mail->XMailer    = null;                         // Completely omit X-Mailer header (prevents spam filters scoring empty/mailer headers)
        $mail->Sender     = $senderEmail;                 // Explicit envelope Return-Path
        $mail->Priority   = 3;                            // Normal priority (prevents spam flags)
        $mail->MessageDate= date('r');                    // Explicit RFC 2822 date

        // Set timeout to avoid indefinite hanging on slow network connections
        $mail->Timeout    = 15;

        // ---------------------------------------------------------------------
        // STEP 4: Sender and Recipient Configuration (Dynamic Variables)
        // ---------------------------------------------------------------------
        $mail->setFrom($senderEmail, $senderName);        // Add sender info (matches authenticated Gmail account)
        $mail->addAddress($recipientEmail, $recipientName);// Add primary recipient

        // Optional: Set Reply-To header
        $mail->addReplyTo($senderEmail, $senderName);

        // ---------------------------------------------------------------------
        // STEP 5: Content Configuration (HTML & Plain-Text Alternative)
        // ---------------------------------------------------------------------
        $mail->isHTML(true);                              // Set email format to HTML
        $mail->Subject = $subject;                        // Set email subject line
        $mail->Body    = $htmlBody;                       // Set rich HTML content

        // If no plain text body provided, strip HTML tags from the HTML body
        if (empty($plainTextBody)) {
            $plainTextBody = strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $htmlBody));
        }
        $mail->AltBody = $plainTextBody;                  // Set alternative plain-text body

        // ---------------------------------------------------------------------
        // STEP 6: Send the Email
        // ---------------------------------------------------------------------
        $mail->send();

        return [
            'success' => true,
            'message' => 'Message has been sent successfully via Gmail SMTP.'
        ];

    } catch (Exception $e) {
        // STEP 7: Handle PHPMailer exceptions and extract meaningful error details
        return [
            'success' => false,
            'message' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo} (Exception: {$e->getMessage()})"
        ];
    } catch (\Throwable $t) {
        // Handle unexpected general system errors
        return [
            'success' => false,
            'message' => "An unexpected error occurred: {$t->getMessage()}"
        ];
    }
}

// -----------------------------------------------------------------------------
// USAGE DEMONSTRATION & EXAMPLE INVOCATION:
// -----------------------------------------------------------------------------
// Load credentials dynamically from environment variables or define here:
$senderEmail    = getenv('SMTP_USER') ?: 'your_gmail_address@gmail.com';
$senderName     = getenv('SMTP_FROM_NAME') ?: 'CareerPulse Notifications';
$gmailAppPass   = getenv('SMTP_PASS') ?: 'xxxx xxxx xxxx xxxx'; // 16-character App Password

$recipientEmail = 'user@example.com';
$recipientName  = 'Jane Doe';
$subject        = 'Welcome to CareerPulse - Your Account Verification';

$htmlBody = '
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background:#f4f6f9; margin:0; padding:20px; }
        .card { max-width:600px; margin:auto; background:#ffffff; border-radius:8px; padding:30px; border:1px solid #e2e8f0; }
        .header { color:#059669; font-size:22px; font-weight:bold; margin-bottom:15px; }
        .btn { display:inline-block; background:#059669; color:#ffffff; padding:12px 24px; border-radius:6px; text-decoration:none; font-weight:bold; margin:20px 0; }
        .footer { font-size:12px; color:#94a3b8; margin-top:25px; border-top:1px solid #f1f5f9; padding-top:15px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">CareerPulse Platform</div>
        <p>Hello <strong>Jane Doe</strong>,</p>
        <p>This is a test notification confirming your Gmail SMTP configuration via PHPMailer on port 587 (STARTTLS).</p>
        <p><a href="https://example.com" class="btn">Access Dashboard &rarr;</a></p>
        <p>If you did not initiate this request, you can safely disregard this email.</p>
        <div class="footer">&copy; ' . date('Y') . ' CareerPulse Job Portal. All rights reserved.</div>
    </div>
</body>
</html>';

$plainTextBody = "Hello Jane Doe,\n\nThis is a test notification confirming your Gmail SMTP configuration via PHPMailer on port 587 (STARTTLS).\n\nAccess Dashboard: https://example.com\n\n- CareerPulse Platform";

// Only run test dispatch if triggered directly via CLI with credentials
if (php_sapi_name() === 'cli' && $argc > 1 && $argv[1] === '--run') {
    echo "Sending test email to {$recipientEmail} via smtp.gmail.com:587...\n";
    $result = sendGmailSmtpEmail(
        $senderEmail,
        $senderName,
        $gmailAppPass,
        $recipientEmail,
        $recipientName,
        $subject,
        $htmlBody,
        $plainTextBody
    );
    echo "Status: " . ($result['success'] ? "SUCCESS" : "FAILED") . "\n";
    echo "Message: " . $result['message'] . "\n";
}
