<?php
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

// Find students with 0 final exam scores (INC condition)
$result = $conn->query("SELECT DISTINCT g.student_id, g.subject_code, g.teacher_id
                        FROM grades g
                        WHERE g.column_name = 'finals_Exam' AND g.grade = '0'
                        AND NOT EXISTS (SELECT 1 FROM academic_alerts WHERE student_id = g.student_id AND course LIKE CONCAT('%', g.subject_code, '%') AND alert_type = 'INC')");

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $student_id = $row['student_id'];
        $subject_code = $row['subject_code'];
        $course_title = $subject_code . ' (Intermediate Programming)';
        
        // Insert academic alert
        $conn->query("INSERT INTO academic_alerts (student_id, course, grade, program_section, reason, intervention, instructor, semester, school_year, alert_type, is_resolved) 
                      VALUES ('$student_id', '$course_title', 'INC', 'BSIT', 'Incomplete Grade', 'Contact instructor: Bernardino, Mark', 'Bernardino, Mark', '2nd', '2025-2026', 'INC', 0)");
        
        // Insert notification
        $notif_msg = "⚠️ Academic Alert: You have an INCOMPLETE (INC) grade for $course_title. Contact instructor: Bernardino, Mark to complete requirements.";
        $conn->query("INSERT INTO notifications (user_id, message, type, is_read) VALUES ('$student_id', '$notif_msg', 'academic_alert', 0)");
        
        // Send email notification
        $email_result = $conn->query("SELECT email, first_name FROM users WHERE id_number='$student_id'");
        if ($email_result && $email_row = $email_result->fetch_assoc()) {
            $email = $email_row['email'];
            $name = $email_row['first_name'];
            $subject = "Academic Alert: Incomplete Grade";
            $message = "Dear $name,\n\nYou have an INCOMPLETE (INC) grade for $course_title due to missing final exam (score: 0).\n\nPlease contact your instructor: Bernardino, Mark to complete the requirements.\n\nProgram: B.S. Information Technology\nSemester: 2nd 2025-2026\n\nThank you.\nLSPU-CCS";
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
                echo "Failed to send email\n";
            }
        }
        
        echo "INC alert created for $student_id - $course_title\n";
    }
} else {
    echo "No new INC grades detected\n";
}

$conn->close();
?>