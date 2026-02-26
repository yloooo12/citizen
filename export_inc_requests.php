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

if ($filter == 'pending') $where[] = "approved=0 AND prof_approved=0 AND student_approved=0";
elseif ($filter == 'prof_reviewing') $where[] = "prof_approved=1 AND student_approved=0 AND approved=0";
elseif ($filter == 'waiting_approval') $where[] = "student_approved=1 AND approved=0";
elseif ($filter == 'approved') $where[] = "approved=1";

$whereClause = !empty($where) ? 'WHERE '.implode(' AND ', $where) : '';
$result = $conn->query("SELECT * FROM inc_requests $whereClause ORDER BY date_submitted DESC");

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
        .footer { margin-top: 20px; text-align: center; font-size: 9px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>LSPU Computer Studies - INC Removal Requests</h1>
        <p><strong>Filter:</strong> <?php echo ucfirst(str_replace('_', ' ', $filter)); ?> | <strong>Total:</strong> <?php echo count($requests); ?></p>
        <p><strong>Generated:</strong> <?php echo date('F d, Y h:i A'); ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Student Name</th>
                <th>Student ID</th>
                <th>Subject</th>
                <th>Professor</th>
                <th>Reason</th>
                <th>Semester</th>
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
                <td><?php echo htmlspecialchars($req['subject']); ?></td>
                <td><?php echo htmlspecialchars($req['professor']); ?></td>
                <td><?php echo htmlspecialchars(substr($req['inc_reason'], 0, 40)).'...'; ?></td>
                <td><?php echo htmlspecialchars($req['inc_semester']); ?></td>
                <td><?php echo date('M d, Y', strtotime($req['date_submitted'])); ?></td>
                <td class="status-<?php echo $req['approved'] ? 'approved' : 'pending'; ?>">
                    <?php 
                    if ($req['approved']) echo 'Approved';
                    elseif ($req['student_approved']) echo 'Waiting Approval';
                    elseif ($req['prof_approved']) echo 'Prof Reviewing';
                    else echo 'Pending';
                    ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        <p>LSPU Computer Studies - Admin Panel | INC Request Report</p>
    </div>
</body>
</html>
<?php
