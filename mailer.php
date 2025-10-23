<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer (installed via Composer)
require 'vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    // === SMTP SETTINGS ===
    $mail->isSMTP();
    $mail->Host       = 'smtp.mailjet.com';   // Mailjet SMTP server
    $mail->SMTPAuth   = true;
    $mail->Username   = '1d37c8775432b2ae2342510c11242858';    // 🔑 Mailjet API key
    $mail->Password   = 'b92b08f9ea74f7a5803a3ee8a6560607'; // 🔒 Mailjet Secret key
    $mail->SMTPSecure = 'tls';                 // Use TLS encryption
    $mail->Port       = 587;                   // TLS port (use 465 for SSL)

    // === SENDER & RECIPIENT ===
    $mail->setFrom('papertrade@nirajang.com.np', 'PaperTrade Inc');
    $mail->addAddress('hello@nirajang.com.np', 'Nirajan Ghimire'); // Receiver

    // === CONTENT ===
    $mail->isHTML(true);
    $mail->Subject = 'Test Email via Mailjet (XAMPP)';
    $mail->Body    = '<h2>Lorem Ipsum</h2><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed sit amet facilisis urna. Praesent ac bibendum elit.</p>';
    $mail->AltBody = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed sit amet facilisis urna.';

    // === SEND ===
    $mail->send();
    echo '✅ Email sent successfully via Mailjet!';
} catch (Exception $e) {
    echo "❌ Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
?>
