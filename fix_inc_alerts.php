<?php
// Fix INC alerts - ensure all students with finals_Exam = 0 have INC alerts
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

echo "<h3>Checking for students with INC grades (finals_Exam = 0)...</h3>";

// Find all students with finals_Exam = 0
$result = $conn->query("SELECT DISTINCT g.student_id, g.subject_code, s.subject_name, u.first_name as teacher_first, u.last_name as teacher_last, ss.program, ss.semester, ss.school_year
                        FROM grades g
                        LEFT JOIN subjects s ON g.subject_code = s.subject_code
                        LEFT JOIN users u ON g.teacher_id = u.id_number
                        LEFT JOIN student_subjects ss ON g.student_id = ss.student_id AND g.subject_code = ss.subject_code
                        WHERE g.column_name = 'finals_Exam' AND g.grade = '0'
                        ORDER BY g.student_id, g.subject_code");

if ($result && $result->num_rows > 0) {
    echo "<p>Found " . $result->num_rows . " student(s) with INC grades</p>";
    
    while ($row = $result->fetch_assoc()) {
        $student_id = $row['student_id'];
        $subject_code = $row['subject_code'];
        $subject_name = $row['subject_name'] ?? $subject_code;
        $teacher_name = ($row['teacher_last'] ?? 'Unknown') . ', ' . ($row['teacher_first'] ?? 'Teacher');
        $course_title = $subject_code . ' (' . $subject_name . ')';
        $program = $row['program'] ?? 'B.S. Information Technology';
        $semester = $row['semester'] ?? '2nd';
        $school_year = $row['school_year'] ?? '2025-2026';
        
        // Check if alert already exists
        $check = $conn->query("SELECT id FROM academic_alerts WHERE student_id='$student_id' AND course='$course_title' AND alert_type='INC' AND is_resolved=0");
        
        if (!$check || $check->num_rows == 0) {
            // Create INC alert
            $insert = $conn->query("INSERT INTO academic_alerts (student_id, course, grade, program_section, reason, intervention, instructor, semester, school_year, alert_type, is_resolved, created_at) 
                                   VALUES ('$student_id', '$course_title', 'INC', '$program', 'INC', 'Contact instructor: $teacher_name', '$teacher_name', '$semester', '$school_year', 'INC', 0, NOW())");
            
            if ($insert) {
                echo "<p style='color: green;'>✓ Created INC alert for $student_id - $course_title</p>";
                
                // Create notification
                $notif_msg = "Academic Alert: You have an INCOMPLETE (INC) grade for $course_title. Contact instructor: $teacher_name to complete requirements.";
                $conn->query("INSERT INTO notifications (user_id, message, type, is_read, created_at) VALUES ('$student_id', '$notif_msg', 'academic_alert', 0, NOW())");
                
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
                        $mail->Subject = 'Academic Alert: Incomplete Grade';
                        $mail->Body = "Dear {$email_row['first_name']},\n\nYou have an INCOMPLETE (INC) grade for $course_title due to missing final exam (score: 0).\n\nPlease contact your instructor: $teacher_name to complete the requirements.\n\nProgram: $program\nSemester: $semester $school_year\n\nThank you.\nLSPU-CCS";
                        $mail->send();
                        echo "<p style='color: blue;'>  ✓ Email sent to {$email_row['email']}</p>";
                    } catch (Exception $e) {
                        echo "<p style='color: orange;'>  ⚠ Email failed: {$e->getMessage()}</p>";
                    }
                }
            } else {
                echo "<p style='color: red;'>✗ Failed to create alert for $student_id - $course_title: " . $conn->error . "</p>";
            }
        } else {
            echo "<p style='color: gray;'>- Alert already exists for $student_id - $course_title</p>";
        }
    }
} else {
    echo "<p>No students with INC grades found.</p>";
}

echo "<br><h3>Summary:</h3>";
$total_inc = $conn->query("SELECT COUNT(*) as count FROM academic_alerts WHERE alert_type='INC' AND is_resolved=0")->fetch_assoc()['count'];
echo "<p><strong>Total active INC alerts:</strong> $total_inc</p>";

$conn->close();
echo "<br><a href='dashboard.php'>Go to Dashboard</a>";
?>
