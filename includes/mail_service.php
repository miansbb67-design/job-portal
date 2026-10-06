<?php
/**
 * Production-Ready Mailer Service using PHPMailer
 * -----------------------------------------------------------------------------
 * Provides secure, reliable email dispatching via Gmail SMTP (or any custom SMTP).
 * Implements STARTTLS on port 587, full error handling with Try-Catch,
 * and support for dynamic sender/recipient parameters, HTML and plain-text bodies.
 *
 * Supports both Composer Autoloader and Manual Include setups.
 *
 * @author Expert PHP Backend Developer
 * @package CareerPulse
 */

// Step 1: Load PHPMailer (Composer Autoloader or Manual Include)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    // 1.A: Composer environment
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    // 1.B: Manual include environment
    require_once __DIR__ . '/PHPMailer/Exception.php';
    require_once __DIR__ . '/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/SMTP.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Sends an email using PHPMailer configured for Gmail SMTP (Port 587, STARTTLS).
 *
 * @param array $options {
 *     @type string      $to_email    Recipient email address (Required)
 *     @type string      $to_name     Recipient display name (Optional)
 *     @type string      $subject     Email subject line (Required)
 *     @type string      $body_html   Rich HTML email body content (Required)
 *     @type string|null $body_text   Plain-text alternative body (Optional, auto-generated from HTML if empty)
 *     @type string|null $from_email  Sender email address (Optional, falls back to .env / config)
 *     @type string|null $from_name   Sender display name (Optional, falls back to .env / config)
 *     @type string|null $reply_to    Reply-to email address (Optional)
 *     @type array|null  $attachments Optional array of file paths or ['path' => ..., 'name' => ...]
 *     @type bool        $debug       Set to true to print verbose SMTP interaction log (Default: false)
 * }
 * @return array ['success' => bool, 'message' => string, 'error' => string|null]
 */
function send_smtp_email(array $options): array {
    // -------------------------------------------------------------------------
    // Step 2: Validate Required Dynamic Parameters
    // -------------------------------------------------------------------------
    $toEmail  = filter_var($options['to_email'] ?? '', FILTER_VALIDATE_EMAIL);
    $toName   = trim($options['to_name'] ?? '');
    $subject  = trim($options['subject'] ?? '');
    $bodyHtml = trim($options['body_html'] ?? '');
    $bodyText = trim($options['body_text'] ?? '');
    $debug    = !empty($options['debug']);

    if (!$toEmail) {
        return [
            'success' => false,
            'message' => 'Invalid recipient email address provided.',
            'error'   => 'INVALID_RECIPIENT_EMAIL'
        ];
    }

    if (empty($subject)) {
        return [
            'success' => false,
            'message' => 'Email subject cannot be empty.',
            'error'   => 'EMPTY_SUBJECT'
        ];
    }

    if (empty($bodyHtml)) {
        return [
            'success' => false,
            'message' => 'Email HTML body content cannot be empty.',
            'error'   => 'EMPTY_BODY'
        ];
    }

    // Auto-generate plain-text version if not supplied
    if (empty($bodyText)) {
        $bodyText = strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $bodyHtml));
        $bodyText = html_entity_decode($bodyText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    // -------------------------------------------------------------------------
    // Step 3: Resolve Dynamic or Environment-based Sender Settings
    // -------------------------------------------------------------------------
    $smtpHost = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
    $smtpPort = (int)(getenv('SMTP_PORT') ?: 587);
    $smtpUser = getenv('SMTP_USER') ?: '';
    $smtpPass = getenv('SMTP_PASS') ?: '';
    
    // Resolve From Name and Email
    $defaultFromEmail = getenv('SMTP_FROM_EMAIL') ?: ($smtpUser ?: 'no-reply@careerpulse.com');
    $defaultFromName  = getenv('SMTP_FROM_NAME') ?: (defined('SITE_NAME') ? SITE_NAME : 'CareerPulse');

    $fromEmail = !empty($options['from_email']) && filter_var($options['from_email'], FILTER_VALIDATE_EMAIL)
        ? $options['from_email']
        : $defaultFromEmail;

    $fromName = !empty($options['from_name'])
        ? trim($options['from_name'])
        : $defaultFromName;

    // ANTI-SPAM RULE 1: SPF & DMARC Alignment for Gmail SMTP
    // When sending via smtp.gmail.com, Google enforces envelope From to match authenticated Gmail account.
    // If From is mismatched (e.g. sending as @careerpulse.com from @gmail.com SMTP), receivers mark it as spoofing/spam.
    $originalFromEmail = $fromEmail;
    if (strpos(strtolower($smtpHost), 'gmail.com') !== false && !empty($smtpUser) && filter_var($smtpUser, FILTER_VALIDATE_EMAIL)) {
        $fromEmail = $smtpUser;
    }

    // -------------------------------------------------------------------------
    // Step 4: Check for Placeholder Credentials (Development Mode Graceful Log)
    // -------------------------------------------------------------------------
    $isConfigured = !empty($smtpUser) && !empty($smtpPass) && 
                    strpos($smtpUser, 'your_email@') === false && 
                    strpos($smtpPass, 'your_gmail_') === false;

    // Instantiate PHPMailer with exceptions enabled (true)
    $mail = new PHPMailer(true);

    try {
        // ---------------------------------------------------------------------
        // Step 5: Configure SMTP Server & Encryption
        // ---------------------------------------------------------------------
        $mail->isSMTP();                                       // Direct transport via SMTP
        $mail->Host       = $smtpHost;                         // Gmail SMTP server (smtp.gmail.com)
        $mail->SMTPAuth   = true;                              // Enable robust SMTP authentication
        $mail->Username   = $smtpUser;                         // Gmail address (e.g. yourname@gmail.com)
        $mail->Password   = $smtpPass;                         // 16-character Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;    // Enable TLS encryption via STARTTLS
        $mail->Port       = $smtpPort;                         // TCP port 587 for STARTTLS
        $mail->CharSet    = PHPMailer::CHARSET_UTF8;           // Enforce UTF-8 encoding
        $mail->Encoding   = 'base64';                          // Robust transfer encoding

        // ANTI-SPAM RULE 2: Prevent Message-ID using '@localhost' (Major spam trigger)
        if (strpos(strtolower($smtpHost), 'gmail.com') !== false) {
            $mail->Hostname = 'mail.gmail.com';
        } elseif (!empty($smtpUser) && strpos($smtpUser, '@') !== false) {
            $mail->Hostname = substr(strrchr($smtpUser, "@"), 1);
        }

        // ANTI-SPAM RULE 3: Suppress X-Mailer header entirely (null completely omits the header)
        // Leaving an empty or spaced X-Mailer triggers spam rules like EMPTY_X_MAILER
        $mail->XMailer = null;

        // ANTI-SPAM RULE 4: Explicit envelope Return-Path (Sender)
        $mail->Sender = $fromEmail;

        // ANTI-SPAM RULE 5: Standard RFC compliant transactional headers
        $mail->Priority    = 3;                                // Normal priority (Priority 1 triggers spam filters)
        $mail->MessageDate = date('r');                        // Standard RFC 2822 date

        // Timeout optimization (15 seconds to prevent PHP execution freeze)
        $mail->Timeout    = 15;

        // Debugging mode
        if ($debug) {
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;             // Verbose debug output
        } else {
            $mail->SMTPDebug = SMTP::DEBUG_OFF;                // Production: Silent
        }

        // ---------------------------------------------------------------------
        // Step 6: Configure Sender & Recipient Headers
        // ---------------------------------------------------------------------
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail, $toName);

        // Configure Reply-To: If custom from email differed from Gmail address, set as reply-to
        if (!empty($options['reply_to']) && filter_var($options['reply_to'], FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($options['reply_to'], $fromName);
        } elseif (!empty($originalFromEmail) && strtolower($originalFromEmail) !== strtolower($fromEmail)) {
            $mail->addReplyTo($originalFromEmail, $fromName);
        }

        // Optional File Attachments
        if (!empty($options['attachments']) && is_array($options['attachments'])) {
            foreach ($options['attachments'] as $att) {
                if (is_string($att) && file_exists($att)) {
                    $mail->addAttachment($att);
                } elseif (is_array($att) && !empty($att['path']) && file_exists($att['path'])) {
                    $mail->addAttachment($att['path'], $att['name'] ?? '');
                }
            }
        }

        // ---------------------------------------------------------------------
        // Step 7: Email Content & Multipurpose Internet Mail Extensions (MIME)
        // ---------------------------------------------------------------------
        $mail->isHTML(true);                                   // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;                            // Rich HTML body
        $mail->AltBody = $bodyText;                            // Plain text body for non-HTML mail clients

        // ---------------------------------------------------------------------
        // Step 8: Send or Simulated Local Dispatch
        // ---------------------------------------------------------------------
        if (!$isConfigured) {
            // If developer hasn't configured their Gmail App Password yet in .env:
            // Log the email into database/error log for seamless local testing
            if (function_exists('log_activity')) {
                log_activity('EMAIL_SIMULATED', 'Mailer', "Simulated email to {$toEmail} (SMTP credentials not yet filled in .env): {$subject}");
            }
            return [
                'success' => true,
                'simulated' => true,
                'message' => "Email prepared successfully. (Notice: Add your real Gmail App Password in .env to deliver live emails via smtp.gmail.com:587)",
                'error'   => null
            ];
        }

        // Execute transmission
        $mail->send();

        if (function_exists('log_activity')) {
            log_activity('EMAIL_SENT', 'Mailer', "Sent email to {$toEmail}: {$subject}");
        }

        return [
            'success' => true,
            'simulated' => false,
            'message' => 'Email sent successfully via Gmail SMTP.',
            'error'   => null
        ];

    } catch (Exception $e) {
        // PHPMailer-specific exception handling
        $errorMessage = $mail->ErrorInfo ?: $e->getMessage();

        if (function_exists('log_activity')) {
            log_activity('EMAIL_FAILED', 'Mailer', "Failed to send email to {$toEmail}: {$errorMessage}");
        }

        return [
            'success' => false,
            'message' => 'Failed to send email: ' . $errorMessage,
            'error'   => $errorMessage,
            'exception' => $e->getMessage()
        ];
    } catch (\Throwable $t) {
        // Catch general system / runtime errors
        return [
            'success' => false,
            'message' => 'Unexpected error while sending email: ' . $t->getMessage(),
            'error'   => $t->getMessage()
        ];
    }
}

/**
 * Convenience shorthand function for sending emails with positional arguments.
 *
 * @param string      $toEmail   Recipient email
 * @param string      $toName    Recipient name
 * @param string      $subject   Subject
 * @param string      $bodyHtml  HTML message body
 * @param string      $bodyText  Alternative plain text
 * @param string|null $fromEmail Custom from email
 * @param string|null $fromName  Custom from name
 * @return array
 */
function send_email(
    string $toEmail,
    string $toName,
    string $subject,
    string $bodyHtml,
    string $bodyText = '',
    ?string $fromEmail = null,
    ?string $fromName = null
): array {
    return send_smtp_email([
        'to_email'   => $toEmail,
        'to_name'    => $toName,
        'subject'    => $subject,
        'body_html'  => $bodyHtml,
        'body_text'  => $bodyText,
        'from_email' => $fromEmail,
        'from_name'  => $fromName
    ]);
}
