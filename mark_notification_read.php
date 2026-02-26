<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["user_id"]) || !isset($_POST['notif_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) {
    echo json_encode(['success' => false]);
    exit;
}

$user_id = $_SESSION["user_id"];
$notif_id = intval($_POST['notif_id']);

$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $notif_id, $user_id);
$success = $stmt->execute();
$stmt->close();
$conn->close();

echo json_encode(['success' => $success]);
?>
