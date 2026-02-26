<?php
// One-time script to add subject_code to existing grades
session_start();
if (!isset($_SESSION["is_teacher"]) || $_SESSION["is_teacher"] !== true) {
    exit('Unauthorized');
}

$conn = new mysqli("localhost", "root", "", "student_services");
$teacher_id = $_SESSION['id_number'];

// Get teacher's subject
$subject_result = $conn->query("SELECT DISTINCT subject_code FROM student_subjects WHERE teacher_id = '$teacher_id' LIMIT 1");
$subject = '';
if ($subject_result && $row = $subject_result->fetch_assoc()) {
    $subject = $row['subject_code'];
}

if ($subject) {
    // Update all grades without subject_code
    $conn->query("UPDATE grades SET subject_code = '$subject' WHERE teacher_id = '$teacher_id' AND (subject_code IS NULL OR subject_code = '')");
    echo "Updated grades with subject: $subject";
} else {
    echo "No subject found";
}

$conn->close();
?>
