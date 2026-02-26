<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = 0;
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'ludoviceylo26@gmail.com';
    $mail->Password = 'fbeq vjhx nzus sdgo'; // WITH SPACES
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;
    
    $mail->setFrom('ludoviceylo26@gmail.com', 'Test');
    $mail->addAddress('ludoviceylo26@gmail.com');
    
    $mail->isHTML(true);
    $mail->Subject = 'Test';
    $mail->Body = 'Test';
    
    $mail->send();
    echo '<h2 style="color: green;">SUCCESS WITH SPACES!</h2>';
} catch (Exception $e) {
    echo '<h2 style="color: red;">FAILED WITH SPACES</h2>';
    echo '<p>' . $e->getMessage() . '</p>';
    
    // Try without spaces
    try {
        $mail2 = new PHPMailer(true);
        $mail2->SMTPDebug = 0;
        $mail2->isSMTP();
        $mail2->Host = 'smtp.gmail.com';
        $mail2->SMTPAuth = true;
        $mail2->Username = 'ludoviceylo26@gmail.com';
        $mail2->Password = 'fbeqvjhxnzussdgo'; // NO SPACES
        $mail2->SMTPSecure = 'tls';
        $mail2->Port = 587;
        
        $mail2->setFrom('ludoviceylo26@gmail.com', 'Test');
        $mail2->addAddress('ludoviceylo26@gmail.com');
        
        $mail2->isHTML(true);
        $mail2->Subject = 'Test';
        $mail2->Body = 'Test';
        
        $mail2->send();
        echo '<h2 style="color: green;">SUCCESS WITHOUT SPACES!</h2>';
    } catch (Exception $e2) {
        echo '<h2 style="color: red;">FAILED WITHOUT SPACES TOO</h2>';
        echo '<p>' . $e2->getMessage() . '</p>';
    }
}
?>
