<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';
require_once 'email_config.php';

function sendGradeWarningEmail($to_email, $student_name, $subject, $grade, $grade_type = 'midterm') {
    $mail = new PHPMailer(true);
    
    try {
        $grade_label = $grade_type === 'final' ? 'Final Grade' : 'Midterm Final Grade';
        
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = 'tls';
        $mail->Port = SMTP_PORT;
        $mail->SMTPOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true));
        
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to_email, $student_name);
        
        $mail->isHTML(true);
        $mail->Subject = "Grade Warning - $subject";
        $mail->Body = "<h2>Grade Warning Notice</h2><p>Dear $student_name,</p><p>Your <strong>$grade_label</strong> in <strong>$subject</strong> is <strong style='color: red;'>$grade</strong>.</p><p>You need to improve your performance to pass this subject. Please see your instructor for guidance.</p><br><p>Best regards,<br>LSPU Computer Studies</p>";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function sendRegistrationEmail($to_email, $first_name, $last_name, $id_number, $token) {
    $mail = new PHPMailer(false);
    
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = 'tls';
        $mail->Port = SMTP_PORT;
        $mail->SMTPOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true));
        $mail->CharSet = 'UTF-8';
        
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to_email, $first_name . ' ' . $last_name);
        
        $registration_link = "http://localhost/citizenz/student_register.php?token=$token";
        
        $mail->isHTML(true);
        $mail->Subject = "Student Registration - LSPU CCS";
        $mail->Body = "<h2>Welcome to LSPU CCS!</h2><p>Dear $first_name $last_name,</p><p>Your student account has been created. Please click the link below to complete your registration:</p><p><a href='$registration_link'>$registration_link</a></p><p>Your ID Number: <strong>$id_number</strong></p><p>This link will expire in 7 days.</p><br><p>Best regards,<br>LSPU Computer Studies</p>";
        
        if (!$mail->send()) {
            error_log('Mailer Error: ' . $mail->ErrorInfo);
            return false;
        }
        return true;
    } catch (Exception $e) {
        error_log('Exception: ' . $e->getMessage());
        return false;
    }
}

function sendTeacherRegistrationEmail($to_email, $first_name, $last_name, $id_number, $token) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = 'tls';
        $mail->Port = SMTP_PORT;
        $mail->SMTPOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true));
        
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to_email, $first_name . ' ' . $last_name);
        
        $registration_link = "http://localhost/citizenz/teacher_register.php?token=$token";
        
        $mail->isHTML(true);
        $mail->Subject = "Teacher Registration - LSPU CCS";
        $mail->Body = "<h2>Welcome to LSPU CCS!</h2><p>Dear $first_name $last_name,</p><p>Your teacher account has been created. Please click the link below to complete your registration:</p><p><a href='$registration_link'>$registration_link</a></p><p>Your ID Number: <strong>$id_number</strong></p><p>This link will expire in 7 days.</p><br><p>Best regards,<br>LSPU Computer Studies</p>";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function sendIncNotificationEmail($to_email, $student_name, $course, $instructor, $semester, $school_year) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = 'tls';
        $mail->Port = SMTP_PORT;
        $mail->SMTPOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true));
        
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to_email, $student_name);
        
        $mail->isHTML(true);
        $mail->Subject = "Academic Alert: Incomplete Grade (INC) - $course";
        $mail->Body = "<h2 style='color: #ef4444;'>🚨 Academic Alert: Incomplete Grade</h2>
                       <p>Dear $student_name,</p>
                       <p>This is to inform you that you have received an <strong style='color: #ef4444;'>INCOMPLETE (INC)</strong> grade for the following subject:</p>
                       <div style='background: #fee2e2; padding: 15px; border-left: 4px solid #ef4444; margin: 15px 0;'>
                           <p><strong>Subject:</strong> $course</p>
                           <p><strong>Instructor:</strong> $instructor</p>
                           <p><strong>Semester:</strong> $semester $school_year</p>
                           <p><strong>Reason:</strong> Missing Finals Exam</p>
                       </div>
                       <p><strong>Required Action:</strong></p>
                       <ul>
                           <li>Contact your instructor immediately: $instructor</li>
                           <li>Submit an INC removal request through the student portal</li>
                           <li>Complete missing requirements as instructed</li>
                       </ul>
                       <p style='color: #dc2626;'><strong>Important:</strong> Failure to resolve this INC grade may affect your academic standing and enrollment for the next semester.</p>
                       <p>Please log in to your student portal to submit an INC removal request.</p>
                       <br><p>Best regards,<br>LSPU Computer Studies<br>Academic Affairs Office</p>";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function sendIncResolvedEmail($to_email, $student_name, $course) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = 'tls';
        $mail->Port = SMTP_PORT;
        $mail->SMTPOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true));
        
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to_email, $student_name);
        
        $mail->isHTML(true);
        $mail->Subject = "Good News: INC Grade Resolved - $course";
        $mail->Body = "<h2 style='color: #10b981;'>✅ Congratulations! INC Grade Resolved</h2>
                       <p>Dear $student_name,</p>
                       <p>We are pleased to inform you that your <strong style='color: #10b981;'>INCOMPLETE (INC)</strong> grade has been successfully resolved!</p>
                       <div style='background: #d1fae5; padding: 15px; border-left: 4px solid #10b981; margin: 15px 0;'>
                           <p><strong>Subject:</strong> $course</p>
                           <p><strong>Status:</strong> <span style='color: #10b981; font-weight: bold;'>RESOLVED</span></p>
                       </div>
                       <p><strong>What's Next:</strong></p>
                       <ul>
                           <li>Log in to your student portal to view your updated grades</li>
                           <li>Check your updated GPA and academic standing</li>
                           <li>Continue maintaining good academic performance</li>
                       </ul>
                       <p style='color: #059669;'><strong>Congratulations on completing your requirements!</strong> Keep up the excellent work.</p>
                       <br><p>Best regards,<br>LSPU Computer Studies<br>Academic Affairs Office</p>";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>
