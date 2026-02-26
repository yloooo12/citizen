<?php
session_start();
if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== "secretary") {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

// Get report data
$students_by_year = [];
$result = $conn->query("SELECT year_level, COUNT(*) as count FROM users WHERE is_admin=0 GROUP BY year_level");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students_by_year[$row['year_level'] ?? 'N/A'] = $row['count'];
    }
}

$students_by_type = [];
$result = $conn->query("SELECT student_type, COUNT(*) as count FROM users WHERE is_admin=0 GROUP BY student_type");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students_by_type[$row['student_type'] ?? 'N/A'] = $row['count'];
    }
}

$interview_stats = [
    'total' => $conn->query("SELECT COUNT(*) as count FROM admission_interviews")->fetch_assoc()['count'],
    'pending' => $conn->query("SELECT COUNT(*) as count FROM admission_interviews WHERE status='pending'")->fetch_assoc()['count'],
    'scheduled' => $conn->query("SELECT COUNT(*) as count FROM admission_interviews WHERE status='scheduled'")->fetch_assoc()['count']
];

$crediting_stats = [
    'total' => $conn->query("SELECT COUNT(*) as count FROM program_head_crediting")->fetch_assoc()['count'],
    'pending' => $conn->query("SELECT COUNT(*) as count FROM program_head_crediting WHERE status IN ('pending', 'warning')")->fetch_assoc()['count'],
    'approved' => $conn->query("SELECT COUNT(*) as count FROM program_head_crediting WHERE status='dean_approved'")->fetch_assoc()['count']
];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Secretary</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; color: #333; }
        .main-container { margin-left: 260px; margin-top: 65px; padding: 2rem 2.5rem; height: calc(100vh - 65px); overflow-y: auto; transition: margin-left 0.3s ease; }
        .main-container.collapsed { margin-left: 70px; }
        .card { background: white; border-radius: 16px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 2rem; }
        .card h2 { font-size: 1.5rem; color: #2d3748; margin-bottom: 1.5rem; }
        .card h3 { font-size: 1.2rem; color: #2d3748; margin-bottom: 1rem; }
        .report-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
        .report-box { background: #f9fafb; padding: 1.5rem; border-radius: 12px; border: 1px solid #e5e7eb; }
        .report-item { display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid #e5e7eb; }
        .report-item:last-child { border-bottom: none; }
        .report-label { color: #4a5568; font-weight: 500; }
        .report-value { color: #667eea; font-weight: 700; font-size: 1.1rem; }
        .btn { background: #667eea; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn:hover { background: #5568d3; }
        .btn-success { background: #10b981; }
        .btn-success:hover { background: #059669; }
        .export-section { display: flex; gap: 1rem; flex-wrap: wrap; }
        @media (max-width: 768px) { .main-container { margin-left: 0; padding: 1rem; } .report-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include 'secretary_navbar.php'; ?>

    <main class="main-container" id="mainContainer">
        <div class="card">
            <h2><i class="fas fa-chart-bar"></i> Reports & Analytics</h2>
            <p style="color: #718096; margin-bottom: 2rem; font-size: 0.95rem;">Generate and export various student reports</p>
            
            <div class="report-grid">
                <div class="report-box">
                    <h3><i class="fas fa-graduation-cap"></i> Students by Year Level</h3>
                    <?php foreach ($students_by_year as $year => $count): ?>
                    <div class="report-item">
                        <span class="report-label"><?php echo htmlspecialchars($year); ?></span>
                        <span class="report-value"><?php echo $count; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="report-box">
                    <h3><i class="fas fa-users"></i> Students by Type</h3>
                    <?php foreach ($students_by_type as $type => $count): ?>
                    <div class="report-item">
                        <span class="report-label"><?php echo htmlspecialchars($type); ?></span>
                        <span class="report-value"><?php echo $count; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="report-box">
                    <h3><i class="fas fa-microphone"></i> Interview Statistics</h3>
                    <div class="report-item">
                        <span class="report-label">Total Requests</span>
                        <span class="report-value"><?php echo $interview_stats['total']; ?></span>
                    </div>
                    <div class="report-item">
                        <span class="report-label">Pending</span>
                        <span class="report-value" style="color: #f59e0b;"><?php echo $interview_stats['pending']; ?></span>
                    </div>
                    <div class="report-item">
                        <span class="report-label">Scheduled</span>
                        <span class="report-value" style="color: #10b981;"><?php echo $interview_stats['scheduled']; ?></span>
                    </div>
                </div>
                
                <div class="report-box">
                    <h3><i class="fas fa-clipboard-check"></i> Crediting Statistics</h3>
                    <div class="report-item">
                        <span class="report-label">Total Requests</span>
                        <span class="report-value"><?php echo $crediting_stats['total']; ?></span>
                    </div>
                    <div class="report-item">
                        <span class="report-label">Pending</span>
                        <span class="report-value" style="color: #f59e0b;"><?php echo $crediting_stats['pending']; ?></span>
                    </div>
                    <div class="report-item">
                        <span class="report-label">Approved</span>
                        <span class="report-value" style="color: #10b981;"><?php echo $crediting_stats['approved']; ?></span>
                    </div>
                </div>
            </div>
            
            <h3 style="margin-top: 2rem; margin-bottom: 1rem;"><i class="fas fa-download"></i> Export Reports</h3>
            <div class="export-section">
                <button class="btn" onclick="exportReport('students')">
                    <i class="fas fa-file-csv"></i> Export All Students
                </button>
                <button class="btn" onclick="exportReport('interviews')">
                    <i class="fas fa-file-csv"></i> Export Interview Requests
                </button>
                <button class="btn" onclick="exportReport('crediting')">
                    <i class="fas fa-file-csv"></i> Export Crediting Requests
                </button>
                <button class="btn btn-success" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>
        </div>
    </main>

    <script>
        function exportReport(type) {
            window.location.href = `export_report.php?type=${type}`;
        }
    </script>
</body>
</html>
