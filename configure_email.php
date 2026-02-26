<?php
function sendEmail($to, $subject, $message, $recipient_name = '') {
    require_once 'PHPMailer/src/Exception.php';
    require_once 'PHPMailer/src/PHPMailer.php';
    require_once 'PHPMailer/src/SMTP.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'yloludovice709@gmail.com';
        $mail->Password = 'byvumtkpzqysysvy';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        $mail->setFrom('yloludovice709@gmail.com', 'LSPU-CCS');
        $mail->addAddress($to, $recipient_name);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->send();
        
        // Log to database
        $conn = new mysqli("localhost", "root", "", "student_services");
        $to = $conn->real_escape_string($to);
        $subject = $conn->real_escape_string($subject);
        $message = $conn->real_escape_string($message);
        $recipient_name = $conn->real_escape_string($recipient_name);
        $conn->query("INSERT INTO email_logs (recipient_email, recipient_name, subject, message, status) VALUES ('$to', '$recipient_name', '$subject', '$message', 'sent')");
        $conn->close();
        
        return true;
    } catch (Exception $e) {
        // Log failed email
        $conn = new mysqli("localhost", "root", "", "student_services");
        $to = $conn->real_escape_string($to);
        $subject = $conn->real_escape_string($subject);
        $message = $conn->real_escape_string($message);
        $recipient_name = $conn->real_escape_string($recipient_name);
        $conn->query("INSERT INTO email_logs (recipient_email, recipient_name, subject, message, status) VALUES ('$to', '$recipient_name', '$subject', '$message', 'failed')");
        $conn->close();
        return false;
    }
}
?>
