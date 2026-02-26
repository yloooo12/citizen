<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$student_id = $_SESSION["id_number"] ?? '';

$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get the student's crediting request ID
$result = $conn->query("SELECT id FROM program_head_crediting WHERE student_id='$student_id' AND status='dean_approved' ORDER BY created_at DESC LIMIT 1");

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode(['success' => true, 'id' => $row['id']]);
} else {
    echo json_encode(['success' => false, 'message' => 'No approved crediting request found']);
}

$conn->close();
?>