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

$subject_code = $_POST['subject_code'] ?? '';
$period = $_POST['period'] ?? 'midterm';
$columns = $_POST['columns'] ?? '';

if ($subject_code && $columns) {
    $teacher_id = $_SESSION['id_number'] ?? '';
    
    // Check if exists
    $check = $conn->prepare("SELECT id FROM grade_columns WHERE subject_code = ? AND teacher_id = ? AND period = ?");
    $check->bind_param("sss", $subject_code, $teacher_id, $period);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        // Update
        $stmt = $conn->prepare("UPDATE grade_columns SET columns = ?, updated_at = NOW() WHERE subject_code = ? AND teacher_id = ? AND period = ?");
        $stmt->bind_param("ssss", $columns, $subject_code, $teacher_id, $period);
    } else {
        // Insert
        $stmt = $conn->prepare("INSERT INTO grade_columns (subject_code, teacher_id, period, columns, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("ssss", $subject_code, $teacher_id, $period, $columns);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    
    $stmt->close();
    $check->close();
}

$conn->close();
?>
