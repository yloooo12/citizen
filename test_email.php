<?php
require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

$mail = new PHPMailer\PHPMailer\PHPMailer(true);

try {
    $mail->SMTPDebug = 2;
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'yloludovice709@gmail.com';
    $mail->Password = 'byvumtkpzqysysvy';
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->setFrom('yloludovice709@gmail.com', 'LSPU CCS');
    $mail->addAddress('yloludovice709@gmail.com', 'Test User');
    $mail->Subject = 'Test Email from LSPU';
    $mail->Body = 'This is a test email to check if PHPMailer is working.';
    
    if ($mail->send()) {
        echo '<h2 style="color: green;">Email sent successfully!</h2>';
    } else {
        echo '<h2 style="color: red;">Email failed to send</h2>';
    }
} catch (Exception $e) {
    echo '<h2 style="color: red;">Error: ' . $mail->ErrorInfo . '</h2>';
    echo '<pre>' . $e->getMessage() . '</pre>';
}
?>
