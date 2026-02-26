<?php
session_start();
if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== 'program_head') {
    header("Location: login.php");
    exit;
}

$first_name = $_SESSION["first_name"] ?? '';
$last_name = $_SESSION["last_name"] ?? '';

$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

// Create activity log table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    user_type VARCHAR(50),
    action VARCHAR(255),
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Get activity logs
$query = "SELECT * FROM activity_log WHERE user_type='program_head' ORDER BY created_at DESC LIMIT 50";
$result = $conn->query($query);
$activities = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - Program Head</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; }
        .main-container { margin-left: 260px; margin-top: 65px; padding: 2rem; transition: all 0.3s ease; }
        .main-container.collapsed { margin-left: 70px; }
        .page-header { margin-bottom: 2rem; }
        .page-header h2 { font-size: 1.75rem; color: #2d3748; font-weight: 700; margin-bottom: 0.5rem; }
        .page-header p { color: #718096; font-size: 0.95rem; }
        .card { background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .activity-item { padding: 1.25rem; border-left: 3px solid #667eea; background: #f9fafb; border-radius: 8px; margin-bottom: 1rem; }
        .activity-item:hover { background: #f0f4ff; }
        .activity-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
        .activity-action { font-weight: 600; color: #2d3748; font-size: 0.95rem; }
        .activity-time { color: #a0aec0; font-size: 0.8rem; }
        .activity-details { color: #718096; font-size: 0.85rem; line-height: 1.5; }
        .empty-state { text-align: center; padding: 3rem; color: #a0aec0; }
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
            <h2><i class="fas fa-history"></i> Activity Log</h2>
            <p>Track all your actions and activities</p>
        </div>

        <div class="card">
            <?php if (!empty($activities)): ?>
                <?php foreach($activities as $activity): ?>
                <div class="activity-item">
                    <div class="activity-header">
                        <div class="activity-action">
                            <i class="fas fa-check-circle" style="color: #667eea;"></i>
                            <?php echo htmlspecialchars($activity['action']); ?>
                        </div>
                        <div class="activity-time">
                            <i class="far fa-clock"></i>
                            <?php 
                            $time_diff = time() - strtotime($activity['created_at']);
                            if ($time_diff < 60) echo 'Just now';
                            elseif ($time_diff < 3600) echo floor($time_diff / 60) . ' min ago';
                            elseif ($time_diff < 86400) echo floor($time_diff / 3600) . ' hr ago';
                            else echo date('M d, Y h:i A', strtotime($activity['created_at']));
                            ?>
                        </div>
                    </div>
                    <div class="activity-details"><?php echo htmlspecialchars($activity['details']); ?></div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-history"></i>
                    <p>No activity recorded yet</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
