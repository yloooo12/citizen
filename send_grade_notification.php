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

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

if ($_POST) {
    $student_id = $_POST['student_id'];
    $subject_code = $_POST['subject_code'];
    $midterm_grade = floatval($_POST['midterm_grade']);
    $teacher_id = $_SESSION['id_number'];
    
    // Calculate how much student needs in finals
    $needed_total = 75; // Passing grade
    $needed_finals = ($needed_total - ($midterm_grade * 0.5)) / 0.5;
    
    if ($needed_finals > 100) {
        $message = "URGENT: Your MIDTERM grade in $subject_code is $midterm_grade. Unfortunately, even with a perfect FINALS score (100), you cannot reach the passing grade of 75. Please consult your teacher immediately.";
    } else {
        $points_needed = $needed_finals - $midterm_grade;
        $message = "NOTICE: Your MIDTERM grade in $subject_code is $midterm_grade. You need to score at least " . number_format($needed_finals, 2) . " in FINALS (+" . number_format($points_needed, 2) . " points improvement) to reach the passing grade of 75.";
    }
    
    // Insert notification
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, created_at) VALUES (?, ?, 'grade_warning', NOW())");
    $stmt->bind_param("ss", $student_id, $message);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Notification sent']);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    
    $stmt->close();
}

$conn->close();
?>
