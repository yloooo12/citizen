<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';
require_once 'PHPMailer/src/Exception.php';
require_once 'email_config.php';

function sendInterviewRequestEmail($to_email, $student_name) {
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
        
        $mail->isHTML(true);
        $mail->Subject = "Interview Request Submitted - LSPU CCS";
        $mail->Body = "<h2>Interview Request Submitted</h2>
                       <p>Dear $student_name,</p>
                       <p>Your admission interview request has been submitted successfully.</p>
                       <div style='background: #fef3c7; padding: 15px; border-left: 4px solid #f59e0b; margin: 15px 0;'>
                           <p><strong>Status:</strong> PENDING</p>
                           <p><strong>Action:</strong> Waiting for secretary to send interview schedule</p>
                       </div>
                       <p>You will receive another notification once the interview schedule is set.</p>
                       <br><p>Best regards,<br>LSPU Computer Studies</p>";
        
        $mail->send();
        error_log("Interview email sent successfully to: $to_email");
        return true;
    } catch (Exception $e) {
        error_log("Interview email failed: " . $e->getMessage());
        return false;
    }
}

function sendInterviewScheduledEmail($to_email, $student_name, $date, $time, $platform, $room = '', $meeting_link = '') {
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
        
        $room_info = !empty($room) ? "<p><strong>Room:</strong> $room</p>" : '';
        $link_info = !empty($meeting_link) ? "<p><strong>Meeting Link:</strong> <a href='$meeting_link' style='color: #667eea;'>$meeting_link</a></p>" : '';
        
        $mail->isHTML(true);
        $mail->Subject = "Interview Scheduled - LSPU CCS";
        $mail->Body = "<h2 style='color: #10b981;'>✅ Interview Scheduled</h2>
                       <p>Dear $student_name,</p>
                       <p>Your admission interview has been scheduled!</p>
                       <div style='background: #d1fae5; padding: 15px; border-left: 4px solid #10b981; margin: 15px 0;'>
                           <p><strong>Date:</strong> $date</p>
                           <p><strong>Time:</strong> $time</p>
                           <p><strong>Platform:</strong> $platform</p>
                           $room_info
                           $link_info
                       </div>
                       <p>Please be on time and prepare the necessary documents.</p>
                       <br><p>Best regards,<br>LSPU Computer Studies<br>Admission Office</p>";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function sendCreditingApprovedEmail($to_email, $student_name) {
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
        
        $mail->isHTML(true);
        $mail->Subject = "Crediting Request Approved - LSPU CCS";
        $mail->Body = "<h2 style='color: #10b981;'>✅ Crediting Request Approved</h2>
                       <p>Dear $student_name,</p>
                       <p>Congratulations! Your crediting request has been approved by the Dean.</p>
                       <div style='background: #d1fae5; padding: 15px; border-left: 4px solid #10b981; margin: 15px 0;'>
                           <p><strong>Status:</strong> APPROVED</p>
                           <p><strong>Action:</strong> Your crediting document is now ready for download</p>
                       </div>
                       <p>Please log in to your student portal to download the official crediting document.</p>
                       <br><p>Best regards,<br>LSPU Computer Studies<br>Registrar Office</p>";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function sendCreditingAlertEmail($to_email, $student_name, $student_type) {
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
        
        $mail->isHTML(true);
        $mail->Subject = "Crediting Alert - Action Required";
        $mail->Body = "<h2 style='color: #ef4444;'>Crediting Alert - Action Required</h2>
                       <p>Dear $student_name,</p>
                       <p>As a <strong>$student_type</strong> student, you need to submit crediting documents.</p>
                       <div style='background: #fee2e2; padding: 15px; border-left: 4px solid #ef4444; margin: 15px 0;'>
                           <p><strong>Student Type:</strong> $student_type</p>
                           <p><strong>Status:</strong> WARNING</p>
                           <p><strong>Action Required:</strong> Submit required documents for crediting evaluation</p>
                       </div>
                       <p><strong>Requirements:</strong></p>
                       <ul>
                           <li>Upload your Transcript of Records</li>
                           <li>Provide previous school details</li>
                           <li>List subjects you want to be credited</li>
                       </ul>
                       <p>Please log in to the portal to submit your crediting request.</p>
                       <br><p>Best regards,<br>LSPU Computer Studies<br>Registrar Office</p>";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>
