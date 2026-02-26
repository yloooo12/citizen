<?php
session_start();
if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] != 'program_head') {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "student_services");

// Get statistics
$total_students = 0;
$total_inc = 0;
$total_crediting = 0;
$total_unscheduled = 0;
$total_ojt = 0;

$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type='student'");
if ($result) $total_students = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM inc_requests");
if ($result) $total_inc = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM crediting_requests");
if ($result) $total_crediting = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM unscheduled_requests");
if ($result) $total_unscheduled = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM ojt_requests");
if ($result) $total_ojt = $result->fetch_assoc()['count'];

// Get students with INC grades
$inc_students = [];
$result = $conn->query("SELECT DISTINCT u.id_number, u.first_name, u.last_name, u.email, aa.course, aa.instructor 
                        FROM academic_alerts aa
                        INNER JOIN users u ON aa.student_id = u.id_number
                        WHERE aa.alert_type = 'INC' AND aa.is_resolved = 0
                        ORDER BY u.last_name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $inc_students[] = $row;
    }
}

// Get crediting requests summary
$crediting_summary = [];
$result = $conn->query("SELECT status, COUNT(*) as count FROM crediting_requests GROUP BY status");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $crediting_summary[$row['status']] = $row['count'];
    }
}

// Get OJT submissions summary
$ojt_summary = [];
$result = $conn->query("SELECT status, COUNT(*) as count FROM ojt_requests GROUP BY status");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $ojt_summary[$row['status']] = $row['count'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reports - Program Head</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; }
        .main-container { margin-left: 260px; margin-top: 85px; padding: 2rem; }
        .header { background: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border-left: 5px solid #667eea; }
        .header h1 { color: #2d3748; font-size: 2rem; margin-bottom: 0.5rem; }
        .header p { color: #718096; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .stat-value { font-size: 2.5rem; font-weight: 700; color: #667eea; }
        .stat-label { color: #718096; font-size: 0.9rem; margin-top: 0.5rem; }
        .report-section { background: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .report-section h2 { color: #2d3748; margin-bottom: 1.5rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f7fafc; font-weight: 600; color: #2d3748; }
        .btn-export { background: #10b981; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .btn-export:hover { background: #059669; }
        .summary-item { display: flex; justify-content: space-between; padding: 0.75rem; border-bottom: 1px solid #e2e8f0; }
        .summary-label { color: #4a5568; }
        .summary-value { font-weight: 600; color: #2d3748; }
    </style>
</head>
<body>
    <?php include 'program_head_navbar.php'; ?>
    
    <div class="main-container">
        <div class="header">
            <h1><i class="fas fa-chart-bar"></i> Reports & Analytics</h1>
            <p>Overview of student requests and academic status</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_students; ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_inc; ?></div>
                <div class="stat-label">INC Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_crediting; ?></div>
                <div class="stat-label">Crediting Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_unscheduled; ?></div>
                <div class="stat-label">Unscheduled Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_ojt; ?></div>
                <div class="stat-label">OJT Submissions</div>
            </div>
        </div>
        
        <div class="report-section">
            <h2><i class="fas fa-exclamation-triangle"></i> Students with INC Grades (<?php echo count($inc_students); ?>)</h2>
            <button class="btn-export" onclick="exportTable('inc-table', 'INC_Students_Report')"><i class="fas fa-download"></i> Export to CSV</button>
            <table id="inc-table" style="margin-top: 1rem;">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Instructor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inc_students as $student): ?>
                    <tr>
                        <td><?php echo $student['id_number']; ?></td>
                        <td><?php echo $student['last_name'] . ', ' . $student['first_name']; ?></td>
                        <td><?php echo $student['email']; ?></td>
                        <td><?php echo $student['course']; ?></td>
                        <td><?php echo $student['instructor']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="report-section">
            <h2><i class="fas fa-certificate"></i> Crediting Requests Summary</h2>
            <?php foreach ($crediting_summary as $status => $count): ?>
                <div class="summary-item">
                    <span class="summary-label"><?php echo ucfirst($status); ?></span>
                    <span class="summary-value"><?php echo $count; ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="report-section">
            <h2><i class="fas fa-briefcase"></i> OJT Submissions Summary</h2>
            <?php foreach ($ojt_summary as $status => $count): ?>
                <div class="summary-item">
                    <span class="summary-label"><?php echo ucfirst($status); ?></span>
                    <span class="summary-value"><?php echo $count; ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script>
    function exportTable(tableId, filename) {
        const table = document.getElementById(tableId);
        let csv = [];
        const rows = table.querySelectorAll('tr');
        
        for (let row of rows) {
            const cols = row.querySelectorAll('td, th');
            const csvRow = [];
            for (let col of cols) {
                csvRow.push('"' + col.innerText.replace(/"/g, '""') + '"');
            }
            csv.push(csvRow.join(','));
        }
        
        const csvContent = csv.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename + '.csv';
        a.click();
    }
    </script>
</body>
</html>
<?php $conn->close(); ?>
