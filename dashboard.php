<?php
session_start();

// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
$first_name = $_SESSION["first_name"];
$last_name = isset($_SESSION["last_name"]) ? $_SESSION["last_name"] : '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["logout"])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

// Database connection
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

$student_id = $_SESSION["id_number"] ?? '';

// Get stats
$pending = 0;
$completed = 0;
$inprogress = 0;
$inc_requests = 0;

if ($student_id) {
    $user_id = $_SESSION['user_id'] ?? 0;
    
    // Real-time INC detection - check grades for INC status
    if ($user_id > 0) {
        // Get enrolled subjects
        $subjects = [];
        $student_program = '';
        $result = $conn->query("SELECT ss.subject_code, ss.teacher_id, ss.semester, ss.program, ss.school_year, u.first_name, u.last_name, s.subject_name, s.units
                                FROM student_subjects ss
                                INNER JOIN users u ON ss.teacher_id = u.id_number
                                LEFT JOIN subjects s ON ss.subject_code = s.subject_code
                                WHERE ss.student_id='$student_id'
                                ORDER BY ss.subject_code ASC");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $subjects[] = $row;
                if (empty($student_program) && !empty($row['program'])) {
                    $student_program = $row['program'];
                }
            }
        }
        
        // Get grades with INC remarks for this student (only if no resolved request exists)
        $inc_subjects = [];
        $result = $conn->query("SELECT DISTINCT g.subject_code, g.teacher_id, g.remarks, g.notified, u.first_name, u.last_name, s.subject_name 
                                FROM grades g 
                                LEFT JOIN users u ON g.teacher_id = u.id_number 
                                LEFT JOIN subjects s ON g.subject_code = s.subject_code 
                                WHERE g.student_id='$student_id' AND g.remarks='INC'
                                AND NOT EXISTS (SELECT 1 FROM inc_requests ir WHERE ir.student_id='$student_id' AND ir.subject=CONCAT(g.subject_code, ' (', COALESCE(s.subject_name, g.subject_code), ')') AND ir.approved=1)");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $inc_subjects[$row['subject_code']] = $row;
            }
        }
        
        // Get previously resolved INC alerts to check for newly resolved ones
        $previously_resolved = [];
        $result = $conn->query("SELECT course FROM academic_alerts WHERE student_id='$student_id' AND alert_type='INC' AND is_resolved=0");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $previously_resolved[] = $row['course'];
            }
        }
        
        // Don't clear old INC alerts - only resolve them when actually resolved
        
        $current_inc_courses = [];
        

        
        // Check for resolved INC grades - only resolve when teacher gives actual final grade
        $resolved_check = $conn->query("SELECT DISTINCT g.subject_code, s.subject_name 
                                        FROM grades g 
                                        LEFT JOIN subjects s ON g.subject_code = s.subject_code 
                                        WHERE g.student_id='$student_id' 
                                        AND g.column_name = 'final_grade' 
                                        AND g.remarks != 'INC' 
                                        AND g.remarks IS NOT NULL 
                                        AND g.remarks IN ('PASSED', 'FAILED', 'CONDITIONAL')
                                        AND EXISTS (SELECT 1 FROM academic_alerts WHERE student_id='$student_id' AND course LIKE CONCAT(g.subject_code, '%') AND alert_type='INC' AND is_resolved=0)");
        
        if ($resolved_check && $resolved_check->num_rows > 0) {
            while ($resolved = $resolved_check->fetch_assoc()) {
                $subject_code = $resolved['subject_code'];
                $subject_name = $resolved['subject_name'] ?? $subject_code;
                $course_title = $subject_code . ' (' . $subject_name . ')';
                
                // Mark alert as resolved
                $conn->query("UPDATE academic_alerts SET is_resolved=1 WHERE student_id='$student_id' AND course='$course_title' AND alert_type='INC'");
                
                // Send resolution notification only once
                $check_notified = $conn->query("SELECT id FROM notifications WHERE user_id='$student_id' AND message LIKE '%resolved%' AND message LIKE '%$course_title%'");
                if (!$check_notified || $check_notified->num_rows == 0) {
                    $student_email = $_SESSION['email'] ?? '';
                    $student_full_name = $_SESSION['first_name'] . ' ' . ($_SESSION['last_name'] ?? '');
                    
                    if (!empty($student_email)) {
                        require_once 'send_email.php';
                        sendIncResolvedEmail($student_email, $student_full_name, $course_title);
                    }
                    
                    $notification_message = "✅ Good News: Your INCOMPLETE (INC) grade for $course_title has been resolved! Check your updated grades.";
                    $conn->query("INSERT INTO notifications (user_id, message, type, is_read) VALUES ('$student_id', '$notification_message', 'grade_resolved', 0)");
                }
            }
        }
    }
    
    // Pending: INC (not started) + Crediting (pending) + Unscheduled (pending) + OJT (pending)
    $result = $conn->query("SELECT COUNT(*) as count FROM inc_requests WHERE student_id='$student_id' AND approved=0 AND prof_approved=0 AND student_approved=0");
    if ($result) $pending = $result->fetch_assoc()['count'];
    $result = $conn->query("SELECT COUNT(*) as count FROM crediting_requests WHERE student_id='$student_id' AND status='pending'");
    if ($result) $pending += $result->fetch_assoc()['count'];
    $result = $conn->query("SELECT COUNT(*) as count FROM unscheduled_requests WHERE student_id='$student_id' AND status='pending'");
    if ($result) $pending += $result->fetch_assoc()['count'];
    $result = $conn->query("SELECT COUNT(*) as count FROM ojt_requests WHERE student_id='$student_id' AND status='pending'");
    if ($result) $pending += $result->fetch_assoc()['count'];
    
    // Completed: INC (approved) + Crediting (approved/dean_approved) + Unscheduled (approved) + OJT (approved)
    $result = $conn->query("SELECT COUNT(*) as count FROM inc_requests WHERE student_id='$student_id' AND approved=1");
    if ($result) $completed = $result->fetch_assoc()['count'];
    $result = $conn->query("SELECT COUNT(*) as count FROM crediting_requests WHERE student_id='$student_id' AND status IN ('approved', 'dean_approved')");
    if ($result) $completed += $result->fetch_assoc()['count'];
    $result = $conn->query("SELECT COUNT(*) as count FROM unscheduled_requests WHERE student_id='$student_id' AND status='approved'");
    if ($result) $completed += $result->fetch_assoc()['count'];
    $result = $conn->query("SELECT COUNT(*) as count FROM ojt_requests WHERE student_id='$student_id' AND status='approved'");
    if ($result) $completed += $result->fetch_assoc()['count'];
    
    // In Progress: INC (prof/student approved but not final) + Crediting (evaluating/preparing/sent_to_registrar/sent_to_dean)
    $result = $conn->query("SELECT COUNT(*) as count FROM inc_requests WHERE student_id='$student_id' AND (prof_approved=1 OR student_approved=1) AND approved=0");
    if ($result) $inprogress = $result->fetch_assoc()['count'];
    $result = $conn->query("SELECT COUNT(*) as count FROM crediting_requests WHERE student_id='$student_id' AND status IN ('evaluating', 'preparing_document', 'sent_to_registrar', 'sent_to_dean', 'approved')");
    if ($result) $inprogress += $result->fetch_assoc()['count'];
    
    // INC requests count (all types)
    $result = $conn->query("SELECT COUNT(*) as count FROM inc_requests WHERE student_id='$student_id'");
    if ($result) $inc_requests = $result->fetch_assoc()['count'];
    $result = $conn->query("SELECT COUNT(*) as count FROM unscheduled_requests WHERE student_id='$student_id'");
    if ($result) $inc_requests += $result->fetch_assoc()['count'];
    
    // Check if student has unresolved INC from grades
    $has_unresolved_inc = false;
    $inc_count = 0;
    $result = $conn->query("SELECT COUNT(*) as count FROM student_grades WHERE student_id='$student_id' AND has_inc=1 AND inc_resolved=0");
    if ($result) {
        $inc_count = $result->fetch_assoc()['count'];
        $has_unresolved_inc = $inc_count > 0;
    }
    
    // Get academic alerts directly from grades table (real-time)
    $inc_alerts = [];
    
    // Auto-create crediting for transferees, shifters, and returnees
    $student_type_result = $conn->query("SELECT student_type FROM users WHERE id_number='$student_id' LIMIT 1");
    if ($student_type_result && $row = $student_type_result->fetch_assoc()) {
        $student_type = $row['student_type'] ?? '';
        
        if (in_array($student_type, ['Transferee', 'Shifter', 'Returnee'])) {
            // Check if crediting alert already exists or if student has submitted request
            $existing_alert = $conn->query("SELECT id FROM crediting_alerts WHERE student_id='$student_id' AND is_resolved=0 LIMIT 1");
            $submitted_request = $conn->query("SELECT id FROM program_head_crediting WHERE student_id='$student_id' LIMIT 1");
            
            if ((!$existing_alert || $existing_alert->num_rows == 0) && (!$submitted_request || $submitted_request->num_rows == 0)) {
                $conn->query("INSERT INTO crediting_alerts (student_id, student_type, status, reason, intervention) 
                              VALUES ('$student_id', '$student_type', 'warning', 'Automatic crediting for $student_type', 'Submit required documents for crediting evaluation')");
                
                // Send email notification (optional)
                try {
                    $email_result = $conn->query("SELECT email, first_name FROM users WHERE id_number='$student_id'");
                    if ($email_result && $email_row = $email_result->fetch_assoc()) {
                        require_once 'send_email_interview.php';
                        @sendCreditingAlertEmail($email_row['email'], $email_row['first_name'], $student_type);
                    }
                } catch (Exception $e) {
                    // Email failed but continue
                }
            }
        }
        
        // Auto-create UNSCHEDULED alert for Irregular students
        if ($student_type == 'Irregular') {
            $unscheduled_check = $conn->query("SELECT id FROM academic_alerts WHERE student_id='$student_id' AND alert_type='UNSCHEDULED' AND is_resolved=0 LIMIT 1");
            $notif_check = $conn->query("SELECT id FROM notifications WHERE user_id='$student_id' AND type='unscheduled_required' LIMIT 1");
            
            if (!$unscheduled_check || $unscheduled_check->num_rows == 0) {
                $conn->query("INSERT INTO academic_alerts (student_id, course, grade, program_section, reason, intervention, instructor, semester, school_year, alert_type, is_resolved) 
                              VALUES ('$student_id', 'Unscheduled Subject Request', 'REQUIRED', 'B.S. Information Technology', 'Request for unscheduled subject offering', 'Submit request through the portal with required documents', 'Registrar Office', '', '', 'UNSCHEDULED', 0)");
                
                // Send notification only once
                if (!$notif_check || $notif_check->num_rows == 0) {
                    $conn->query("INSERT INTO notifications (user_id, message, type, is_read) 
                                  VALUES ('$student_id', 'Action Required: As an Irregular student, you can request unscheduled subject offerings. Please submit your request through the portal.', 'unscheduled_required', 0)");
                    
                    // Send email notification
                    $email_result = $conn->query("SELECT email, first_name FROM users WHERE id_number='$student_id'");
                    if ($email_result && $email_row = $email_result->fetch_assoc()) {
                        require_once 'PHPMailer/src/Exception.php';
                        require_once 'PHPMailer/src/PHPMailer.php';
                        require_once 'PHPMailer/src/SMTP.php';
                        
                        require_once 'email_config.php';
                        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                        try {
                            $mail->isSMTP();
                            $mail->Host = SMTP_HOST;
                            $mail->SMTPAuth = SMTP_AUTH;
                            $mail->Username = SMTP_USERNAME;
                            $mail->Password = SMTP_PASSWORD;
                            $mail->SMTPSecure = 'tls';
                            $mail->Port = SMTP_PORT;
                            $mail->SMTPOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true));
                            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                            $mail->addAddress($email_row['email'], $email_row['first_name']);
                            $mail->Subject = 'Action Required: Unscheduled Subject Request - LSPU CCS';
                            $mail->Body = "Dear {$email_row['first_name']},\n\nAs an Irregular student, you are eligible to request unscheduled subject offerings.\n\nProgram: B.S. Information Technology\nStatus: REQUIRED\n\nAction Required: Submit your request through the portal with the following documents:\n- Request Letter\n- Copy of Evaluation of Grades\n\nPlease log in to the student portal and submit your request as soon as possible.\n\nThank you.\nLSPU-CCS\nRegistrar Office";
                            $mail->send();
                        } catch (Exception $e) {
                            error_log('Unscheduled email error: ' . $e->getMessage());
                        }
                    }
                }
            }
        }
        
        // Auto-create admission interview alert for Freshmen (only if no request submitted)
        if ($student_type == 'Freshmen') {
            $interview_check = $conn->query("SELECT id, status FROM admission_interviews WHERE student_id='$student_id' LIMIT 1");
            $alert_check = $conn->query("SELECT id FROM academic_alerts WHERE student_id='$student_id' AND alert_type='INTERVIEW' AND is_resolved=0 LIMIT 1");
            $notif_check = $conn->query("SELECT id FROM notifications WHERE user_id='$student_id' AND type='interview_required' LIMIT 1");
            
            // Only create alert if no interview request exists
            if ((!$interview_check || $interview_check->num_rows == 0) && (!$alert_check || $alert_check->num_rows == 0)) {
                $conn->query("INSERT INTO academic_alerts (student_id, course, grade, program_section, reason, intervention, instructor, semester, school_year, alert_type, is_resolved) 
                              VALUES ('$student_id', 'Admission Interview', 'REQUIRED', 'B.S. Information Technology', 'Admission interview required for freshmen students', 'Submit interview request through the portal', 'Admission Office', '', '', 'INTERVIEW', 0)");
                
                // Send notification and email only once
                if (!$notif_check || $notif_check->num_rows == 0) {
                    $conn->query("INSERT INTO notifications (user_id, message, type, is_read) 
                                  VALUES ('$student_id', 'Action Required: As a Freshmen student, you need to request an admission interview. Please submit your request through the Admission Interview page.', 'interview_required', 0)");
                    
                    // Send email notification
                    $email_result = $conn->query("SELECT email, first_name FROM users WHERE id_number='$student_id'");
                    if ($email_result && $email_row = $email_result->fetch_assoc()) {
                        require_once 'PHPMailer/src/Exception.php';
                        require_once 'PHPMailer/src/PHPMailer.php';
                        require_once 'PHPMailer/src/SMTP.php';
                        require_once 'email_config.php';
                        
                        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
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
                            $mail->addAddress($email_row['email'], $email_row['first_name']);
                            
                            $mail->isHTML(true);
                            $mail->Subject = 'Action Required: Admission Interview - LSPU CCS';
                            $mail->Body = "<h2 style='color: #f59e0b;'>⚠️ Action Required: Admission Interview</h2>
                                           <p>Dear {$email_row['first_name']},</p>
                                           <p>As a <strong>Freshmen</strong> student, you are required to request an admission interview.</p>
                                           <div style='background: #fef3c7; padding: 15px; border-left: 4px solid #f59e0b; margin: 15px 0;'>
                                               <p><strong>Program:</strong> B.S. Information Technology</p>
                                               <p><strong>Status:</strong> REQUIRED</p>
                                               <p><strong>Action Required:</strong> Submit interview request through the portal</p>
                                           </div>
                                           <p>Please log in to the student portal and submit your interview request as soon as possible.</p>
                                           <br><p>Best regards,<br>LSPU Computer Studies<br>Admission Office</p>";
                            
                            $mail->send();
                        } catch (Exception $e) {}
                    }
                }
            }
        }
    }

    // Auto-detect midterm grades below 75
    $midterm_check = $conn->query("SELECT DISTINCT g.subject_code, g.grade, s.subject_name, u.first_name, u.last_name
                                   FROM grades g
                                   LEFT JOIN subjects s ON g.subject_code = s.subject_code
                                   LEFT JOIN users u ON g.teacher_id = u.id_number
                                   WHERE g.student_id='$student_id' AND g.column_name = 'midterm_total' AND CAST(g.grade AS DECIMAL(5,2)) < 75
                                   AND NOT EXISTS (SELECT 1 FROM notifications WHERE user_id = g.student_id AND message LIKE CONCAT('%', g.subject_code, '%') AND type = 'academic_warning')");
    
    if ($midterm_check && $midterm_check->num_rows > 0) {
        while($mid_row = $midterm_check->fetch_assoc()) {
            $midterm_grade = floatval($mid_row['grade']);
            $required_finals = (75 - ($midterm_grade * 0.4)) / 0.6;
            $required_finals = max(0, min(100, round($required_finals, 2)));
            
            $subject_code = $mid_row['subject_code'];
            $subject_name = $mid_row['subject_name'] ?? $subject_code;
            
            // Send email
            $email_result = $conn->query("SELECT email, first_name FROM users WHERE id_number='$student_id'");
            if ($email_result && $email_row = $email_result->fetch_assoc()) {
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
                    $mail->addAddress($email_row['email']);
                    $mail->Subject = 'Academic Warning: Low Midterm Grade';
                    $mail->Body = "Dear {$email_row['first_name']},\n\nYour midterm grade for $subject_code ($subject_name) is $midterm_grade.\n\nTo pass this subject, you need to get at least $required_finals in your finals exam.\n\nPlease study hard!\n\nLSPU-CCS";
                    $mail->send();
                } catch (Exception $e) {}
            }
            
            // Create notification
            $notif_msg = "Academic Warning: Your midterm grade for $subject_code is $midterm_grade. You need at least $required_finals in finals to pass.";
            $conn->query("INSERT INTO notifications (user_id, message, type, is_read) VALUES ('$student_id', '$notif_msg', 'academic_warning', 0)");
        }
    }
    
    // Auto-detect and create INC alerts when finals_Exam = 0 (only if no EXAM or INC alert exists)
    $exam_check = $conn->query("SELECT DISTINCT g.subject_code, s.subject_name, u.first_name, u.last_name, ss.program, ss.semester, ss.school_year
                                FROM grades g
                                LEFT JOIN subjects s ON g.subject_code = s.subject_code
                                LEFT JOIN users u ON g.teacher_id = u.id_number
                                LEFT JOIN student_subjects ss ON g.student_id = ss.student_id AND g.subject_code = ss.subject_code
                                WHERE g.student_id='$student_id' AND g.column_name = 'finals_Exam' AND g.grade = '0'
                                AND NOT EXISTS (SELECT 1 FROM academic_alerts WHERE student_id = g.student_id AND course LIKE CONCAT('%', g.subject_code, '%') AND alert_type IN ('INC', 'EXAM') AND is_resolved = 0)");
    
    if ($exam_check && $exam_check->num_rows > 0) {
        while($exam_row = $exam_check->fetch_assoc()) {
            $subject_code = $exam_row['subject_code'];
            $subject_name = $exam_row['subject_name'] ?? 'Intermediate Programming';
            $teacher_name = ($exam_row['last_name'] ?? 'Bernardino') . ', ' . ($exam_row['first_name'] ?? 'Mark');
            $course_title = $subject_code . ' (' . $subject_name . ')';
            
            // Create alert
            $conn->query("INSERT INTO academic_alerts (student_id, course, grade, program_section, reason, intervention, instructor, semester, school_year, alert_type, is_resolved) 
                          VALUES ('$student_id', '$course_title', 'INC', 'B. S. Information Technology', 'INC', 'Contact instructor: $teacher_name', '$teacher_name', '2nd', '2025 - 2026', 'INC', 0)");
            
            // Send email and notification
            $email_result = $conn->query("SELECT email, first_name FROM users WHERE id_number='$student_id'");
            if ($email_result && $email_row = $email_result->fetch_assoc()) {
                require_once 'configure_email.php';
                $subject = 'Academic Alert: Incomplete Grade';
                $message = "Dear {$email_row['first_name']},\n\nYou have an INCOMPLETE (INC) grade for $course_title due to missing final exam (score: 0).\n\nPlease contact your instructor: $teacher_name to complete the requirements.\n\nProgram: B.S. Information Technology\nSemester: 2nd 2025-2026\n\nThank you.\nLSPU-CCS";
                sendEmail($email_row['email'], $subject, $message, $email_row['first_name']);
            }
            
            // Create notification only if no EXAM alert exists
            $exam_alert_check = $conn->query("SELECT id FROM academic_alerts WHERE student_id='$student_id' AND course='$course_title' AND alert_type='EXAM' AND is_resolved=0");
            if (!$exam_alert_check || $exam_alert_check->num_rows == 0) {
                $notif_msg = "Academic Alert: You have an INCOMPLETE (INC) grade for $course_title. Contact instructor: $teacher_name to complete requirements.";
                $conn->query("INSERT INTO notifications (user_id, message, type, is_read) VALUES ('$student_id', '$notif_msg', 'academic_alert', 0)");
            }
        }
    }
    
    // Auto-resolve old INC alerts when EXAM alerts exist for same subject
    $resolve_old_inc = $conn->query("UPDATE academic_alerts a1 
                                     SET is_resolved = 1 
                                     WHERE a1.student_id = '$student_id' 
                                     AND a1.alert_type = 'INC' 
                                     AND a1.is_resolved = 0
                                     AND EXISTS (SELECT 1 FROM academic_alerts a2 
                                                WHERE a2.student_id = '$student_id' 
                                                AND a2.course = a1.course 
                                                AND a2.alert_type = 'EXAM' 
                                                AND a2.is_resolved = 0)");
    
    // Auto-resolve EXAM alerts when student gets final grade and send notification
    $grade_completion_check = $conn->query("SELECT DISTINCT aa.course, g.subject_code, g.grade, s.subject_name
                                            FROM academic_alerts aa
                                            JOIN grades g ON aa.student_id = g.student_id AND aa.course LIKE CONCAT('%', g.subject_code, '%')
                                            LEFT JOIN subjects s ON g.subject_code = s.subject_code
                                            WHERE aa.student_id = '$student_id' 
                                            AND aa.alert_type = 'EXAM' 
                                            AND aa.is_resolved = 0
                                            AND g.column_name = 'finals_Exam' 
                                            AND g.grade != '0'
                                            AND NOT EXISTS (SELECT 1 FROM notifications WHERE user_id = '$student_id' AND message LIKE CONCAT('%grade completed%', aa.course, '%'))");
    
    if ($grade_completion_check && $grade_completion_check->num_rows > 0) {
        while($grade_row = $grade_completion_check->fetch_assoc()) {
            $course_title = $grade_row['course'];
            $final_grade = $grade_row['grade'];
            
            // Send email notification
            $email_result = $conn->query("SELECT email, first_name FROM users WHERE id_number='$student_id'");
            if ($email_result && $email_row = $email_result->fetch_assoc()) {
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
                    $mail->addAddress($email_row['email']);
                    $mail->Subject = 'INC Grade Completed';
                    $mail->Body = "Dear {$email_row['first_name']},\n\nGood news! Your INCOMPLETE (INC) grade for $course_title has been completed with a final grade of $final_grade.\n\nPlease check your grades in the portal.\n\nThank you.\nLSPU-CCS";
                    $mail->send();
                } catch (Exception $e) {}
            }
            
            // Create portal notification
            $notif_msg = "✅ INC Grade Completed: Your INC for $course_title has been resolved with final grade $final_grade. Please check your grades.";
            $conn->query("INSERT INTO notifications (user_id, message, type, is_read) VALUES ('$student_id', '$notif_msg', 'grade_completed', 0)");
        }
    }
    
    // Resolve EXAM alerts after notifications are sent
    $resolve_exam_alerts = $conn->query("UPDATE academic_alerts aa 
                                         SET is_resolved = 1 
                                         WHERE aa.student_id = '$student_id' 
                                         AND aa.alert_type = 'EXAM' 
                                         AND aa.is_resolved = 0
                                         AND EXISTS (SELECT 1 FROM grades g 
                                                    WHERE g.student_id = '$student_id' 
                                                    AND aa.course LIKE CONCAT('%', g.subject_code, '%') 
                                                    AND g.column_name = 'finals_Exam' 
                                                    AND g.grade != '0')");
    

    
    // Resolve old INTERVIEW alerts if student has submitted request
    $conn->query("UPDATE academic_alerts SET is_resolved=1 
                  WHERE student_id='$student_id' 
                  AND alert_type='INTERVIEW' 
                  AND course='Admission Interview' 
                  AND EXISTS (SELECT 1 FROM admission_interviews WHERE student_id='$student_id')");
    
    // Get INC/EXAM/INTERVIEW/UNSCHEDULED alerts from academic_alerts table
    $result = $conn->query("SELECT aa.*, 
                                   CASE 
                                       WHEN ir.dean_approved = 1 THEN 'DEAN_APPROVED'
                                       WHEN ir.id IS NOT NULL AND ir.dean_approved = 0 THEN 'PENDING_DEAN'
                                       ELSE aa.alert_type
                                   END as display_type,
                                   ai.status as interview_status
                            FROM academic_alerts aa
                            LEFT JOIN inc_requests ir ON aa.student_id = ir.student_id AND aa.course = ir.subject AND ir.dean_approved IN (0,1)
                            LEFT JOIN admission_interviews ai ON aa.student_id = ai.student_id AND aa.alert_type = 'INTERVIEW'
                            WHERE aa.student_id='$student_id' AND aa.alert_type IN ('INC', 'EXAM', 'INTERVIEW', 'UNSCHEDULED') AND aa.is_resolved=0 
                            ORDER BY aa.created_at DESC");
    
    if ($result) {
        while($row = $result->fetch_assoc()) {
            $inc_alerts[] = [
                'course' => $row['course'],
                'grade' => $row['grade'],
                'program_section' => $row['program_section'],
                'reason' => $row['reason'],
                'intervention' => $row['intervention'],
                'instructor' => $row['instructor'],
                'semester' => $row['semester'],
                'school_year' => $row['school_year'],
                'alert_type' => $row['alert_type'],
                'display_type' => $row['display_type'],
                'interview_status' => $row['interview_status'] ?? null
            ];
        }
    }
    

    
    // Get crediting alerts from crediting_alerts table - only show if no request submitted
    $has_submitted_request = $conn->query("SELECT id FROM program_head_crediting WHERE student_id='$student_id' LIMIT 1");
    if (!$has_submitted_request || $has_submitted_request->num_rows == 0) {
        $crediting_result = $conn->query("SELECT ca.* FROM crediting_alerts ca 
                                          WHERE ca.student_id='$student_id' AND ca.is_resolved=0 
                                          ORDER BY ca.created_at DESC");
        if ($crediting_result) {
            while($row = $crediting_result->fetch_assoc()) {
                $inc_alerts[] = [
                    'course' => 'Subject Crediting',
                    'grade' => 'WARNING',
                    'program_section' => $row['program'],
                    'reason' => 'Subjects to be Credited: List subjects you want to be credited',
                    'intervention' => 'Upload Transcript of Records and provide previous school details',
                    'instructor' => 'Registrar Office',
                    'semester' => '',
                    'school_year' => '',
                    'alert_type' => 'CREDITING',
                    'display_type' => 'CREDITING',
                    'crediting_status' => $row['status'],
                    'student_type' => $row['student_type'],
                    'transcript_info' => 'Previous school name, course, and relevant details required'
                ];
            }
        }
    }
    
    // Auto-remove dean_approved crediting alerts after 24 hours
    $conn->query("UPDATE program_head_crediting 
                  SET status = 'completed' 
                  WHERE student_id='$student_id' 
                  AND status = 'dean_approved' 
                  AND updated_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    
    // Show submitted crediting requests status (exclude completed)
    $submitted_crediting = $conn->query("SELECT phc.*, 'PENDING' as display_status FROM program_head_crediting phc 
                                         WHERE phc.student_id='$student_id' AND phc.status != 'completed'
                                         ORDER BY phc.created_at DESC");
    if ($submitted_crediting) {
        while($row = $submitted_crediting->fetch_assoc()) {
            $status_message = '';
            $current_status = $row['status'];
            
            switch($current_status) {
                case 'pending':
                case 'warning':
                    $status_message = 'Your crediting request is pending Program Head evaluation.';
                    break;
                case 'approved':
                    $status_message = 'Your crediting request has been approved by Program Head and is pending Dean approval.';
                    break;
                case 'sent_to_dean':
                    $status_message = 'Your crediting request has been sent to Dean for final approval.';
                    break;
                case 'dean_approved':
                    $status_message = 'Your crediting request has been approved by Dean. Document is ready for download.';
                    break;
                default:
                    $status_message = 'Your crediting request is being processed.';
            }
            
            $inc_alerts[] = [
                'course' => 'Crediting Request Status',
                'grade' => strtoupper($current_status),
                'program_section' => 'B.S. Information Technology',
                'reason' => $status_message,
                'intervention' => 'Current Status: ' . ucfirst(str_replace('_', ' ', $current_status)),
                'instructor' => $current_status == 'dean_approved' ? 'Completed' : 'Program Head/Dean',
                'semester' => '',
                'school_year' => '',
                'alert_type' => 'CREDITING',
                'display_type' => 'CREDITING_SUBMITTED',
                'crediting_status' => $current_status,
                'student_type' => $row['student_type']
            ];
        }
    }
    

    

    
    // Recent activity from user_activities table
    $activities = [];
    $result = $conn->query("SELECT activity_title as title, created_at as date, status FROM user_activities WHERE student_id='$student_id' ORDER BY created_at DESC LIMIT 5");
    if ($result) {
        while($row = $result->fetch_assoc()) {
            $activities[] = [
                'title' => $row['title'],
                'date' => $row['date'],
                'status' => $row['status']
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - LSPU CCS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fa;
            height: 100vh;
            color: #333;
            overflow: hidden;
            transition: background 0.3s ease, color 0.3s ease;
        }

        body.dark-mode {
            background: #1a202c;
            color: #e2e8f0;
        }

        body.dark-mode .dashboard-section,
        body.dark-mode .chart-card {
            background: #2d3748;
            border-color: #4a5568;
        }

        body.dark-mode .dashboard-section h1,
        body.dark-mode .chart-card h3,
        body.dark-mode .stat-box .stat-value,
        body.dark-mode .alert-title,
        body.dark-mode .activity-title {
            color: #e2e8f0;
        }

        body.dark-mode .dashboard-section p,
        body.dark-mode .stat-box .stat-label,
        body.dark-mode .alert-desc,
        body.dark-mode .activity-date {
            color: #cbd5e0;
        }

        body.dark-mode .stat-box {
            background: #374151;
            border-color: #4a5568;
        }

        body.dark-mode .activity-item {
            background: #374151;
            border-left-color: #667eea;
        }

        body.dark-mode .alert-item.alert-danger {
            background: #7f1d1d;
        }

        body.dark-mode .alert-item.alert-warning {
            background: #78350f;
        }

        body.dark-mode .alert-item.alert-success {
            background: #064e3b;
        }

        body.dark-mode .alert-danger .alert-title,
        body.dark-mode .alert-danger .alert-desc {
            color: #fecaca;
        }

        body.dark-mode .alert-warning .alert-title,
        body.dark-mode .alert-warning .alert-desc {
            color: #fde68a;
        }

        body.dark-mode .alert-success .alert-title,
        body.dark-mode .alert-success .alert-desc {
            color: #a7f3d0;
        }

        .main-container {
            margin-left: 260px;
            margin-top: 65px;
            padding: 2rem 2.5rem;
            height: calc(100vh - 65px);
            overflow-y: auto;
            transition: margin-left 0.3s ease;
        }

        .main-container.collapsed {
            margin-left: 70px;
        }

        .dashboard-section {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 5px solid #667eea;
            animation: fadeInDown 0.6s ease;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dashboard-section h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .dashboard-section h1 span {
            color: #667eea;
        }

        .dashboard-section p {
            font-size: 0.95rem;
            color: #718096;
            margin-bottom: 1rem;
        }

        .quick-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .stat-box {
            background: #f7fafc;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            border: 1px solid #e2e8f0;
            animation: fadeInUp 0.6s ease;
        }

        .stat-box:nth-child(1) { animation-delay: 0.1s; opacity: 0; animation-fill-mode: forwards; }
        .stat-box:nth-child(2) { animation-delay: 0.2s; opacity: 0; animation-fill-mode: forwards; }
        .stat-box:nth-child(3) { animation-delay: 0.3s; opacity: 0; animation-fill-mode: forwards; }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-box i {
            font-size: 1.5rem;
            color: #667eea;
            margin-bottom: 0.5rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        .stat-box .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
            animation: countUp 1s ease;
        }

        @keyframes countUp {
            from { opacity: 0; transform: scale(0.5); }
            to { opacity: 1; transform: scale(1); }
        }

        .stat-box .stat-label {
            font-size: 0.8rem;
            color: #718096;
            margin-top: 0.25rem;
        }

        .analytics-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .chart-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            animation: fadeInUp 0.6s ease;
        }

        .analytics-section .chart-card:nth-child(1) { animation-delay: 0.4s; opacity: 0; animation-fill-mode: forwards; }
        .analytics-section .chart-card:nth-child(2) { animation-delay: 0.5s; opacity: 0; animation-fill-mode: forwards; }

        .chart-card h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 1.5rem;
        }

        .alert-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            border-left: 4px solid;
            animation: slideInLeft 0.5s ease;
        }

        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .alert-item.alert-danger {
            background: #fee2e2;
            border-left-color: #ef4444;
        }

        .alert-item.alert-warning {
            background: #fef3c7;
            border-left-color: #f59e0b;
        }

        .alert-item.alert-success {
            background: #d1fae5;
            border-left-color: #10b981;
        }

        .alert-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .alert-danger .alert-icon {
            background: #ef4444;
            color: white;
        }

        .alert-warning .alert-icon {
            background: #f59e0b;
            color: white;
        }

        .alert-success .alert-icon {
            background: #10b981;
            color: white;
        }

        .alert-content {
            flex: 1;
        }

        .alert-title {
            font-weight: 700;
            font-size: 0.95rem;
            margin-bottom: 0.25rem;
        }

        .alert-danger .alert-title { color: #991b1b; }
        .alert-warning .alert-title { color: #92400e; }
        .alert-success .alert-title { color: #065f46; }

        .alert-desc {
            font-size: 0.85rem;
            line-height: 1.5;
        }

        .alert-danger .alert-desc { color: #7f1d1d; }
        .alert-warning .alert-desc { color: #78350f; }
        .alert-success .alert-desc { color: #064e3b; }

        .alert-action {
            display: inline-block;
            margin-top: 0.75rem;
            padding: 0.5rem 1rem;
            background: #ef4444;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .alert-action:hover {
            background: #dc2626;
            transform: translateX(5px);
        }
        
        .alert-action.crediting {
            background: #ef4444;
        }
        
        .alert-action.crediting:hover {
            background: #dc2626;
        }

        .recent-activity {
            list-style: none;
        }

        .activity-item {
            padding: 1rem;
            border-left: 3px solid #667eea;
            background: #f7fafc;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .activity-item .activity-title {
            font-weight: 600;
            color: #2d3748;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .activity-item .activity-date {
            font-size: 0.75rem;
            color: #718096;
        }

        .activity-item .activity-status {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }

        .activity-item .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .activity-item .status-completed {
            background: #d1fae5;
            color: #065f46;
        }

        @media (max-width: 768px) {
            .main-container {
                margin-left: 0;
                padding: 1rem;
            }

            .dashboard-section {
                padding: 1.5rem;
            }

            .dashboard-section h1 {
                font-size: 1.3rem;
            }

            .dashboard-section p {
                font-size: 0.85rem;
            }

            .quick-stats {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }

            .analytics-section {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .chart-card {
                padding: 1.25rem;
            }

            .chart-card h3 {
                font-size: 1rem;
            }

            .alert-item {
                flex-direction: column;
                padding: 1rem;
            }

            .alert-icon {
                width: 35px;
                height: 35px;
                font-size: 1rem;
            }

            .alert-title {
                font-size: 0.9rem;
            }

            .alert-desc {
                font-size: 0.8rem;
            }
        }

        @media (max-width: 480px) {
            .dashboard-section {
                padding: 1.25rem;
            }

            .dashboard-section h1 {
                font-size: 1.1rem;
            }

            .dashboard-section p {
                font-size: 0.8rem;
            }

            .stat-box {
                padding: 0.75rem;
            }

            .stat-box .stat-value {
                font-size: 1.25rem;
            }

            .stat-box .stat-label {
                font-size: 0.75rem;
            }

            .chart-card {
                padding: 1rem;
            }

            .alert-action {
                font-size: 0.8rem;
                padding: 0.4rem 0.8rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main class="main-container" id="mainContainer">
        <section class="dashboard-section">
            <h1>Welcome, <span><?php echo htmlspecialchars($first_name); ?>!</span></h1>
            <p>Access student services and manage your academic requirements through the Citizen's Charter portal.</p>
            <div style="margin-top: 1rem;">
                <a href="export_student_report.php" style="background: #10b981; color: white; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; font-weight: 600; transition: all 0.3s ease;" onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10b981'">
                    <i class="fas fa-file-pdf"></i> Download Academic Report (PDF)
                </a>
            </div>
            
            <div class="quick-stats">
                <div class="stat-box">
                    <i class="fas fa-tasks"></i>
                    <div class="stat-value"><?php echo $pending; ?></div>
                    <div class="stat-label">Pending Requests</div>
                </div>
                <div class="stat-box">
                    <i class="fas fa-check-circle"></i>
                    <div class="stat-value"><?php echo $completed; ?></div>
                    <div class="stat-label">Completed</div>
                </div>
                <div class="stat-box">
                    <i class="fas fa-clock"></i>
                    <div class="stat-value"><?php echo $inprogress; ?></div>
                    <div class="stat-label">In Progress</div>
                </div>
            </div>
        </section>

        <div class="analytics-section">
            <div class="chart-card">
                <h3><i class="fas fa-exclamation-triangle"></i> Academic Alerts</h3>
                <?php
                // Check for OJT eligibility notifications and submitted documents
                $conn_ojt = new mysqli("localhost", "root", "", "student_services");
                if (!$conn_ojt->connect_error) {
                    $conn_ojt->query("CREATE TABLE IF NOT EXISTS student_notifications (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        student_id VARCHAR(50),
                        title VARCHAR(255),
                        message TEXT,
                        type VARCHAR(50),
                        is_read TINYINT DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )");
                    
                    // Auto-create OJT eligibility notification for 4th year 1st semester students
                    $conn_student = new mysqli("localhost", "root", "", "student_services");
                    if (!$conn_student->connect_error) {
                        $student_check = $conn_student->query("SELECT year_level, semester FROM student_subjects WHERE student_id='$student_id' ORDER BY id DESC LIMIT 1");
                        if ($student_check && $student_row = $student_check->fetch_assoc()) {
                            if ($student_row['year_level'] === '4th Year' && $student_row['semester'] === '1st') {
                                // Check if notification already exists
                                $notif_check = $conn_ojt->query("SELECT id FROM student_notifications WHERE student_id='$student_id' AND type='ojt_eligible' LIMIT 1");
                                if (!$notif_check || $notif_check->num_rows == 0) {
                                    $title = "OJT Deployment Now Available";
                                    $message = "Congratulations! You are now eligible to submit your OJT deployment requirements. As a 4th Year 1st Semester student, you can now prepare and submit your OJT documents through the portal.";
                                    $conn_ojt->query("INSERT INTO student_notifications (student_id, title, message, type, created_at) VALUES ('$student_id', '$title', '$message', 'ojt_eligible', NOW())");
                                }
                            }
                        }
                        $conn_student->close();
                    }
                    
                    // Check for submitted OJT documents
                    $ojt_submission = null;
                    $result = $conn_ojt->query("SELECT * FROM ojt_requests WHERE student_id='$student_id' ORDER BY created_at DESC LIMIT 1");
                    if ($result && $result->num_rows > 0) {
                        $ojt_submission = $result->fetch_assoc();
                    }
                    
                    if ($ojt_submission) {
                        // Show submitted documents
                        $submitted_docs = [];
                        $doc_fields = [
                            'resume_file' => 'Student Resume',
                            'parent_consent' => 'Parent Consent (Notarized)',
                            'enrollment_form' => 'Enrollment/Registration Form/ID',
                            'medical_cert' => 'Medical Certificate',
                            'letter_inquiry' => 'Letter of Inquiry',
                            'letter_response' => 'Letter of Response',
                            'application_letter' => 'Application Letter',
                            'recommendation_letter' => 'Recommendation Letter',
                            'acceptance_letter' => 'Acceptance Letter',
                            'internship_plan' => 'Internship Plan/Time Frame',
                            'internship_contract_lspu' => 'Internship Contract - LSPU',
                            'internship_contract_company' => 'Internship Contract - Company',
                            'moa_draft' => 'MOA DRAFT',
                            'certificate_employment' => 'Certificate of Employment'
                        ];
                        
                        foreach ($doc_fields as $field => $label) {
                            if (!empty($ojt_submission[$field])) {
                                $submitted_docs[] = $label;
                            }
                        }
                        
                        $status_color = $ojt_submission['status'] == 'approved' ? 'success' : ($ojt_submission['status'] == 'pending' ? 'warning' : 'danger');
                        $status_icon = $ojt_submission['status'] == 'approved' ? 'fa-check-circle' : ($ojt_submission['status'] == 'pending' ? 'fa-clock' : 'fa-exclamation-circle');
                ?>
                <div class="alert-item alert-<?php echo $status_color; ?>">
                    <div class="alert-icon"><i class="fas <?php echo $status_icon; ?>"></i></div>
                    <div class="alert-content">
                        <div class="alert-title">OJT Deployment Request - <?php echo ucfirst($ojt_submission['status']); ?></div>
                        <div class="alert-desc">
                            <strong>Submitted:</strong> <?php echo date('M d, Y - h:i A', strtotime($ojt_submission['created_at'])); ?><br>
                            <strong>Documents Submitted (<?php echo count($submitted_docs); ?>/14):</strong><br>
                            <?php foreach($submitted_docs as $doc): ?>
                                <span style="display: inline-block; background: #10b981; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; margin: 2px;">✓ <?php echo $doc; ?></span>
                            <?php endforeach; ?>
                            <?php if ($ojt_submission['status'] == 'pending'): ?>
                                <br><strong>Status:</strong> Pending Dean review and approval
                            <?php elseif ($ojt_submission['status'] == 'approved'): ?>
                                <br><strong>Status:</strong> Approved - Ready for OJT deployment
                            <?php endif; ?>
                        </div>
                        <?php if (count($submitted_docs) < 14): ?>
                            <a href="deployment_ojt.php" class="alert-action" style="background: #f59e0b;">Complete Remaining Documents <i class="fas fa-arrow-right"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php 
                    } else {
                        // Show OJT eligibility notifications if no submission yet
                        $ojt_notifications = [];
                        $result = $conn_ojt->query("SELECT * FROM student_notifications WHERE student_id='$student_id' AND type='ojt_eligible' AND is_read=0 ORDER BY created_at DESC");
                        if ($result) {
                            while ($row = $result->fetch_assoc()) {
                                $ojt_notifications[] = $row;
                            }
                        }
                        
                        // Display OJT notifications
                        foreach($ojt_notifications as $ojt_notif):
                ?>
                <div class="alert-item alert-success">
                    <div class="alert-icon"><i class="fas fa-graduation-cap"></i></div>
                    <div class="alert-content">
                        <div class="alert-title"><?php echo htmlspecialchars($ojt_notif['title']); ?></div>
                        <div class="alert-desc"><?php echo htmlspecialchars($ojt_notif['message']); ?></div>
                        <a href="deployment_ojt.php" class="alert-action" style="background: #10b981;">Submit OJT Requirements <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                <?php 
                        endforeach;
                    }
                    $conn_ojt->close();
                }
                ?>
                <?php if (!empty($inc_alerts)): ?>
                    <?php 
                    // Get subjects with existing requests to avoid showing Submit button
                    $requested_subjects = [];
                    $req_check = $conn->query("SELECT subject FROM inc_requests WHERE student_id='$student_id' AND dean_approved=0");
                    if ($req_check && $req_check->num_rows > 0) {
                        while($row = $req_check->fetch_assoc()) {
                            $requested_subjects[] = $row['subject'];
                        }
                    }
                    ?>
                    <?php foreach($inc_alerts as $alert): ?>
                    <?php 
                    $has_pending_request = in_array($alert['course'], $requested_subjects) || $alert['alert_type'] == 'PENDING_INC';
                    ?>
                    <?php 
                    $alert_class = 'alert-danger';
                    $alert_icon = 'fa-exclamation-circle';
                    $alert_title_prefix = 'INC Grade - ';
                    $show_submit_button = true;
                    
                    if ($alert['alert_type'] == 'EXAM') {
                        $alert_class = 'alert-success';
                        $alert_icon = 'fa-calendar-check';
                        $alert_title_prefix = 'Exam Schedule - ';
                        $show_submit_button = false;
                    } elseif ($alert['alert_type'] == 'INTERVIEW') {
                        if (!empty($alert['interview_status'])) {
                            if ($alert['interview_status'] == 'scheduled') {
                                $alert_class = 'alert-success';
                                $alert_icon = 'fa-check-circle';
                                $alert_title_prefix = 'Interview Scheduled - ';
                                $show_submit_button = false;
                            } else {
                                $alert_class = 'alert-warning';
                                $alert_icon = 'fa-clock';
                                $alert_title_prefix = 'Interview Request Pending - ';
                                $show_submit_button = false;
                            }
                        } else {
                            $alert_class = 'alert-danger';
                            $alert_icon = 'fa-exclamation-circle';
                            $alert_title_prefix = '';
                            $show_submit_button = true;
                        }
                    } elseif ($alert['alert_type'] == 'UNSCHEDULED') {
                        // Check if student has submitted request
                        $unsch_check = $conn->query("SELECT status FROM unscheduled_requests WHERE student_id='$student_id' ORDER BY date_submitted DESC LIMIT 1");
                        if ($unsch_check && $unsch_row = $unsch_check->fetch_assoc()) {
                            if ($unsch_row['status'] == 'approved') {
                                $alert_class = 'alert-success';
                                $alert_icon = 'fa-check-circle';
                            } else {
                                $alert_class = 'alert-warning';
                                $alert_icon = 'fa-clock';
                            }
                        } else {
                            $alert_class = 'alert-danger';
                            $alert_icon = 'fa-calendar-alt';
                        }
                        $alert_title_prefix = '';
                        $show_submit_button = true;
                    } elseif ($alert['alert_type'] == 'CREDITING') {
                        if ($alert['display_type'] == 'CREDITING_SUBMITTED') {
                            // Check if dean_approved status
                            if (isset($alert['crediting_status']) && $alert['crediting_status'] == 'dean_approved') {
                                $alert_class = 'alert-success';
                                $alert_icon = 'fa-check-circle';
                            } else {
                                $alert_class = 'alert-warning';
                                $alert_icon = 'fa-clock';
                            }
                            $alert_title_prefix = '';
                            $show_submit_button = false;
                        } elseif ($alert['display_type'] == 'CREDITING_PENDING') {
                            $alert_class = 'alert-warning';
                            $alert_icon = 'fa-clock';
                            $alert_title_prefix = 'Crediting Request Submitted - ';
                            $show_submit_button = false;
                        } else {
                            $alert_class = 'alert-danger';
                            $alert_icon = 'fa-graduation-cap';
                            $alert_title_prefix = 'Subject Crediting - ';
                            $show_submit_button = true;
                        }
                    } elseif ($alert['display_type'] == 'DEAN_APPROVED') {
                        $alert_class = 'alert-success';
                        $alert_icon = 'fa-check-circle';
                        $alert_title_prefix = 'Request Approved - ';
                        $alert['intervention'] = 'Your INC request has been approved by the Dean. Wait for exam schedule from instructor.';
                        $show_submit_button = false;
                    } elseif ($alert['display_type'] == 'PENDING_DEAN' || $has_pending_request) {
                        $alert_class = 'alert-warning';
                        $alert_icon = 'fa-clock';
                        $alert_title_prefix = 'INC Request Pending - ';
                        $alert['intervention'] = 'Your INC request is pending Dean approval.';
                        $show_submit_button = false;
                    }
                    ?>
                    <div class="alert-item <?php echo $alert_class; ?>">
                        <div class="alert-icon"><i class="fas <?php echo $alert_icon; ?>"></i></div>
                        <div class="alert-content">
                            <?php if ($alert['display_type'] == 'CREDITING_SUBMITTED'): ?>
                                <div class="alert-title"><?php echo htmlspecialchars($alert['course']); ?></div>
                                <div class="alert-desc"><?php echo htmlspecialchars($alert['reason']); ?></div>
                            <?php else: ?>
                                <div class="alert-title"><?php echo $alert_title_prefix; ?><?php echo htmlspecialchars($alert['course']); ?></div>
                                <div class="alert-desc">
                                    <strong>Program:</strong> <?php echo htmlspecialchars($alert['program_section']); ?><br>
                                    <?php if ($alert['alert_type'] == 'CREDITING'): ?>
                                        <strong>Student Type:</strong> <?php echo htmlspecialchars($alert['student_type'] ?? 'Transferee'); ?><br>
                                        <strong>Status:</strong> <?php echo ucfirst($alert['crediting_status'] ?? 'pending'); ?><br>
                                        <strong>Requirements:</strong> <?php echo htmlspecialchars($alert['reason']); ?><br>
                                        <?php if (!empty($alert['transcript_info'])): ?>
                                        <strong>Transcript Info:</strong> <?php echo htmlspecialchars($alert['transcript_info']); ?><br>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if (!empty($alert['semester']) || !empty($alert['school_year'])): ?>
                                        <strong>Semester:</strong> <?php echo htmlspecialchars(trim(($alert['semester'] ?? '') . ' ' . ($alert['school_year'] ?? ''))); ?><br>
                                        <?php endif; ?>
                                        <strong>Reason:</strong> <?php echo htmlspecialchars($alert['reason']); ?><br>
                                    <?php endif; ?>
                                    <strong>Action Required:</strong> <?php echo htmlspecialchars($alert['intervention']); ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($show_submit_button): ?>
                                <?php if ($alert['alert_type'] == 'CREDITING'): ?>
                                    <a href="crediting.php" class="alert-action" style="background: #ef4444;">Submit Crediting <i class="fas fa-arrow-right"></i></a>
                                <?php elseif ($alert['alert_type'] == 'INTERVIEW'): ?>
                                    <a href="admission_interview.php" class="alert-action" style="background: #ef4444;">Request Interview <i class="fas fa-arrow-right"></i></a>
                                <?php elseif ($alert['alert_type'] == 'UNSCHEDULED'): ?>
                                    <?php
                                    $unsch_check = $conn->query("SELECT id, status FROM unscheduled_requests WHERE student_id='$student_id' ORDER BY date_submitted DESC LIMIT 1");
                                    if ($unsch_check && $unsch_row = $unsch_check->fetch_assoc()) {
                                        if ($unsch_row['status'] == 'approved') {
                                            echo '<a href="generate_unscheduled_document.php?id=' . $unsch_row['id'] . '&action=view" target="_blank" class="alert-action" style="background: #10b981;">Download Document <i class="fas fa-download"></i></a>';
                                        } else {
                                            echo '<span style="display: inline-block; padding: 0.5rem 1rem; background: #fef3c7; color: #92400e; border-radius: 6px; font-weight: 600; font-size: 0.85rem; border: 1px solid #f59e0b;">✓ Request submitted - Pending Dean approval</span>';
                                        }
                                    } else {
                                        echo '<a href="unscheduled_subjects.php" class="alert-action" style="background: #ef4444;">Submit Request <i class="fas fa-arrow-right"></i></a>';
                                    }
                                    ?>
                                <?php else: ?>
                                    <a href="inc_removal.php" class="alert-action">Submit Request <i class="fas fa-arrow-right"></i></a>
                                <?php endif; ?>
                            <?php elseif ($alert['alert_type'] == 'CREDITING' && $alert['crediting_status'] == 'dean_approved'): ?>
                                <a href="#" onclick="downloadCreditingDocument()" class="alert-action" style="background: #10b981;">Download Document <i class="fas fa-download"></i></a>
                            <?php elseif ($alert['alert_type'] == 'CREDITING'): ?>
                                <span style="color: #f59e0b; font-weight: 600; font-size: 0.85rem;">✓ Crediting request submitted - Status: <?php echo ucfirst(str_replace('_', ' ', $alert['crediting_status'])); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if ($has_unresolved_inc): ?>
                <div class="alert-item alert-danger">
                    <div class="alert-icon"><i class="fas fa-exclamation-circle"></i></div>
                    <div class="alert-content">
                        <div class="alert-title">Unresolved INC Grade<?php echo $inc_count > 1 ? 's' : ''; ?> (<?php echo $inc_count; ?>)</div>
                        <div class="alert-desc">You have <?php echo $inc_count; ?> incomplete grade<?php echo $inc_count > 1 ? 's' : ''; ?> that need<?php echo $inc_count > 1 ? '' : 's'; ?> to be resolved. Please submit an INC removal request.</div>
                        <a href="inc_removal.php" class="alert-action">Submit Request <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                <?php endif; ?>
                

                
                <?php
                // Get student_type from users table
                $student_type = '';
                $student_type_result = $conn->query("SELECT student_type FROM users WHERE id_number='$student_id' LIMIT 1");
                if ($student_type_result && $row = $student_type_result->fetch_assoc()) {
                    $student_type = $row['student_type'] ?? '';
                }
                
                // Auto-create crediting for transferees, shifters, and returnees (only once)
                if (in_array($student_type, ['Transferee', 'Shifter', 'Returnee'])) {
                    // Check if crediting alert already exists to prevent duplicates
                    $existing_alert = $conn->query("SELECT id FROM academic_alerts WHERE student_id='$student_id' AND alert_type='CREDITING' AND is_resolved=0 LIMIT 1");
                    
                    if (!$existing_alert || $existing_alert->num_rows == 0) {
                        // Get student's program and current semester info
                        $student_info = $conn->query("SELECT program, semester, school_year FROM student_subjects WHERE student_id='$student_id' LIMIT 1");
                        $program = 'BSIT';
                        $semester = '';
                        $school_year = '';
                        if ($student_info && $info_row = $student_info->fetch_assoc()) {
                            $program = $info_row['program'] ?? 'BSIT';
                            $semester = $info_row['semester'] ?? '';
                            $school_year = $info_row['school_year'] ?? '';
                        }
                        
                        // Create academic alert for crediting (only once)
                        $conn->query("INSERT INTO academic_alerts (student_id, course, grade, program_section, reason, intervention, instructor, semester, school_year, alert_type, is_resolved) 
                                      VALUES ('$student_id', 'Subject Crediting', 'PENDING', '$program', 'Automatic crediting for $student_type', 'Submit required documents for crediting evaluation', 'Registrar Office', '$semester', '$school_year', 'CREDITING', 0)");
                        
                        // Send notification only once
                        $existing_notification = $conn->query("SELECT id FROM notifications WHERE user_id='$student_id' AND type='crediting_alert' LIMIT 1");
                        if (!$existing_notification || $existing_notification->num_rows == 0) {
                            $notification_message = "Crediting: As a $student_type, your crediting request has been submitted. Please provide required documents.";
                            $conn->query("INSERT INTO notifications (user_id, message, type, is_read) VALUES ('$student_id', '$notification_message', 'crediting_alert', 0)");
                        }
                    }
                }
                
                // Check for crediting eligibility
                $crediting_eligible = false;
                $crediting_count = 0;
                
                // Check from crediting_requests table
                $result = $conn->query("SELECT COUNT(*) as count FROM crediting_requests WHERE student_id='$student_id' AND status='pending'");
                if ($result) {
                    $crediting_count = $result->fetch_assoc()['count'];
                    $crediting_eligible = $crediting_count > 0;
                }
                
                // Also check academic_alerts for crediting
                if (!$crediting_eligible) {
                    $result = $conn->query("SELECT COUNT(*) as count FROM academic_alerts WHERE student_id='$student_id' AND alert_type='CREDITING' AND is_resolved=0");
                    if ($result) {
                        $crediting_count += $result->fetch_assoc()['count'];
                        $crediting_eligible = $crediting_count > 0;
                    }
                }
                ?>
                

                
                <?php if (empty($inc_alerts) && !$has_unresolved_inc && !$crediting_eligible): ?>
                <div class="alert-item alert-success">
                    <div class="alert-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="alert-content">
                        <div class="alert-title">All Clear!</div>
                        <div class="alert-desc">You have no pending academic issues at the moment.</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="chart-card">
                <h3><i class="fas fa-history"></i> Recent Activity</h3>
                <ul class="recent-activity">
                    <?php if (!empty($activities)): ?>
                        <?php foreach($activities as $activity): ?>
                        <li class="activity-item">
                            <div class="activity-title"><?php echo htmlspecialchars($activity['title']); ?></div>
                            <div class="activity-date"><?php echo date('M d, Y - h:i A', strtotime($activity['date'])); ?></div>
                            <?php if ($activity['status'] == 'completed'): ?>
                                <span class="activity-status status-completed">Completed</span>
                            <?php elseif ($activity['status'] == 'inprogress'): ?>
                                <span class="activity-status status-pending">In Progress</span>
                            <?php elseif ($activity['status'] == 'pending'): ?>
                                <span class="activity-status status-pending">Pending</span>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="activity-item">
                            <div class="activity-title">No recent activity</div>
                            <div class="activity-date">Start using services to see your activity here</div>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </main>

    <script>
        function downloadCreditingDocument() {
            // Get the student's crediting request ID
            fetch('get_student_crediting_id.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.id) {
                        window.open(`generate_crediting_document.php?id=${data.id}&action=download`, '_blank');
                    } else {
                        alert('Document not found or not ready for download.');
                    }
                })
                .catch(error => {
                    alert('Error downloading document. Please try again.');
                });
        }
    </script>

    <?php include 'chatbot.php'; ?>
</body>
</html>
