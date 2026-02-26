<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';
require_once 'PHPMailer/src/Exception.php';
require_once 'email_config.php';

function sendAcademicAlertEmail($to_email, $student_name, $alert) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = SMTP_AUTH;
        if ($mail->SMTPAuth) {
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
        }
        $mail->SMTPSecure = 'tls';
        $mail->Port = SMTP_PORT;
        $mail->SMTPOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true));
        
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to_email, $student_name);
        
        $alert_type = $alert['alert_type'];
        $course = $alert['course'];
        $grade = $alert['grade'];
        $reason = $alert['reason'];
        $intervention = $alert['intervention'];
        $instructor = $alert['instructor'];
        
        // Determine alert color and icon
        $color = '#ef4444';
        $bg_color = '#fee2e2';
        $icon = '⚠️';
        
        if ($alert_type == 'EXAM') {
            $color = '#10b981';
            $bg_color = '#d1fae5';
            $icon = '✅';
        } elseif ($alert_type == 'INTERVIEW') {
            $color = '#f59e0b';
            $bg_color = '#fef3c7';
            $icon = '📋';
        }
        
        $mail->isHTML(true);
        $mail->Subject = "Academic Alert: $course - LSPU CCS";
        $mail->Body = "<h2 style='color: $color;'>$icon Academic Alert</h2>
                       <p>Dear $student_name,</p>
                       <p>You have a new academic alert that requires your attention.</p>
                       <div style='background: $bg_color; padding: 15px; border-left: 4px solid $color; margin: 15px 0;'>
                           <p><strong>Subject/Course:</strong> $course</p>
                           <p><strong>Status:</strong> $grade</p>
                           <p><strong>Program:</strong> {$alert['program_section']}</p>
                           <p><strong>Reason:</strong> $reason</p>
                           <p><strong>Action Required:</strong> $intervention</p>
                           <p><strong>Contact:</strong> $instructor</p>
                       </div>
                       <p>Please log in to your student portal to view full details and take necessary action.</p>
                       <br><p>Best regards,<br>LSPU Computer Studies<br>Academic Affairs Office</p>";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>
