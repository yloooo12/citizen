<?php
session_start();
if (!isset($_SESSION['id_number']) || (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'teacher')) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$teacher_id = $_SESSION['id_number'];

// Don't auto-mark as read - let user control this

// Get all notifications
$notifications = [];
$result = $conn->query("SELECT id, message, is_read, created_at FROM teacher_notifications WHERE teacher_id = '$teacher_id' ORDER BY created_at DESC");
if ($result) {
    while($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Teacher Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fa;
            color: #333;
            overflow-x: hidden;
        }

        .content-wrapper {
            background: white;
            border-radius: 16px;
            margin: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .page-header {
            background: #667eea;
            padding: 2.5rem 2rem;
            color: white;
            position: relative;
        }

        .page-header-content {
            position: relative;
            z-index: 1;
        }

        .page-title {
            font-size: 2.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .page-title i {
            font-size: 2rem;
        }

        .page-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .notifications-container {
            padding: 2rem;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            border: 1px solid #e5e7eb;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #64748b;
            font-weight: 500;
        }

        .notification-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid #f1f5f9;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .notification-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: #667eea;
        }

        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
            border-color: #e2e8f0;
        }

        .notification-header {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .notification-icon {
            width: 48px;
            height: 48px;
            background: #667eea;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .notification-content {
            flex: 1;
        }

        .notification-message {
            font-size: 1rem;
            color: #334155;
            line-height: 1.6;
            margin-bottom: 0.75rem;
            font-weight: 500;
        }

        .notification-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .notification-time {
            font-size: 0.85rem;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .notification-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .notification-type {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .mark-btn {
            padding: 0.4rem 0.8rem;
            border: 1px solid #667eea;
            background: white;
            color: #667eea;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .mark-btn:hover {
            background: #667eea;
            color: white;
        }

        .notification-card.unread {
            border-left-color: #ef4444;
            background: #fef2f2;
        }

        .notification-card.read {
            opacity: 0.8;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: #f8fafc;
            border-radius: 16px;
            border: 2px dashed #cbd5e1;
        }

        .empty-icon {
            font-size: 4rem;
            color: #94a3b8;
            margin-bottom: 1.5rem;
        }

        .empty-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 0.5rem;
        }

        .empty-text {
            color: #64748b;
            font-size: 1rem;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1.75rem;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        @media (max-width: 768px) {
            .content-wrapper {
                margin: 1rem;
            }

            .page-header {
                padding: 2rem 1.5rem;
            }

            .page-title {
                font-size: 1.75rem;
            }

            .notifications-container {
                padding: 1.5rem;
            }

            .notification-card {
                padding: 1.25rem;
            }

            .notification-header {
                flex-direction: column;
                gap: 1rem;
            }

            .notification-meta {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <?php include 'teacher_navbar.php'; ?>
    
    <div class="main-container" id="mainContainer">
        <div class="content-wrapper">
            <div class="page-header">
                <div class="page-header-content">
                    <h1 class="page-title">
                        <i class="fas fa-bell"></i>
                        Notifications
                    </h1>
                    <p class="page-subtitle">Manage and view all your notification updates</p>
                </div>
            </div>

            <div class="notifications-container">
                <a href="teacher_dashboard.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>

                <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($notifications); ?></div>
                        <div class="stat-label">Total Notifications</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($notifications, function($n) { return $n['is_read'] == 0; })); ?></div>
                        <div class="stat-label">Unread</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($notifications, function($n) { return $n['is_read'] == 1; })); ?></div>
                        <div class="stat-label">Read</div>
                    </div>
                </div>

                <?php if (empty($notifications)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-bell-slash"></i>
                        </div>
                        <h2 class="empty-title">No Notifications Yet</h2>
                        <p class="empty-text">You'll see INC request notifications and other updates here when they arrive.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-card <?php echo $notification['is_read'] == 0 ? 'unread' : 'read'; ?>" data-id="<?php echo $notification['id']; ?>">
                            <div class="notification-header">
                                <div class="notification-icon">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-message">
                                        <?php echo htmlspecialchars($notification['message']); ?>
                                    </div>
                                    <div class="notification-meta">
                                        <div class="notification-time">
                                            <i class="fas fa-clock"></i>
                                            <?php echo date('M j, Y \a\t g:i A', strtotime($notification['created_at'])); ?>
                                        </div>
                                        <div class="notification-actions">
                                            <div class="notification-type">
                                                <i class="fas fa-file-alt"></i>
                                                INC Request
                                            </div>
                                            <button class="mark-btn" onclick="toggleRead(<?php echo $notification['id']; ?>, <?php echo $notification['is_read']; ?>)">
                                                <?php echo $notification['is_read'] == 0 ? 'Mark as Read' : 'Mark as Unread'; ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    function toggleRead(notifId, currentStatus) {
        const newStatus = currentStatus == 0 ? 1 : 0;
        
        fetch('toggle_teacher_notification.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + notifId + '&status=' + newStatus
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }
    </script>
</body>
</html>