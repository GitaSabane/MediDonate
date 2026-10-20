<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

$mail = new PHPMailer(true);

// SMTP Configuration
$mail->isSMTP();
$mail->Host       = 'smtp.gmail.com';
$mail->SMTPAuth   = true;
$mail->Username   = 'umdsystem01@gmail.com';
$mail->Password   = 'mngr rldi cybc yera'; // Replace with your actual App Password
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port       = 587;

$mail->setFrom('umdsystem01@gmail.com', 'MediDonate');
$mail->isHTML(true);
$mail->CharSet = 'UTF-8';
?>