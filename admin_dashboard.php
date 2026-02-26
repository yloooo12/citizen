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

$conn = new mysqli("localhost", "root", "", "citizenproj");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Get statistics - exclude admin accounts
$admin_ids = ['246', '999'];
$placeholders = implode(',', array_fill(0, count($admin_ids), '?'));
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE id_number NOT IN ($placeholders)");
$stmt->bind_param(str_repeat('s', count($admin_ids)), ...$admin_ids);
$stmt->execute();
$result = $stmt->get_result();
$total_students = $result ? $result->fetch_assoc()['count'] : 0;
$stmt->close();

// INC Requests
$result = $conn->query("SELECT COUNT(*) as count FROM inc_requests");
$total_inc = $result ? $result->fetch_assoc()['count'] : 0;

$result = $conn->query("SELECT COUNT(*) as count FROM inc_requests WHERE approved=0");
$pending_inc = $result ? $result->fetch_assoc()['count'] : 0;

$result = $conn->query("SELECT COUNT(*) as count FROM inc_requests WHERE approved=1");
$approved_inc = $result ? $result->fetch_assoc()['count'] : 0;

// Crediting Requests
$result = $conn->query("SELECT COUNT(*) as count FROM crediting_requests");
$total_crediting = $result ? $result->fetch_assoc()['count'] : 0;

$result = $conn->query("SELECT COUNT(*) as count FROM crediting_requests WHERE status NOT IN ('approved', 'declined')");
$pending_crediting = $result ? $result->fetch_assoc()['count'] : 0;

$result = $conn->query("SELECT COUNT(*) as count FROM crediting_requests WHERE status='approved'");
$approved_crediting = $result ? $result->fetch_assoc()['count'] : 0;

// Combined totals
$total_requests = $total_inc + $total_crediting;
$pending_requests = $pending_inc + $pending_crediting;
$approved_requests = $approved_inc + $approved_crediting;

// Recent activities from activity log
$recent_activities = $conn->query("SELECT student_name, subject, action_type, created_at FROM activity_log ORDER BY created_at DESC LIMIT 10"); 

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - LSPU CCS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            width: 100%;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .header {
            background: #667eea;
            padding: 1rem 2rem;
            z-index: 100;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            box-shadow: 0 2px 15px rgba(102, 126, 234, 0.25);
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo-img {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            object-fit: cover;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border: 2px solid rgba(255,255,255,0.2);
        }

        .logo-text h1 {
            font-size: 1.1rem;
            font-weight: 700;
            color: white;
            line-height: 1.2;
        }

        .logo-text p {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.9);
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .welcome-text {
            font-weight: 600;
            color: white;
            font-size: 0.9rem;
        }

        .logout-btn {
            background: rgba(255,255,255,0.15);
            color: white;
            border: 1.5px solid rgba(255,255,255,0.3);
            padding: 0.5rem 1.2rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-block;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.25);
            transform: translateY(-1px);
        }

        .toggle-btn {
            background: rgba(255,255,255,0.15);
            border: none;
            color: white;
            font-size: 1.25rem;
            cursor: pointer;
            padding: 0.6rem;
            margin-right: 1rem;
            border-radius: 8px;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 65px;
            width: 260px;
            height: calc(100vh - 65px);
            background: white;
            box-shadow: 4px 0 25px rgba(0,0,0,0.06);
            z-index: 99;
            overflow-y: auto;
            transition: all 0.3s ease;
            border-right: 1px solid #e8ecf4;
        }

        .sidebar.collapsed {
            width: 70px;
        }

        .sidebar.collapsed .sidebar-menu a span {
            display: none;
        }

        .sidebar.collapsed .sidebar-menu a {
            justify-content: center;
            padding: 1rem;
        }

        .sidebar.collapsed:hover {
            width: 260px;
        }

        .sidebar.collapsed:hover .sidebar-menu a span {
            display: inline;
        }

        .sidebar.collapsed:hover .sidebar-menu a {
            justify-content: flex-start;
            padding: 1rem 1.5rem;
        }

        .sidebar-menu {
            padding: 1.5rem 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            margin: 0.25rem 0.75rem;
            color: #4a5568;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            font-weight: 500;
            border-radius: 12px;
        }

        .sidebar-menu a:hover {
            background: #f0f4ff;
            color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }

        .sidebar-menu a.active {
            background: #667eea;
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            font-weight: 600;
        }

        .sidebar-menu a i {
            width: 22px;
            text-align: center;
            font-size: 1.1rem;
        }

        .dashboard-container {
            margin-left: 260px;
            margin-top: 85px;
            padding: 2rem;
            transition: margin-left 0.3s ease;
        }

        .dashboard-container.collapsed {
            margin-left: 70px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
            width: 100%;
        }

        .stat-card:nth-child(1) {
            animation: slideInLeft 0.5s ease 0.1s backwards;
        }

        .stat-card:nth-child(2) {
            animation: slideInLeft 0.5s ease 0.2s backwards;
        }

        .stat-card:nth-child(3) {
            animation: slideInRight 0.5s ease 0.3s backwards;
        }

        .stat-card:nth-child(4) {
            animation: slideInRight 0.5s ease 0.4s backwards;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
        }

        .stat-card.purple::before { background: linear-gradient(180deg, #667eea 0%, #764ba2 100%); }
        .stat-card.green::before { background: linear-gradient(180deg, #10b981 0%, #059669 100%); }
        .stat-card.orange::before { background: linear-gradient(180deg, #f59e0b 0%, #d97706 100%); }
        .stat-card.red::before { background: linear-gradient(180deg, #ef4444 0%, #dc2626 100%); }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 1rem;
            animation: pulse 2s ease-in-out infinite;
        }

        .stat-card.purple .stat-icon { background: #f0f4ff; color: #667eea; }
        .stat-card.green .stat-icon { background: #d1fae5; color: #10b981; }
        .stat-card.orange .stat-icon { background: #fef3c7; color: #f59e0b; }
        .stat-card.red .stat-icon { background: #fee2e2; color: #ef4444; }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.95rem;
            color: #718096;
            font-weight: 500;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
            width: 100%;
        }

        .chart-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            width: 100%;
            box-sizing: border-box;
            overflow: hidden;
        }

        .charts-grid .chart-card:nth-child(1) {
            animation: scaleIn 0.6s ease 0.5s backwards;
        }

        .charts-grid .chart-card:nth-child(2) {
            animation: scaleIn 0.6s ease 0.6s backwards;
        }

        .dashboard-container > .chart-card {
            animation: fadeIn 0.6s ease 0.7s backwards;
        }

        .chart-card h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 1.5rem;
        }

        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #f9fafb;
            border-radius: 12px;
            transition: all 0.3s ease;
            animation: fadeIn 0.4s ease backwards;
        }

        .activity-item:nth-child(1) { animation-delay: 0.7s; }
        .activity-item:nth-child(2) { animation-delay: 0.8s; }
        .activity-item:nth-child(3) { animation-delay: 0.9s; }
        .activity-item:nth-child(4) { animation-delay: 1s; }
        .activity-item:nth-child(5) { animation-delay: 1.1s; }

        .activity-item:hover {
            background: #f0f4ff;
            transform: translateX(5px);
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 600;
            color: #2d3748;
            font-size: 0.95rem;
        }

        .activity-subtitle {
            font-size: 0.85rem;
            color: #718096;
        }

        .activity-time {
            font-size: 0.8rem;
            color: #a0aec0;
        }

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .charts-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            * {
                max-width: 100%;
            }

            body {
                overflow-x: hidden;
                width: 100vw;
            }

            .header {
                padding: 0.5rem 0.75rem;
            }

            .logo-img {
                width: 30px;
                height: 30px;
            }

            .logo-text h1 {
                font-size: 0.75rem;
            }

            .logo-text p {
                display: none;
            }

            .toggle-btn {
                margin-right: 0.25rem;
                padding: 0.4rem;
                font-size: 1rem;
            }

            .welcome-text {
                display: none;
            }

            .logout-btn {
                padding: 0.4rem 0.6rem;
                font-size: 0.75rem;
            }

            .logout-btn span {
                display: none;
            }

            .sidebar {
                transform: translateX(-100%);
                width: 250px;
                top: 55px;
                height: calc(100vh - 55px);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
                width: 100%;
            }

            .dashboard-container {
                padding: 0 0.75rem;
                margin-left: 0;
                margin-top: 55px;
                max-width: 100vw;
                width: 100vw;
            }

            .stat-card {
                padding: 1rem;
            }

            .stat-icon {
                width: 45px;
                height: 45px;
                font-size: 1.25rem;
            }

            .stat-value {
                font-size: 1.75rem;
            }

            .stat-label {
                font-size: 0.85rem;
            }

            .charts-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
                width: 100%;
            }

            .chart-card {
                padding: 1rem;
                width: 100%;
                max-width: 100%;
            }

            .chart-card h3 {
                font-size: 1rem;
                margin-bottom: 1rem;
            }

            .activity-item {
                padding: 0.5rem;
                gap: 0.5rem;
                flex-wrap: wrap;
            }

            .activity-icon {
                width: 30px;
                height: 30px;
                font-size: 0.8rem;
            }

            .activity-title {
                font-size: 0.85rem;
            }

            .activity-subtitle {
                font-size: 0.75rem;
            }

            .activity-time {
                width: 100%;
                text-align: right;
                margin-top: 0.25rem;
                font-size: 0.7rem;
            }
        }

        @media (max-width: 480px) {
            .header {
                padding: 0.4rem 0.5rem;
            }

            .logo-img {
                width: 28px;
                height: 28px;
            }

            .logo-text h1 {
                font-size: 0.65rem;
            }

            .toggle-btn {
                padding: 0.3rem;
                font-size: 0.9rem;
                margin-right: 0.2rem;
            }

            .logout-btn {
                padding: 0.35rem 0.5rem;
            }

            .dashboard-container {
                padding: 0 0.5rem;
                margin-top: 55px;
                width: 100vw;
                max-width: 100vw;
            }

            .stats-grid {
                gap: 0.75rem;
            }

            .stat-card {
                padding: 0.75rem;
            }

            .stat-value {
                font-size: 1.5rem;
            }

            .stat-label {
                font-size: 0.8rem;
            }

            .stat-icon {
                width: 40px;
                height: 40px;
                font-size: 1.1rem;
                margin-bottom: 0.75rem;
            }

            .charts-grid {
                gap: 0.75rem;
            }

            .chart-card {
                padding: 0.75rem;
            }

            .chart-card h3 {
                font-size: 0.9rem;
                margin-bottom: 0.75rem;
            }

            .activity-item {
                padding: 0.4rem;
                gap: 0.4rem;
            }

            .activity-icon {
                width: 28px;
                height: 28px;
                font-size: 0.75rem;
            }

            .activity-title {
                font-size: 0.8rem;
            }

            .activity-subtitle {
                font-size: 0.7rem;
            }

            .activity-time {
                font-size: 0.65rem;
            }
        }

    </style>
</head>
<body>
    <?php $page_title = 'Admin Dashboard'; include 'admin_header.php'; ?>

    <aside class="sidebar" id="sidebar">
        <nav class="sidebar-menu">
            <a href="admin_dashboard.php" class="active">
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
            </a>
            <a href="admin_inc.php">
                <i class="fas fa-file-alt"></i>
                <span>INC Requests</span>
            </a>
            <a href="admin_crediting.php">
                <i class="fas fa-graduation-cap"></i>
                <span>Crediting Requests</span>
            </a>
            <a href="admin_interviews.php"><i class="fas fa-calendar-check"></i><span>Interview Requests</span></a>
            <a href="admin_students.php">
                <i class="fas fa-users"></i>
                <span>Students</span>
            </a>
            <a href="admin_notification.php">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
            </a>
            <a href="post_announcement.php">
                <i class="fas fa-bullhorn"></i>
                <span>Post Announcement</span>
            </a>
            <a href="admin_announcements.php">
                <i class="fas fa-eye"></i>
                <span>View Announcements</span>
            </a>
            <a href="admin_upload_grades.php">
                <i class="fas fa-upload"></i>
                <span>Upload Grades</span>
            </a>
        </nav>
    </aside>

    <div class="dashboard-container" id="mainContainer">
        <div class="stats-grid">
            <div class="stat-card purple">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value"><?php echo $total_students; ?></div>
                <div class="stat-label">Total Students</div>
            </div>

            <div class="stat-card green">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?php echo $approved_requests; ?></div>
                <div class="stat-label">Approved Requests</div>
            </div>

            <div class="stat-card orange">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value"><?php echo $pending_requests; ?></div>
                <div class="stat-label">Pending Requests</div>
            </div>

            <div class="stat-card red">
                <div class="stat-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-value"><?php echo $total_requests; ?></div>
                <div class="stat-label">Total Requests (INC + Crediting)</div>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <h3><i class="fas fa-chart-bar"></i> Request Statistics</h3>
                <canvas id="requestChart"></canvas>
            </div>

            <div class="chart-card">
                <h3><i class="fas fa-history"></i> Recent Activities</h3>
                <div class="activity-list">
                    <?php if ($recent_activities && $recent_activities->num_rows > 0): ?>
                        <?php while($activity = $recent_activities->fetch_assoc()): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-<?php echo $activity['action_type'] == 'sent_to_professor' ? 'chalkboard-teacher' : 'user-graduate'; ?>"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title"><?php echo htmlspecialchars($activity['student_name']); ?></div>
                                <div class="activity-subtitle"><?php echo htmlspecialchars($activity['subject']); ?> - <?php echo $activity['action_type'] == 'sent_to_professor' ? 'Sent to Professor' : 'Sent to Student'; ?></div>
                            </div>
                            <div class="activity-time">
                                <?php echo date('M d', strtotime($activity['created_at'])); ?>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align: center; color: #718096; padding: 2rem;">
                            No recent activities
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="chart-card">
            <h3><i class="fas fa-chart-pie"></i> Request Status Distribution</h3>
            <canvas id="statusChart" style="max-height: 300px;"></canvas>
        </div>
    </div>

    <?php include 'admin_logout_modal.php'; ?>

    <script>
        // Request Statistics Chart
        const requestCtx = document.getElementById('requestChart').getContext('2d');
        new Chart(requestCtx, {
            type: 'bar',
            data: {
                labels: ['INC Requests', 'Crediting Requests'],
                datasets: [
                    {
                        label: 'Approved',
                        data: [<?php echo $approved_inc; ?>, <?php echo $approved_crediting; ?>],
                        backgroundColor: 'rgba(16, 185, 129, 0.8)',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        borderWidth: 2,
                        borderRadius: 8
                    },
                    {
                        label: 'Pending',
                        data: [<?php echo $pending_inc; ?>, <?php echo $pending_crediting; ?>],
                        backgroundColor: 'rgba(245, 158, 11, 0.8)',
                        borderColor: 'rgba(245, 158, 11, 1)',
                        borderWidth: 2,
                        borderRadius: 8
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    },
                    x: {
                        stacked: false
                    }
                }
            }
        });

        // Status Distribution Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Approved', 'Pending'],
                datasets: [{
                    data: [<?php echo $approved_requests; ?>, <?php echo $pending_requests; ?>],
                    backgroundColor: [
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)'
                    ],
                    borderColor: [
                        'rgba(16, 185, 129, 1)',
                        'rgba(245, 158, 11, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>

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
</body>
</html>
