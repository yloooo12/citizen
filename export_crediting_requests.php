<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "citizenproj");
if ($conn->connect_error) die("Connection failed");

$filter = $_GET['filter'] ?? 'all';
$where = [];

if ($filter == 'pending') $where[] = "status='pending'";
elseif ($filter == 'evaluating') $where[] = "status='evaluating'";
elseif ($filter == 'preparing_document') $where[] = "status='preparing_document'";
elseif ($filter == 'sent_to_registrar') $where[] = "status='sent_to_registrar'";
elseif ($filter == 'approved') $where[] = "status='approved'";
elseif ($filter == 'declined') $where[] = "status='declined'";

$whereClause = !empty($where) ? 'WHERE '.implode(' AND ', $where) : '';
$result = $conn->query("SELECT * FROM crediting_requests $whereClause ORDER BY date_submitted DESC");

$requests = [];
if ($result) while($row = $result->fetch_assoc()) $requests[] = $row;

$conn->close();

header('Content-Type: text/html; charset=utf-8');

?>
<script>window.onload = function() { window.print(); };</script>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        .header h1 { color: #667eea; margin: 0; font-size: 22px; }
        .header p { color: #666; margin: 5px 0; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 9px; }
        th, td { border: 1px solid #ddd; padding: 5px; text-align: left; }
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
        <h1>LSPU Computer Studies - Subject Crediting Requests</h1>
        <p><strong>Filter:</strong> <?php echo ucfirst(str_replace('_', ' ', $filter)); ?> | <strong>Total:</strong> <?php echo count($requests); ?></p>
        <p><strong>Generated:</strong> <?php echo date('F d, Y h:i A'); ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Student Name</th>
                <th>Student ID</th>
                <th>Subjects</th>
                <th>Previous School</th>
                <th>Student Type</th>
                <th>Date Submitted</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($requests as $req): ?>
            <tr>
                <td><?php echo $req['id']; ?></td>
                <td><?php echo htmlspecialchars($req['student_name']); ?></td>
                <td><?php echo htmlspecialchars($req['student_id']); ?></td>
                <td><?php echo htmlspecialchars(substr($req['subjects_to_credit'], 0, 50)).'...'; ?></td>
                <td><?php echo htmlspecialchars($req['previous_school'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars(ucfirst($req['student_type'] ?? '')); ?></td>
                <td><?php echo date('M d, Y', strtotime($req['date_submitted'])); ?></td>
                <td class="status-<?php echo $req['status']; ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $req['status'])); ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        <p>LSPU Computer Studies - Admin Panel | Crediting Request Report</p>
    </div>
</body>
</html>
<?php
