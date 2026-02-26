<?php
session_start();
if (!isset($_SESSION["is_teacher"]) || $_SESSION["is_teacher"] !== true) {
    exit('Unauthorized');
}

$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "student_services";
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

if ($_POST && isset($_POST['student_id']) && isset($_POST['grade'])) {
    $student_id = $_POST['student_id'];
    $grade = $_POST['grade'];
    $teacher_id = $_SESSION['id_number'];
    $teacher_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
    $subject = $_SESSION['assigned_lecture'];
    
    // Get student info
    $result = $conn->query("SELECT email, first_name, last_name FROM users WHERE id_number='$student_id'");
    $student = $result->fetch_assoc();
    $student_name = $student['first_name'] . ' ' . $student['last_name'];
    
    // Check if Final grade exists
    $check = $conn->query("SELECT grade FROM grades WHERE student_id='$student_id' AND teacher_id='$teacher_id' AND column_name='Final'");
    $has_final = $check && $check->num_rows > 0;
    
    $grade_type = $has_final ? 'final' : 'midterm';
    $grade_label = $has_final ? 'Final Grade' : 'Midterm Final Grade';
    
    // Create notification
    $message = "Grade Warning\n\nYour $grade_label in $subject is $grade.\n\nYou need to improve your performance to pass this subject. Please see your instructor for guidance.\n\nInstructor: $teacher_name";
    $insert_result = $conn->query("INSERT INTO notifications (user_id, message, created_at, is_read) VALUES ('$student_id', '$message', NOW(), 0)");
    
    if (!$insert_result) {
        error_log("Notification insert failed: " . $conn->error);
    }
    
    // Send email
    require_once 'send_email.php';
    $email_sent = sendGradeWarningEmail($student['email'], $student_name, $subject, $grade, $grade_type);
    
    echo json_encode(['success' => true, 'notification' => $insert_result ? 'sent' : 'failed', 'email' => $email_sent ? 'sent' : 'failed']);
}

$conn->close();
?>
