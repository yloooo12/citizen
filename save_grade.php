<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION["is_teacher"]) || $_SESSION["is_teacher"] !== true) {
    exit('Unauthorized');
}

$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "student_services";
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

if ($_POST) {
    $student_id = $_POST['student_id'];
    $column_name = $_POST['column_name'];
    $grade = $_POST['grade'];
    $teacher_id = $_SESSION['id_number'];
    $subject_code = $_POST['subject_code'] ?? $_SESSION['assigned_lecture'] ?? '';
    $units = $_POST['units'] ?? 3;
    $equivalent = $_POST['equivalent'] ?? NULL;
    
    // Create grades table if not exists
    $conn->query("CREATE TABLE IF NOT EXISTS grades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(50),
        teacher_id VARCHAR(50),
        subject_code VARCHAR(50),
        column_name VARCHAR(100),
        grade VARCHAR(50),
        units INT DEFAULT 3,
        equivalent VARCHAR(10),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_grade (student_id, teacher_id, subject_code, column_name)
    )");
    
    // Always save the grade value (including 0 or empty)
    if ($grade === '' || $grade === null) {
        $grade = '0';
    }
    
    $stmt = $conn->prepare("INSERT INTO grades (student_id, teacher_id, subject_code, column_name, grade, units, equivalent) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE grade = ?, units = ?, equivalent = ?, updated_at = CURRENT_TIMESTAMP");
    if (!$stmt) {
        die('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param("sssssissis", $student_id, $teacher_id, $subject_code, $column_name, $grade, $units, $equivalent, $grade, $units, $equivalent);
    if (!$stmt->execute()) {
        die('Execute failed: ' . $stmt->error);
    }
    
    // Check if finals exam is 0 and send INC email
    if ($column_name == 'finals_Exam' && $grade == '0') {
        require_once 'configure_email.php';
        $student_info = $conn->query("SELECT u.first_name, u.email, ss.subject_title, t.first_name as t_first, t.last_name as t_last
                                      FROM users u 
                                      INNER JOIN student_subjects ss ON u.id_number = ss.student_id 
                                      LEFT JOIN users t ON ss.teacher_id = t.id_number
                                      WHERE u.id_number = '$student_id' AND ss.subject_code = '$subject_code' LIMIT 1");
        
        if ($student_info && $row = $student_info->fetch_assoc()) {
            $course_title = $subject_code . ' (' . $row['subject_title'] . ')';
            $teacher_name = ($row['t_last'] ?? 'Teacher') . ', ' . ($row['t_first'] ?? '');
            
            // Create INC alert if not exists
            $check = $conn->query("SELECT id FROM academic_alerts WHERE student_id='$student_id' AND course='$course_title' AND alert_type IN ('INC', 'EXAM') AND is_resolved=0");
            if (!$check || $check->num_rows == 0) {
                $conn->query("INSERT INTO academic_alerts (student_id, course, grade, program_section, reason, intervention, instructor, semester, school_year, alert_type, is_resolved) 
                              VALUES ('$student_id', '$course_title', 'INC', 'B. S. Information Technology', 'INC', 'Contact instructor: $teacher_name', '$teacher_name', '2nd', '2025 - 2026', 'INC', 0)");
                
                // Send notification
                $notif_msg = "Academic Alert: You have an INCOMPLETE (INC) grade for $course_title. Contact instructor: $teacher_name to complete requirements.";
                $conn->query("INSERT INTO notifications (user_id, message, type, is_read) VALUES ('$student_id', '$notif_msg', 'academic_alert', 0)");
                
                // Send email
                $subject = 'Academic Alert: Incomplete Grade';
                $message = "Dear {$row['first_name']},\n\nYou have an INCOMPLETE (INC) grade for $course_title due to missing final exam (score: 0).\n\nPlease contact your instructor: $teacher_name to complete the requirements.\n\nProgram: B.S. Information Technology\nSemester: 2nd 2025-2026\n\nThank you.\nLSPU-CCS";
                sendEmail($row['email'], $subject, $message, $row['first_name']);
            }
        }
    }
    
    // Check if midterm grade is 74 or below and send notification
    if ($column_name == 'midterm_total' && floatval($grade) <= 74 && floatval($grade) > 0) {
        // Get student info
        $student_info = $conn->query("SELECT u.first_name, u.last_name, u.email, ss.subject_title 
                                      FROM users u 
                                      INNER JOIN student_subjects ss ON u.id_number = ss.student_id 
                                      WHERE u.id_number = '$student_id' AND ss.subject_code = '$subject_code' LIMIT 1");
        
        if ($student_info && $row = $student_info->fetch_assoc()) {
            $name = $row['first_name'] . ' ' . $row['last_name'];
            $email = $row['email'];
            $subject_title = $row['subject_title'];
            
            // Insert notification
            $message = "Your midterm grade in $subject_code ($subject_title) is $grade. You need to improve to pass this subject.";
            $conn->query("INSERT INTO notifications (user_id, message, type, created_at) 
                          VALUES ('$student_id', '$message', 'warning', NOW())");
            
            // Send email
            $to = $email;
            $subject = "Grade Alert: $subject_code - Midterm Grade Below Passing";
            $body = "Dear $name,\n\nThis is to inform you that your midterm grade in $subject_code ($subject_title) is $grade.\n\nYou are currently below the passing grade of 75. Please consult with your instructor to improve your performance.\n\nBest regards,\nLSPU CCS";
            $headers = "From: noreply@lspu.edu.ph";
            
            @mail($to, $subject, $body, $headers);
        }
    }
    
    echo 'saved';
}
    
$conn->close();
?>