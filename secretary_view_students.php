<?php
session_start();
if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== "secretary") {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

// Get all students - try multiple approaches
$students = [];

// Try 1: is_admin=0
$query1 = "SELECT * FROM users WHERE is_admin=0";
$result1 = $conn->query($query1);
if ($result1 && $result1->num_rows > 0) {
    while ($row = $result1->fetch_assoc()) {
        $students[] = $row;
    }
}

// Try 2: if no results, try user_type='student'
if (empty($students)) {
    $query2 = "SELECT * FROM users WHERE user_type='student'";
    $result2 = $conn->query($query2);
    if ($result2 && $result2->num_rows > 0) {
        while ($row = $result2->fetch_assoc()) {
            $students[] = $row;
        }
    }
}

// Try 3: if still no results, get ALL users
if (empty($students)) {
    $query3 = "SELECT * FROM users";
    $result3 = $conn->query($query3);
    if ($result3 && $result3->num_rows > 0) {
        while ($row = $result3->fetch_assoc()) {
            // Filter out admins manually
            if (!isset($row['is_admin']) || $row['is_admin'] == 0) {
                $students[] = $row;
            }
        }
    }
}

// Apply filters after fetching
$search = $_GET['search'] ?? '';
$year_level = $_GET['year_level'] ?? '';
$student_type = $_GET['student_type'] ?? '';

if (!empty($search) || !empty($year_level) || !empty($student_type)) {
    $students = array_filter($students, function($s) use ($search, $year_level, $student_type) {
        $match = true;
        if (!empty($search)) {
            $match = $match && (stripos($s['id_number'], $search) !== false || 
                               stripos($s['first_name'], $search) !== false || 
                               stripos($s['last_name'], $search) !== false || 
                               stripos($s['email'], $search) !== false);
        }
        if (!empty($year_level)) {
            $match = $match && ($s['year_level'] ?? '') == $year_level;
        }
        if (!empty($student_type)) {
            $match = $match && ($s['student_type'] ?? '') == $student_type;
        }
        return $match;
    });
}

// Get statistics
$total_students = count($students);
$freshmen = 0;
$transferees = 0;
foreach ($students as $s) {
    if (($s['student_type'] ?? '') == 'Freshmen') $freshmen++;
    if (($s['student_type'] ?? '') == 'Transferee') $transferees++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Students - Secretary</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; color: #333; }
        .main-container { margin-left: 260px; margin-top: 65px; padding: 2rem 2.5rem; height: calc(100vh - 65px); overflow-y: auto; transition: margin-left 0.3s ease; }
        .main-container.collapsed { margin-left: 70px; }
        .card { background: white; border-radius: 16px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 2rem; }
        .card h2 { font-size: 1.5rem; color: #2d3748; margin-bottom: 1.5rem; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 1.5rem; border-radius: 12px; color: white; }
        .stat-card:nth-child(2) { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .stat-card:nth-child(3) { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .stat-value { font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem; }
        .stat-label { font-size: 0.9rem; opacity: 0.9; }
        .filters { display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 1rem; margin-bottom: 1.5rem; }
        .filters input, .filters select { padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; }
        .btn { background: #667eea; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .btn:hover { background: #5568d3; }
        .btn-export { background: #10b981; }
        .btn-export:hover { background: #059669; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 0.9rem; }
        th { background: #f7fafc; font-weight: 600; color: #4a5568; }
        tr:hover td { background: #f7fafc; }
        .badge { padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.8rem; font-weight: 600; }
        .badge-freshmen { background: #dbeafe; color: #1e40af; }
        .badge-transferee { background: #fef3c7; color: #92400e; }
        .badge-shifter { background: #e0e7ff; color: #4338ca; }
        .badge-returnee { background: #d1fae5; color: #065f46; }
        @media (max-width: 768px) { .main-container { margin-left: 0; padding: 1rem; } .filters { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include 'secretary_navbar.php'; ?>

    <main class="main-container" id="mainContainer">
        <div class="card">
            <h2><i class="fas fa-users"></i> Student Directory</h2>
            
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_students; ?></div>
                    <div class="stat-label"><i class="fas fa-users"></i> Total Students</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $freshmen; ?></div>
                    <div class="stat-label"><i class="fas fa-user-graduate"></i> Freshmen</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $transferees; ?></div>
                    <div class="stat-label"><i class="fas fa-exchange-alt"></i> Transferees</div>
                </div>
            </div>
            
            <form method="get" class="filters">
                <input type="text" name="search" placeholder="Search by ID, name, or email..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="year_level">
                    <option value="">All Year Levels</option>
                    <option value="1st Year" <?php echo $year_level == '1st Year' ? 'selected' : ''; ?>>1st Year</option>
                    <option value="2nd Year" <?php echo $year_level == '2nd Year' ? 'selected' : ''; ?>>2nd Year</option>
                    <option value="3rd Year" <?php echo $year_level == '3rd Year' ? 'selected' : ''; ?>>3rd Year</option>
                    <option value="4th Year" <?php echo $year_level == '4th Year' ? 'selected' : ''; ?>>4th Year</option>
                </select>
                <select name="student_type">
                    <option value="">All Types</option>
                    <option value="Freshmen" <?php echo $student_type == 'Freshmen' ? 'selected' : ''; ?>>Freshmen</option>
                    <option value="Transferee" <?php echo $student_type == 'Transferee' ? 'selected' : ''; ?>>Transferee</option>
                    <option value="Shifter" <?php echo $student_type == 'Shifter' ? 'selected' : ''; ?>>Shifter</option>
                    <option value="Returnee" <?php echo $student_type == 'Returnee' ? 'selected' : ''; ?>>Returnee</option>
                </select>
                <button type="submit" class="btn"><i class="fas fa-search"></i> Filter</button>
            </form>
            
            <div style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
                <div style="color: #718096;">Showing <?php echo count($students); ?> student(s)</div>
                <button class="btn btn-export" onclick="exportToCSV()"><i class="fas fa-download"></i> Export CSV</button>
            </div>
            
            <table id="studentsTable">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Year Level</th>
                        <th>Type</th>
                        <th>Program</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($students) > 0): ?>
                        <?php foreach($students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['id_number']); ?></td>
                            <td><?php echo htmlspecialchars(($student['last_name'] ?? '') . ', ' . ($student['first_name'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td><?php echo htmlspecialchars($student['year_level'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="badge badge-<?php echo strtolower($student['student_type'] ?? 'freshmen'); ?>">
                                    <?php echo htmlspecialchars($student['student_type'] ?? 'N/A'); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($student['program'] ?? 'BSIT'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem; color: #718096;">No students found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
    <?php $conn->close(); ?>

    <script>
        function exportToCSV() {
            const table = document.getElementById('studentsTable');
            let csv = [];
            
            for (let row of table.rows) {
                let cols = [];
                for (let cell of row.cells) {
                    cols.push('"' + cell.innerText.replace(/"/g, '""') + '"');
                }
                csv.push(cols.join(','));
            }
            
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'students_' + new Date().toISOString().slice(0,10) + '.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>
