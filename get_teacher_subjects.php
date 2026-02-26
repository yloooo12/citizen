<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$conn = new mysqli("localhost", "root", "", "student_services");
$teacher_id = $_GET['teacher_id'] ?? '';

$subjects = [];
if ($teacher_id) {
    $result = $conn->query("SELECT DISTINCT subject_code FROM student_subjects WHERE teacher_id = '$teacher_id' ORDER BY subject_code");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row['subject_code'];
        }
    }
}

echo json_encode(['subjects' => $subjects]);
$conn->close();
?>
