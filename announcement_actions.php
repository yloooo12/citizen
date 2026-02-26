<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$conn = new mysqli("localhost", "root", "", "citizenproj");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed']);
    exit;
}

$user_id = $_SESSION["user_id"];
$action = $_POST['action'] ?? '';
$announcement_id = intval($_POST['announcement_id'] ?? 0);

if ($action === 'toggle_like') {
    // Check if already liked
    $check = $conn->prepare("SELECT id FROM announcement_likes WHERE announcement_id=? AND user_id=?");
    $check->bind_param("ii", $announcement_id, $user_id);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        // Unlike
        $stmt = $conn->prepare("DELETE FROM announcement_likes WHERE announcement_id=? AND user_id=?");
        $stmt->bind_param("ii", $announcement_id, $user_id);
        $stmt->execute();
        $liked = false;
    } else {
        // Like
        $stmt = $conn->prepare("INSERT INTO announcement_likes (announcement_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $announcement_id, $user_id);
        $stmt->execute();
        $liked = true;
    }
    
    // Get total likes
    $count = $conn->query("SELECT COUNT(*) as total FROM announcement_likes WHERE announcement_id=$announcement_id")->fetch_assoc()['total'];
    
    echo json_encode(['success' => true, 'liked' => $liked, 'count' => $count]);
    
} elseif ($action === 'post_comment') {
    $comment_text = trim($_POST['comment_text'] ?? '');
    
    if (empty($comment_text)) {
        echo json_encode(['success' => false, 'message' => 'Comment is empty']);
        exit;
    }
    
    $stmt = $conn->prepare("INSERT INTO announcement_comments (announcement_id, user_id, comment_text) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $announcement_id, $user_id, $comment_text);
    $stmt->execute();
    
    $first_name = $_SESSION["first_name"] ?? '';
    $last_name = $_SESSION["last_name"] ?? '';
    
    echo json_encode([
        'success' => true,
        'comment' => [
            'author' => $first_name . ' ' . $last_name,
            'text' => htmlspecialchars($comment_text),
            'avatar' => strtoupper(substr($first_name, 0, 1))
        ]
    ]);
    
} elseif ($action === 'get_comments') {
    $stmt = $conn->prepare("SELECT c.comment_text, c.created_at, u.first_name, u.last_name 
                            FROM announcement_comments c 
                            JOIN users u ON c.user_id = u.id 
                            WHERE c.announcement_id = ? 
                            ORDER BY c.created_at ASC");
    $stmt->bind_param("i", $announcement_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $comments = [];
    while ($row = $result->fetch_assoc()) {
        $comments[] = [
            'author' => $row['first_name'] . ' ' . $row['last_name'],
            'text' => $row['comment_text'],
            'avatar' => strtoupper(substr($row['first_name'], 0, 1))
        ];
    }
    
    echo json_encode(['success' => true, 'comments' => $comments]);
}

$conn->close();
?>
