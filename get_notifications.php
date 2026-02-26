<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(['notifications' => [], 'unread_count' => 0]);
    exit;
}

$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) {
    echo json_encode(['notifications' => [], 'unread_count' => 0]);
    exit;
}

$user_id = $_SESSION["user_id"];

$stmt = $conn->prepare("SELECT id, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $time_diff = time() - strtotime($row['created_at']);
    if ($time_diff < 60) $time_ago = 'Just now';
    elseif ($time_diff < 3600) $time_ago = floor($time_diff / 60) . ' min ago';
    elseif ($time_diff < 86400) $time_ago = floor($time_diff / 3600) . ' hr ago';
    else $time_ago = floor($time_diff / 86400) . ' day ago';
    
    $row['time_ago'] = $time_ago;
    $notifications[] = $row;
}
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$unread_count = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

$conn->close();

echo json_encode(['notifications' => $notifications, 'unread_count' => $unread_count]);
?>
