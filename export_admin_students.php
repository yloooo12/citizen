<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "citizenproj");
if ($conn->connect_error) die("Connection failed");

$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$admin_ids = ['246', '999'];
$where = ["id_number NOT IN ('".implode("','", $admin_ids)."')"];

if ($search) {
    $search = $conn->real_escape_string($search);
    $where[] = "(first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR id_number LIKE '%$search%')";
}

if ($filter == 'pending') $where[] = "status='pending'";
elseif ($filter == 'approved') $where[] = "status='approved'";
elseif ($filter == 'declined') $where[] = "status='declined'";

$whereClause = 'WHERE '.implode(' AND ', $where);
$result = $conn->query("SELECT * FROM users $whereClause ORDER BY last_name, first_name");

$students = [];
if ($result) while($row = $result->fetch_assoc()) $students[] = $row;

$conn->close();

header('Content-Type: text/html; charset=utf-8');

?>
<script>
window.onload = function() {
    window.print();
};
</script>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        .header h1 { color: #667eea; margin: 0; font-size: 24px; }
        .header p { color: #666; margin: 5px 0; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background: #667eea; color: white; font-weight: bold; }
        tr:nth-child(even) { background: #f9f9f9; }
        .status-approved { color: #10b981; font-weight: bold; }
        .status-pending { color: #f59e0b; font-weight: bold; }
        .status-declined { color: #ef4444; font-weight: bold; }
        .footer { margin-top: 20px; text-align: center; font-size: 9px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>LSPU Computer Studies - Student Accounts</h1>
        <p><strong>Filter:</strong> <?php echo ucfirst($filter); ?> | <strong>Total Records:</strong> <?php echo count($students); ?></p>
        <p><strong>Generated:</strong> <?php echo date('F d, Y h:i A'); ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Student ID</th>
                <th>Last Name</th>
                <th>First Name</th>
                <th>Middle Name</th>
                <th>Sex</th>
                <th>Email</th>
                <th>Contact</th>
                <th>Date Registered</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($students as $student): ?>
            <tr>
                <td><?php echo htmlspecialchars($student['id_number']); ?></td>
                <td><?php echo htmlspecialchars($student['last_name']); ?></td>
                <td><?php echo htmlspecialchars($student['first_name']); ?></td>
                <td><?php echo htmlspecialchars($student['middle_name'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($student['sex']); ?></td>
                <td><?php echo htmlspecialchars($student['email']); ?></td>
                <td><?php echo htmlspecialchars($student['contact_number']); ?></td>
                <td><?php echo isset($student['created_at']) ? date('M d, Y', strtotime($student['created_at'])) : ''; ?></td>
                <td class="status-<?php echo $student['status']; ?>">
                    <?php echo ucfirst($student['status']); ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        <p>LSPU Computer Studies - Admin Panel | This document is system-generated</p>
    </div>
</body>
</html>
<?php
