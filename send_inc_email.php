<?php
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

// Get student email
$result = $conn->query("SELECT email, first_name FROM users WHERE id_number='0122-0348'");
if ($result && $row = $result->fetch_assoc()) {
    $email = $row['email'];
    $name = $row['first_name'];
    
    $subject = "Academic Alert: Incomplete Grade";
    $message = "Dear $name,\n\nYou have an INCOMPLETE (INC) grade for ITEC103 (Intermediate Programming) due to missing final exam (score: 0).\n\nPlease contact your instructor: Bernardino, Mark to complete the requirements.\n\nProgram: B.S. Information Technology\nSemester: 2nd 2025-2026\n\nThank you.\nLSPU-CCS";
    
    require_once 'PHPMailer/src/Exception.php';
    require_once 'PHPMailer/src/PHPMailer.php';
    require_once 'PHPMailer/src/SMTP.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'yloludovice709@gmail.com';
        $mail->Password = 'huxxoupfbwfeoaun';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('yloludovice709@gmail.com', 'LSPU-CCS');
        $mail->addAddress($email);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->send();
        echo "Email sent to $email\n";
    } catch (Exception $e) {
        echo "Failed to send email: {$mail->ErrorInfo}\n";
    }
} else {
    echo "Student email not found\n";
}

$conn->close();
?>