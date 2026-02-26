<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id_number']) || (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'teacher')) {
    echo json_encode(['success' => false]);
    exit;
}

$teacher_id = $_SESSION['id_number'];
$notif_id = $_POST['id'] ?? 0;
$new_status = $_POST['status'] ?? 0;

$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $conn->prepare("UPDATE teacher_notifications SET is_read = ? WHERE id = ? AND teacher_id = ?");
$stmt->bind_param("iis", $new_status, $notif_id, $teacher_id);
$success = $stmt->execute();

$stmt->close();
$conn->close();

echo json_encode(['success' => $success]);
?>