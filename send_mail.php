<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$name    = trim($_POST['name']    ?? '');
$email   = trim($_POST['email']   ?? '');
$subject = trim($_POST['subject'] ?? '');
$enquiry = trim($_POST['enquiry'] ?? '');
$message = trim($_POST['message'] ?? '');


// Server-side validation
$errors = [];
if (strlen($name) < 2)                              $errors[] = 'Full name is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))     $errors[] = 'A valid email address is required.';
if (strlen($subject) < 3)                           $errors[] = 'Subject is required.';
if (!in_array($enquiry, ['Customer Enquiries', 'Corporate Partnerships', 'Product Availability'], true))
                                                    $errors[] = 'Please select a valid enquiry type.';
if (strlen($message) < 10)                          $errors[] = 'Message must be at least 10 characters.';

if ($errors) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// ── SMTP configuration ──────────────────────────────────────────────────────
define('SMTP_HOST',     'mail.oikoshealthcare.in');
define('SMTP_PORT',     465);
define('SMTP_USER',     'info@oikoshealthcare.in');
define('SMTP_PASS',     'Oikoshealthcare_glansa@2026');        // ← replace with actual password
define('MAIL_TO',       'hr@oikoshealthcare.in');
define('MAIL_TO_NAME',  'Oikos Healthcare');

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = SMTP_PORT;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom(SMTP_USER, 'OikosCare Website');
    $mail->addAddress(MAIL_TO, MAIL_TO_NAME);
    $mail->addReplyTo($email, $name);

    $mail->isHTML(true);
    $mail->Subject = sprintf('[%s] %s', $enquiry, $subject);

    $nameEsc    = htmlspecialchars($name,    ENT_QUOTES, 'UTF-8');
    $emailEsc   = htmlspecialchars($email,   ENT_QUOTES, 'UTF-8');
    $subjectEsc = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
    $enquiryEsc = htmlspecialchars($enquiry, ENT_QUOTES, 'UTF-8');
    $messageEsc = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

    $mail->Body = <<<HTML
    <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto">
      <h2 style="color:#1a3c5e;border-bottom:2px solid #c8a84b;padding-bottom:8px">
        New Contact Form Submission
      </h2>
      <table style="width:100%;border-collapse:collapse">
        <tr><td style="padding:8px 0;font-weight:bold;width:140px">Name</td>
            <td style="padding:8px 0">{$nameEsc}</td></tr>
        <tr><td style="padding:8px 0;font-weight:bold">Email</td>
            <td style="padding:8px 0"><a href="mailto:{$emailEsc}">{$emailEsc}</a></td></tr>
        <tr><td style="padding:8px 0;font-weight:bold">Enquiry Type</td>
            <td style="padding:8px 0">{$enquiryEsc}</td></tr>
        <tr><td style="padding:8px 0;font-weight:bold">Subject</td>
            <td style="padding:8px 0">{$subjectEsc}</td></tr>
      </table>
      <h3 style="color:#1a3c5e;margin-top:20px">Message</h3>
      <p style="background:#f5f5f5;padding:16px;border-radius:4px;line-height:1.6">
        {$messageEsc}
      </p>
    </div>
    HTML;

    $mail->AltBody = "Name: $name\nEmail: $email\nEnquiry: $enquiry\nSubject: $subject\n\nMessage:\n$message";

    $mail->send();
    echo json_encode(['success' => true, 'message' => 'Your message has been sent successfully.']);

} catch (Exception $e) {
    error_log('PHPMailer error: ' . $mail->ErrorInfo);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not send your message. Please try again later.']);
}
