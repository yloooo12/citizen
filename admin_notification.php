<?php
session_start();

if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "citizenproj";

$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['clear_all'])) {
    $conn->query("DELETE FROM admin_notifications");
    header("Location: admin_notification.php");
    exit();
}

if (isset($_POST['toggle_read']) && isset($_POST['notif_id'])) {
    $notif_id = intval($_POST['notif_id']);
    $is_read = intval($_POST['is_read']);
    $conn->query("UPDATE admin_notifications SET is_read = $is_read WHERE id = $notif_id");
    header("Location: admin_notification.php");
    exit();
}

$sql = "SELECT id, message, date_created, IFNULL(is_read,0) as is_read FROM admin_notifications ORDER BY date_created DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Notifications - LSPU CCS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; color: #333; overflow-x: hidden; width: 100%; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes slideIn { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes slideInDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        .header { background: #667eea; padding: 1rem 2rem; z-index: 100; position: fixed; top: 0; left: 0; right: 0; box-shadow: 0 2px 15px rgba(102, 126, 234, 0.25); }
        .nav-container { display: flex; justify-content: space-between; align-items: center; }
        .logo-section { display: flex; align-items: center; gap: 1rem; }
        .logo-img { width: 45px; height: 45px; border-radius: 10px; object-fit: cover; box-shadow: 0 4px 12px rgba(0,0,0,0.15); border: 2px solid rgba(255,255,255,0.2); }
        .logo-text h1 { font-size: 1.1rem; font-weight: 700; color: white; line-height: 1.2; }
        .logo-text p { font-size: 0.75rem; color: rgba(255,255,255,0.9); }
        .user-section { display: flex; align-items: center; gap: 1rem; }
        .welcome-text { font-weight: 600; color: white; font-size: 0.9rem; }
        .logout-btn { background: rgba(255,255,255,0.15); color: white; border: 1.5px solid rgba(255,255,255,0.3); padding: 0.5rem 1.2rem; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; font-size: 0.85rem; text-decoration: none; display: inline-block; }
        .logout-btn:hover { background: rgba(255,255,255,0.25); transform: translateY(-1px); }
        .toggle-btn { background: rgba(255,255,255,0.15); border: none; color: white; font-size: 1.25rem; cursor: pointer; padding: 0.6rem; margin-right: 1rem; border-radius: 8px; }
        .sidebar { position: fixed; left: 0; top: 65px; width: 260px; height: calc(100vh - 65px); background: white; box-shadow: 4px 0 25px rgba(0,0,0,0.06); z-index: 99; overflow-y: auto; transition: all 0.3s ease; border-right: 1px solid #e8ecf4; }
        .sidebar.collapsed { width: 70px; }
        .sidebar.collapsed .sidebar-menu a span { display: none; }
        .sidebar.collapsed .sidebar-menu a { justify-content: center; padding: 1rem; }
        .sidebar.collapsed:hover { width: 260px; }
        .sidebar.collapsed:hover .sidebar-menu a span { display: inline; }
        .sidebar.collapsed:hover .sidebar-menu a { justify-content: flex-start; padding: 1rem 1.5rem; }
        .sidebar-menu { padding: 1.5rem 0; }
        .sidebar-menu a { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; margin: 0.25rem 0.75rem; color: #4a5568; text-decoration: none; transition: all 0.3s ease; font-size: 0.95rem; font-weight: 500; border-radius: 12px; }
        .sidebar-menu a:hover { background: #f0f4ff; color: #667eea; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15); }
        .sidebar-menu a.active { background: #667eea; color: white; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); font-weight: 600; }
        .sidebar-menu a i { width: 22px; text-align: center; font-size: 1.1rem; }
        .main-container { margin-left: 260px; margin-top: 65px; padding: 2rem; height: calc(100vh - 65px); overflow-y: auto; transition: margin-left 0.3s ease; }
        .main-container.collapsed { margin-left: 70px; }
        .notif-wrapper { animation: fadeIn 0.5s ease; }
        .notif-header { background: white; border-radius: 16px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; animation: slideInDown 0.6s ease; }
        .notif-header h1 { font-size: 1.75rem; font-weight: 700; color: #2d3748; }
        .notif-header h1 i { color: #667eea; margin-right: 0.5rem; }
        .header-actions { display: flex; gap: 1rem; }
        .btn { padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; font-size: 0.95rem; cursor: pointer; transition: all 0.3s ease; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-back { background: #f3f4f6; color: #374151; }
        .btn-back:hover { background: #e5e7eb; transform: translateY(-2px); }
        .btn-clear { background: #ef4444; color: white; }
        .btn-clear:hover { background: #dc2626; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3); }
        .notif-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(102, 126, 234, 0.15); }
        .stat-card.total { border-left: 4px solid #667eea; }
        .stat-card.unread { border-left: 4px solid #f59e0b; }
        .stat-card.read { border-left: 4px solid #10b981; }
        .stat-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1rem; }
        .stat-card.total .stat-icon { background: #f0f4ff; color: #667eea; }
        .stat-card.unread .stat-icon { background: #fef3c7; color: #f59e0b; }
        .stat-card.read .stat-icon { background: #d1fae5; color: #10b981; }
        .stat-value { font-size: 2rem; font-weight: 700; color: #2d3748; margin-bottom: 0.25rem; }
        .stat-label { font-size: 0.9rem; color: #718096; }
        .notif-list { display: flex; flex-direction: column; gap: 1rem; }
        .notif-card { background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #e2e8f0; transition: all 0.3s ease; animation: slideIn 0.4s ease; }
        .notif-card:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.1); transform: translateX(5px); }
        .notif-card.unread { border-left-color: #667eea; background: linear-gradient(90deg, #f0f4ff 0%, white 100%); }
        .notif-card.read { border-left-color: #10b981; }
        .notif-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .notif-id { font-weight: 700; color: #667eea; font-size: 1.1rem; }
        .notif-date { font-size: 0.85rem; color: #718096; display: flex; align-items: center; gap: 0.5rem; }
        .notif-body { margin-bottom: 1rem; }
        .notif-message { font-size: 1rem; color: #2d3748; line-height: 1.6; background: #f9fafb; padding: 1rem; border-radius: 8px; }
        .notif-actions { display: flex; gap: 0.75rem; }
        .action-btn { padding: 0.5rem 1rem; border-radius: 6px; border: none; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 0.5rem; background: #f0f4ff; color: #667eea; }
        .action-btn:hover { background: #667eea; color: white; }
        .empty-state { background: white; border-radius: 12px; padding: 3rem; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .empty-state i { font-size: 4rem; color: #cbd5e0; margin-bottom: 1rem; }
        .empty-state h3 { font-size: 1.25rem; color: #2d3748; margin-bottom: 0.5rem; }
        .empty-state p { color: #718096; }
        @media (max-width: 768px) {
            * { max-width: 100%; }
            body { overflow-x: hidden; width: 100vw; }
            .header { padding: 0.5rem 0.75rem; }
            .logo-img { width: 30px; height: 30px; }
            .logo-text h1 { font-size: 0.75rem; }
            .logo-text p { display: none; }
            .toggle-btn { margin-right: 0.25rem; padding: 0.4rem; font-size: 1rem; }
            .welcome-text { display: none; }
            .logout-btn { padding: 0.4rem 0.6rem; font-size: 0.75rem; }
            .logout-btn span { display: none; }
            .sidebar { transform: translateX(-100%); width: 250px; top: 55px; height: calc(100vh - 55px); }
            .sidebar.show { transform: translateX(0); }
            .main-container { margin-left: 0; margin-top: 55px; padding: 1rem; }
            .notif-header { flex-direction: column; gap: 1rem; align-items: flex-start; padding: 1rem; }
            .header-actions { width: 100%; flex-direction: column; }
            .btn { width: 100%; justify-content: center; }
            .notif-stats { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php $page_title = 'Admin Notifications'; include 'admin_header.php'; ?>

    <aside class="sidebar" id="sidebar">
        <nav class="sidebar-menu">
            <a href="admin_dashboard.php"><i class="fas fa-th-large"></i><span>Dashboard</span></a>
            <a href="admin_inc.php"><i class="fas fa-file-alt"></i><span>INC Requests</span></a>
            <a href="admin_crediting.php">
                <i class="fas fa-graduation-cap"></i>
                <span>Crediting Requests</span>
            </a>
            <a href="admin_students.php"><i class="fas fa-users"></i><span>Students</span></a>
            <a href="admin_notification.php" class="active"><i class="fas fa-bell"></i><span>Notifications</span></a>
            <a href="post_announcement.php"><i class="fas fa-bullhorn"></i><span>Announcements</span></a>
            <a href="admin_upload_grades.php">
                <i class="fas fa-upload"></i>
                <span>Upload Grades</span>
            </a>
        </nav>
    </aside>

    <main class="main-container" id="mainContainer">
        <div class="notif-wrapper">
            <div class="notif-header">
                <h1><i class="fas fa-bell"></i>Admin Notifications</h1>
                <div class="header-actions">
                    <a href="admin_dashboard.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Back</a>
                    <form method="post" style="display:inline;">
                        <button type="submit" name="clear_all" class="btn btn-clear" onclick="return confirm('Clear all notifications?');"><i class="fas fa-trash"></i> Clear All</button>
                    </form>
                </div>
            </div>

            <?php
            $total = $result->num_rows;
            $unread = 0;
            $read = 0;
            if ($result) {
                $result->data_seek(0);
                while($row = $result->fetch_assoc()) {
                    if ($row['is_read']) {
                        $read++;
                    } else {
                        $unread++;
                    }
                }
                $result->data_seek(0);
            }
            ?>

            <div class="notif-stats">
                <div class="stat-card total">
                    <div class="stat-icon"><i class="fas fa-inbox"></i></div>
                    <div class="stat-value"><?php echo $total; ?></div>
                    <div class="stat-label">Total Notifications</div>
                </div>
                <div class="stat-card unread">
                    <div class="stat-icon"><i class="fas fa-envelope"></i></div>
                    <div class="stat-value"><?php echo $unread; ?></div>
                    <div class="stat-label">Unread</div>
                </div>
                <div class="stat-card read">
                    <div class="stat-icon"><i class="fas fa-envelope-open"></i></div>
                    <div class="stat-value"><?php echo $read; ?></div>
                    <div class="stat-label">Read</div>
                </div>
            </div>

            <div class="notif-list">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <div class="notif-card<?php echo $row['is_read'] ? ' read' : ' unread'; ?>">
                        <div class="notif-top">
                            <span class="notif-id"><i class="fas fa-hashtag"></i><?php echo $row["id"]; ?></span>
                            <span class="notif-date"><i class="far fa-clock"></i><?php echo date('M d, Y - h:i A', strtotime($row["date_created"])); ?></span>
                        </div>
                        <div class="notif-body">
                            <div class="notif-message"><?php echo htmlspecialchars($row["message"]); ?></div>
                        </div>
                        <div class="notif-actions">
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="notif_id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="is_read" value="<?php echo $row['is_read'] ? 0 : 1; ?>">
                                <button type="submit" name="toggle_read" class="action-btn">
                                    <i class="fas fa-<?php echo $row['is_read'] ? 'envelope' : 'envelope-open'; ?>"></i>
                                    <?php echo $row['is_read'] ? 'Mark as Unread' : 'Mark as Read'; ?>
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endwhile; ?>
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

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContainer = document.getElementById('mainContainer');
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                if (mainContainer) mainContainer.classList.toggle('collapsed');
            }
        }

        const sidebar = document.getElementById('sidebar');
        const mainContainer = document.getElementById('mainContainer');
        if (sidebar && mainContainer) {
            sidebar.addEventListener('mouseenter', function() {
                if (window.innerWidth > 768 && sidebar.classList.contains('collapsed')) {
                    mainContainer.style.marginLeft = '260px';
                }
            });
            sidebar.addEventListener('mouseleave', function() {
                if (window.innerWidth > 768 && sidebar.classList.contains('collapsed')) {
                    mainContainer.style.marginLeft = '70px';
                }
            });
        }

        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.querySelector('.toggle-btn');
            if (window.innerWidth <= 768 && sidebar.classList.contains('show')) {
                if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    </script>
<?php include 'admin_logout_modal.php'; ?>`r`n`r`n</body>
</html>
<?php
$conn->close();
?>
