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

// Get all students who submitted crediting requests
$query = "SELECT DISTINCT student_id, student_name, student_type, 
          (SELECT COUNT(*) FROM program_head_crediting WHERE student_id = phc.student_id) as total_requests,
          (SELECT COUNT(*) FROM program_head_crediting WHERE student_id = phc.student_id AND status='approved') as approved_count,
          (SELECT MAX(created_at) FROM program_head_crediting WHERE student_id = phc.student_id) as last_request
          FROM program_head_crediting phc
          ORDER BY last_request DESC";
$result = $conn->query($query);
$students = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Directory - Program Head</title>
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
        .search-bar { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 2rem; }
        .search-bar input { width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; }
        .search-bar input:focus { outline: none; border-color: #667eea; }
        .card { background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f7fafc; font-weight: 600; color: #4a5568; font-size: 0.85rem; }
        tr:hover td { background: #f7fafc; }
        .badge { padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.8rem; font-weight: 600; }
        .badge-transferee { background: #dbeafe; color: #1e40af; }
        .badge-shiftee { background: #fef3c7; color: #92400e; }
        .btn { background: #667eea; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-size: 0.85rem; text-decoration: none; display: inline-block; }
        .btn:hover { background: #5568d3; }
        @media (max-width: 768px) {
            .main-container { margin-left: 0; padding: 1rem; }
        }
    </style>
</head>
<body>
    <?php include 'program_head_navbar.php'; ?>
    
    <div class="main-container" id="mainContainer">
        <div class="page-header">
            <h2><i class="fas fa-users"></i> Student Directory</h2>
            <p>View all students who submitted crediting requests</p>
        </div>

        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="🔍 Search by student name or ID..." onkeyup="filterTable()">
        </div>

        <div class="card">
            <table id="studentTable">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Type</th>
                        <th>Total Requests</th>
                        <th>Approved</th>
                        <th>Last Request</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($students)): ?>
                        <?php foreach($students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                            <td>
                                <span class="badge <?php echo $student['student_type'] == 'Transferee' ? 'badge-transferee' : 'badge-shiftee'; ?>">
                                    <?php echo htmlspecialchars($student['student_type']); ?>
                                </span>
                            </td>
                            <td><?php echo $student['total_requests']; ?></td>
                            <td><?php echo $student['approved_count']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($student['last_request'])); ?></td>
                            <td>
                                <a href="program_head_requests.php" class="btn">
                                    <i class="fas fa-eye"></i> View Requests
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem; color: #718096;">No students found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function filterTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('studentTable');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                const tdId = tr[i].getElementsByTagName('td')[0];
                const tdName = tr[i].getElementsByTagName('td')[1];
                if (tdId || tdName) {
                    const txtId = tdId.textContent || tdId.innerText;
                    const txtName = tdName.textContent || tdName.innerText;
                    if (txtId.toUpperCase().indexOf(filter) > -1 || txtName.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = '';
                    } else {
                        tr[i].style.display = 'none';
                    }
                }
            }
        }
    </script>
</body>
</html>
