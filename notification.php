<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$first_name = $_SESSION["first_name"] ?? '';
$last_name = $_SESSION["last_name"] ?? '';

$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "student_services";

$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

if (isset($_POST['clear_all'])) {
    $user_id = $_SESSION['id_number'] ?? '';
    $conn->query("DELETE FROM notifications WHERE user_id='$user_id'");
    header("Location: notification.php");
    exit();
}

if (isset($_POST['toggle_read']) && isset($_POST['notif_id'])) {
    $notif_id = intval($_POST['notif_id']);
    $is_read = intval($_POST['is_read']);
    $conn->query("UPDATE notifications SET is_read = $is_read WHERE id = $notif_id");
    header("Location: notification.php");
    exit();
}

$user_id = $_SESSION['id_number'] ?? '';

$sql = "SELECT id, message, created_at, IFNULL(is_read,0) as is_read FROM notifications WHERE user_id='$user_id' ORDER BY created_at DESC";
$result = $conn->query($sql);
if (!$result) {
    die("Query error: " . $conn->error);
}

$notifications = [];
while($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

$total = count($notifications);
$unread = 0;
$read = 0;
foreach($notifications as $n) {
    if ($n['is_read']) {
        $read++;
    } else {
        $unread++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - LSPU CCS</title>
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
            overflow: hidden;
            transition: background 0.3s ease, color 0.3s ease;
        }

        body.dark-mode {
            background: #1a202c;
            color: #e2e8f0;
        }

        body.dark-mode .notif-header,
        body.dark-mode .stat-card,
        body.dark-mode .notif-card,
        body.dark-mode .empty-state,
        body.dark-mode .footer {
            background: #2d3748;
            border-color: #4a5568;
        }

        body.dark-mode .notif-header h1,
        body.dark-mode .stat-value,
        body.dark-mode .notif-message,
        body.dark-mode .empty-state h3 {
            color: #e2e8f0;
        }

        body.dark-mode .stat-label,
        body.dark-mode .notif-student,
        body.dark-mode .empty-state p,
        body.dark-mode .footer {
            color: #cbd5e0;
        }

        body.dark-mode .notif-message {
            background: #374151;
            border-left-color: #4a5568;
        }

        body.dark-mode .notif-card.unread .notif-message {
            background: #374151;
            border-left-color: #667eea;
        }

        body.dark-mode .notif-meta {
            border-bottom-color: #4a5568;
        }

        body.dark-mode .action-btn.mark {
            background: #374151;
            color: #667eea;
        }

        body.dark-mode .action-btn.mark:hover {
            background: #667eea;
            color: white;
        }

        body.dark-mode .btn-back {
            background: #4a5568;
            color: #e2e8f0;
        }

        body.dark-mode .btn-back:hover {
            background: #374151;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .main-container {
            margin-left: 260px;
            margin-top: 65px;
            padding: 2rem 2.5rem;
            height: calc(100vh - 65px);
            overflow-y: auto;
            transition: all 0.3s ease;
        }

        .main-container.collapsed {
            margin-left: 70px;
        }

        .notif-wrapper {
            animation: fadeIn 0.5s ease;
        }

        .notif-header {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notif-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #2d3748;
        }

        .notif-header h1 i {
            color: #667eea;
            margin-right: 0.5rem;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-back {
            background: #f3f4f6;
            color: #374151;
        }

        .btn-back:hover {
            background: #e5e7eb;
            transform: translateY(-2px);
        }

        .btn-clear {
            background: #ef4444;
            color: white;
        }

        .btn-clear:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .notif-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.15);
        }

        .stat-card.total {
            border-left: 4px solid #667eea;
        }

        .stat-card.unread {
            border-left: 4px solid #f59e0b;
        }

        .stat-card.read {
            border-left: 4px solid #10b981;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stat-card.total .stat-icon {
            background: #f0f4ff;
            color: #667eea;
        }

        .stat-card.unread .stat-icon {
            background: #fef3c7;
            color: #f59e0b;
        }

        .stat-card.read .stat-icon {
            background: #d1fae5;
            color: #10b981;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #718096;
        }

        .notif-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .notif-card {
            background: white;
            border-radius: 16px;
            padding: 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
            animation: slideIn 0.4s ease;
            overflow: hidden;
        }

        .notif-card:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transform: translateY(-3px);
        }

        .notif-card.unread {
            border-color: #667eea;
            border-width: 3px;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.2);
        }

        .notif-card.unread .notif-header-bar {
            background: #667eea;
        }

        .notif-card.read {
            border-color: #e2e8f0;
        }

        .notif-card.read .notif-header-bar {
            background: #10b981;
        }

        .notif-header-bar {
            padding: 0.75rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #e2e8f0;
        }

        .notif-status-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .notif-status-badge i {
            font-size: 1rem;
        }

        .notif-content-area {
            padding: 1.5rem;
        }

        .notif-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #f3f4f6;
        }

        .notif-date {
            font-size: 0.9rem;
            color: #667eea;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .notif-date i {
            color: #667eea;
        }

        .notif-body {
            margin-bottom: 1rem;
        }

        .notif-student {
            font-size: 0.9rem;
            color: #667eea;
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .notif-message {
            font-size: 1.05rem;
            color: #2d3748;
            line-height: 1.7;
            padding: 1.25rem;
            background: #f9fafb;
            border-radius: 12px;
            border-left: 4px solid #e2e8f0;
        }

        .notif-card.unread .notif-message {
            background: #f0f4ff;
            border-left-color: #667eea;
            font-weight: 500;
        }

        .notif-actions {
            display: flex;
            gap: 0.75rem;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            border: none;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .action-btn.mark {
            background: #f0f4ff;
            color: #667eea;
        }

        .action-btn.mark:hover {
            background: #667eea;
            color: white;
        }

        .empty-state {
            background: white;
            border-radius: 12px;
            padding: 3rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .empty-state i {
            font-size: 4rem;
            color: #cbd5e0;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-size: 1.25rem;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: #718096;
        }

        .footer {
            text-align: center;
            padding: 1rem;
            color: #718096;
            font-size: 0.75rem;
            background: white;
            margin-left: 260px;
            transition: all 0.3s ease;
            border-top: 1px solid #e8ecf4;
        }

        .footer.collapsed {
            margin-left: 70px;
        }

        @media (max-width: 768px) {
            .main-container {
                margin-left: 0;
                padding: 1rem;
                margin-top: 55px;
            }

            .footer {
                margin-left: 0;
            }

            .notif-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .header-actions {
                width: 100%;
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .notif-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main class="main-container" id="mainContainer">
        <div class="notif-wrapper">
            <div class="notif-header">
                <h1><i class="fas fa-bell"></i>Notifications</h1>
                <div class="header-actions">
                    <a href="index.php" class="btn btn-back">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <form method="post" style="display:inline;">
                        <button type="submit" name="clear_all" class="btn btn-clear" onclick="return confirm('Clear all notifications?');">
                            <i class="fas fa-trash"></i> Clear All
                        </button>
                    </form>
                </div>
            </div>



            <div class="notif-stats">
                <div class="stat-card total">
                    <div class="stat-icon">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <div class="stat-value"><?php echo $total; ?></div>
                    <div class="stat-label">Total Notifications</div>
                </div>
                <div class="stat-card unread">
                    <div class="stat-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="stat-value"><?php echo $unread; ?></div>
                    <div class="stat-label">Unread</div>
                </div>
                <div class="stat-card read">
                    <div class="stat-icon">
                        <i class="fas fa-envelope-open"></i>
                    </div>
                    <div class="stat-value"><?php echo $read; ?></div>
                    <div class="stat-label">Read</div>
                </div>
            </div>

            <div class="notif-list">
                <?php if (count($notifications) > 0): ?>
                    <?php foreach($notifications as $row): ?>
                    <div class="notif-card<?php echo $row['is_read'] ? ' read' : ' unread'; ?>">
                        <div class="notif-header-bar">
                            <div class="notif-status-badge">
                                <i class="fas fa-<?php echo $row['is_read'] ? 'check-circle' : 'bell'; ?>"></i>
                                <span><?php echo $row['is_read'] ? 'READ' : 'UNREAD'; ?></span>
                            </div>
                            <span class="notif-date" style="color: white;">
                                <i class="far fa-clock"></i>
                                <?php echo date('M d, Y - h:i A', strtotime($row["created_at"])); ?>
                            </span>
                        </div>
                        <div class="notif-content-area">
                            <div class="notif-meta">
                                <div class="notif-date">
                                    <i class="far fa-clock"></i>
                                    <?php echo date('M d, Y - h:i A', strtotime($row["created_at"])); ?>
                                </div>
                            </div>
                            <div class="notif-message">
                                <?php echo nl2br(htmlspecialchars($row["message"])); ?>
                            </div>
                            <div class="notif-actions" style="margin-top: 1rem;">
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="notif_id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="is_read" value="<?php echo $row['is_read'] ? 0 : 1; ?>">
                                    <button type="submit" name="toggle_read" class="action-btn mark">
                                        <i class="fas fa-<?php echo $row['is_read'] ? 'envelope' : 'envelope-open'; ?>"></i>
                                        <?php echo $row['is_read'] ? 'Mark as Unread' : 'Mark as Read'; ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>No Notifications</h3>
                        <p>You don't have any notifications at the moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="footer" id="footer">
        <p>&copy; 2024 Laguna State Polytechnic University - Department of Computer Studies</p>
        <p>INTEGRITY • PROFESSIONALISM • INNOVATION</p>
    </footer>

    <?php include 'chatbot.php'; ?>

    <script>
        const sidebar = document.getElementById('sidebar');
        const mainContainer = document.getElementById('mainContainer');
        const footer = document.getElementById('footer');

        if (sidebar) {
            const observer = new MutationObserver(() => {
                if (sidebar.classList.contains('collapsed')) {
                    mainContainer.classList.add('collapsed');
                    footer.classList.add('collapsed');
                } else {
                    mainContainer.classList.remove('collapsed');
                    footer.classList.remove('collapsed');
                }
            });
            observer.observe(sidebar, { attributes: true, attributeFilter: ['class'] });
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>
