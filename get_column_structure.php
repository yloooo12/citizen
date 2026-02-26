<?php
session_start();
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "student_services";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'error' => $conn->connect_error]));
}

$subject_code = $_GET['subject_code'] ?? '';
$period = $_GET['period'] ?? 'midterm';
$teacher_id = $_SESSION['id_number'] ?? '';

if ($subject_code && $teacher_id) {
    $stmt = $conn->prepare("SELECT columns FROM grade_columns WHERE subject_code = ? AND teacher_id = ? AND period = ?");
    $stmt->bind_param("sss", $subject_code, $teacher_id, $period);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'columns' => $row['columns']]);
    } else {
        echo json_encode(['success' => true, 'columns' => '']);
    }
    
    $stmt->close();
}

$conn->close();
?>
