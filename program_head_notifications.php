<?php
session_start();
if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== 'program_head') {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

$user_id = $_SESSION["user_id"];

// Mark all as read
if (isset($_POST['mark_all_read'])) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: program_head_notifications.php");
    exit;
}

// Mark single as read
if (isset($_POST['mark_read'])) {
    $notif_id = $_POST['notif_id'];
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notif_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: program_head_notifications.php");
    exit;
}

// Fetch notifications
$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Program Head</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; }
        .main-container { margin-left: 260px; margin-top: 65px; padding: 2rem; transition: all 0.3s ease; }
        .main-container.collapsed { margin-left: 70px; }
        .page-header { margin-bottom: 2rem; }
        .page-header h2 { font-size: 1.75rem; color: #2d3748; font-weight: 700; margin-bottom: 0.5rem; }
        .page-header p { color: #718096; font-size: 0.95rem; }
        .notifications-container { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .notifications-header { padding: 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .notifications-header h3 { font-size: 1.1rem; color: #2d3748; font-weight: 600; }
        .mark-all-btn { background: #667eea; color: white; border: none; padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.85rem; cursor: pointer; transition: all 0.3s ease; }
        .mark-all-btn:hover { background: #5568d3; }
        .notification-item { padding: 1.25rem 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; gap: 1rem; transition: all 0.3s ease; }
        .notification-item:last-child { border-bottom: none; }
        .notification-item.unread { background: #f0f4ff; }
        .notification-item:hover { background: #f9fafb; }
        .notification-icon { width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; background: #dbeafe; color: #3b82f6; }
        .notification-content { flex: 1; }
        .notification-message { color: #2d3748; font-size: 0.95rem; margin-bottom: 0.5rem; line-height: 1.5; }
        .notification-time { color: #a0aec0; font-size: 0.8rem; }
        .notification-actions { display: flex; align-items: center; gap: 0.5rem; }
        .mark-read-btn { background: transparent; border: 1px solid #e2e8f0; color: #718096; padding: 0.4rem 0.8rem; border-radius: 6px; font-size: 0.8rem; cursor: pointer; transition: all 0.3s ease; }
        .mark-read-btn:hover { background: #f7fafc; border-color: #cbd5e0; }
        .empty-state { padding: 3rem; text-align: center; color: #a0aec0; }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; }
        @media (max-width: 768px) {
            .main-container { margin-left: 0; padding: 1rem; }
        }
    </style>
</head>
<body>
    <?php include 'program_head_navbar.php'; ?>
    
    <div class="main-container" id="mainContainer">
        <div class="page-header">
            <h2><i class="fas fa-bell"></i> Notifications</h2>
            <p>Stay updated with your latest notifications</p>
        </div>

        <div class="notifications-container">
            <div class="notifications-header">
                <h3>All Notifications</h3>
                <?php if (count($notifications) > 0): ?>
                    <form method="post" style="display:inline;">
                        <button type="submit" name="mark_all_read" class="mark-all-btn">
                            <i class="fas fa-check-double"></i> Mark All as Read
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if (count($notifications) > 0): ?>
                <?php foreach ($notifications as $notif): ?>
                    <div class="notification-item <?php echo $notif['is_read'] == 0 ? 'unread' : ''; ?>">
                        <div class="notification-icon">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-message"><?php echo htmlspecialchars($notif['message']); ?></div>
                            <div class="notification-time">
                                <i class="far fa-clock"></i> <?php echo date('M d, Y h:i A', strtotime($notif['created_at'])); ?>
                            </div>
                        </div>
                        <?php if ($notif['is_read'] == 0): ?>
                            <div class="notification-actions">
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="notif_id" value="<?php echo $notif['id']; ?>">
                                    <button type="submit" name="mark_read" class="mark-read-btn">
                                        <i class="fas fa-check"></i> Mark Read
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="far fa-bell-slash"></i>
                    <p>No notifications yet</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
