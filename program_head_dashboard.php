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

// Get statistics
$total_requests = $conn->query("SELECT COUNT(*) as count FROM program_head_crediting")->fetch_assoc()['count'];
$pending_requests = $conn->query("SELECT COUNT(*) as count FROM program_head_crediting WHERE status IN ('pending', 'warning', 'evaluating')")->fetch_assoc()['count'];
$approved_requests = $conn->query("SELECT COUNT(*) as count FROM program_head_crediting WHERE status='approved'")->fetch_assoc()['count'];
$sent_to_dean = $conn->query("SELECT COUNT(*) as count FROM program_head_crediting WHERE status='sent_to_dean'")->fetch_assoc()['count'];
$dean_approved = $conn->query("SELECT COUNT(*) as count FROM program_head_crediting WHERE status='dean_approved'")->fetch_assoc()['count'];

// Recent requests
$recent_query = "SELECT * FROM program_head_crediting ORDER BY created_at DESC LIMIT 5";
$recent_result = $conn->query($recent_query);
$recent_requests = $recent_result ? $recent_result->fetch_all(MYSQLI_ASSOC) : [];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Program Head Dashboard</title>
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
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .stat-card.purple { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .stat-card.orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; }
        .stat-card.green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
        .stat-card.blue { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; }
        .stat-card.teal { background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); color: white; }
        .stat-number { font-size: 2.5rem; font-weight: 700; margin-bottom: 0.5rem; }
        .stat-label { font-size: 0.9rem; opacity: 0.9; }
        .card { background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 2rem; }
        .card h3 { font-size: 1.25rem; color: #2d3748; margin-bottom: 1.5rem; font-weight: 600; }
        .chart-container { height: 300px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f7fafc; font-weight: 600; color: #4a5568; font-size: 0.85rem; }
        tr:hover td { background: #f7fafc; }
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.8rem; font-weight: 600; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-approved { background: #d1fae5; color: #065f46; }
        .status-sent { background: #dbeafe; color: #1e40af; }
        @media (max-width: 768px) {
            .main-container { margin-left: 0; padding: 1rem; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include 'program_head_navbar.php'; ?>
    
    <div class="main-container" id="mainContainer">
        <div class="page-header">
            <h2><i class="fas fa-th-large"></i> Dashboard Overview</h2>
            <p>Welcome back, <?php echo htmlspecialchars($first_name); ?>! Here's your crediting management summary.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card purple">
                <div class="stat-number"><?php echo $total_requests; ?></div>
                <div class="stat-label"><i class="fas fa-clipboard-list"></i> Total Requests</div>
            </div>
            <div class="stat-card orange">
                <div class="stat-number"><?php echo $pending_requests; ?></div>
                <div class="stat-label"><i class="fas fa-clock"></i> Pending Review</div>
            </div>
            <div class="stat-card green">
                <div class="stat-number"><?php echo $approved_requests; ?></div>
                <div class="stat-label"><i class="fas fa-check-circle"></i> Approved</div>
            </div>
            <div class="stat-card blue">
                <div class="stat-number"><?php echo $sent_to_dean; ?></div>
                <div class="stat-label"><i class="fas fa-paper-plane"></i> Sent to Dean</div>
            </div>
            <div class="stat-card teal">
                <div class="stat-number"><?php echo $dean_approved; ?></div>
                <div class="stat-label"><i class="fas fa-award"></i> Dean Approved</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
            <div class="card">
                <h3><i class="fas fa-chart-pie"></i> Request Status Distribution</h3>
                <div class="chart-container"><canvas id="statusChart"></canvas></div>
            </div>
            <div class="card">
                <h3><i class="fas fa-chart-bar"></i> Monthly Trends</h3>
                <div class="chart-container"><canvas id="trendChart"></canvas></div>
            </div>
        </div>

        <div class="card">
            <h3><i class="fas fa-history"></i> Recent Requests</h3>
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Student ID</th>
                        <th>Status</th>
                        <th>Date Submitted</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recent_requests)): ?>
                        <?php foreach($recent_requests as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                            <td>
                                <span class="status-badge <?php 
                                    if ($row['status'] == 'dean_approved') echo 'status-approved';
                                    elseif ($row['status'] == 'approved' || $row['status'] == 'sent_to_dean') echo 'status-sent';
                                    else echo 'status-pending';
                                ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            <td>
                                <a href="program_head_requests.php" style="color: #667eea; text-decoration: none; font-weight: 600;">
                                    <i class="fas fa-arrow-right"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 2rem; color: #718096;">No requests yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Pending', 'Approved', 'Sent to Dean', 'Dean Approved'],
                    datasets: [{
                        data: [<?php echo $pending_requests; ?>, <?php echo $approved_requests; ?>, <?php echo $sent_to_dean; ?>, <?php echo $dean_approved; ?>],
                        backgroundColor: ['#f59e0b', '#10b981', '#3b82f6', '#14b8a6']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            });

            const trendCtx = document.getElementById('trendChart').getContext('2d');
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Requests',
                        data: [12, 19, 15, 25, 22, 30],
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        });
    </script>
</body>
</html>
