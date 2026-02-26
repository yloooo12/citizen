<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "citizenproj");
if ($conn->connect_error) die("Connection failed");

$student_id = $_SESSION["id_number"] ?? '';
$first_name = $_SESSION["first_name"] ?? '';
$last_name = $_SESSION["last_name"] ?? '';

// Get student data
$inc_requests = [];
$crediting_requests = [];
$grades = [];

if ($student_id) {
    $result = $conn->query("SELECT * FROM inc_requests WHERE student_id='$student_id' ORDER BY date_submitted DESC");
    if ($result) while($row = $result->fetch_assoc()) $inc_requests[] = $row;
    
    $result = $conn->query("SELECT * FROM crediting_requests WHERE student_id='$student_id' ORDER BY date_submitted DESC");
    if ($result) while($row = $result->fetch_assoc()) $crediting_requests[] = $row;
    
    $result = $conn->query("SELECT * FROM student_grades WHERE student_id='$student_id' ORDER BY semester DESC, course ASC");
    if ($result) while($row = $result->fetch_assoc()) $grades[] = $row;
}

$conn->close();

// Output as HTML for browser print-to-PDF
header('Content-Type: text/html; charset=utf-8');

?>
<script>window.onload = function() { window.print(); };</script>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #667eea; padding-bottom: 15px; }
        .header h1 { color: #667eea; margin: 0; }
        .header p { color: #666; margin: 5px 0; }
        .section { margin: 20px 0; }
        .section h2 { background: #667eea; color: white; padding: 10px; margin: 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
        th { background: #f0f4ff; font-weight: bold; }
        .status-approved { color: #10b981; font-weight: bold; }
        .status-pending { color: #f59e0b; font-weight: bold; }
        .status-declined { color: #ef4444; font-weight: bold; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>LSPU Computer Studies</h1>
        <p>Student Academic Report</p>
        <p><strong>Student:</strong> <?php echo htmlspecialchars($first_name.' '.$last_name); ?> | <strong>ID:</strong> <?php echo htmlspecialchars($student_id); ?></p>
        <p><strong>Generated:</strong> <?php echo date('F d, Y h:i A'); ?></p>
    </div>

    <?php if (!empty($grades)): ?>
    <div class="section">
        <h2>Academic Grades</h2>
        <table>
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Grade</th>
                    <th>Semester</th>
                    <th>Program/Section</th>
                    <th>INC Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($grades as $grade): ?>
                <tr>
                    <td><?php echo htmlspecialchars($grade['course']); ?></td>
                    <td><?php echo htmlspecialchars($grade['grade']); ?></td>
                    <td><?php echo htmlspecialchars($grade['semester']); ?></td>
                    <td><?php echo htmlspecialchars($grade['program_section']); ?></td>
                    <td><?php echo $grade['has_inc'] ? ($grade['inc_resolved'] ? 'Resolved' : 'Unresolved') : 'N/A'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if (!empty($inc_requests)): ?>
    <div class="section">
        <h2>INC Removal Requests</h2>
        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Professor</th>
                    <th>Reason</th>
                    <th>Date Submitted</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($inc_requests as $req): ?>
                <tr>
                    <td><?php echo htmlspecialchars($req['subject']); ?></td>
                    <td><?php echo htmlspecialchars($req['professor']); ?></td>
                    <td><?php echo htmlspecialchars(substr($req['inc_reason'], 0, 50)).'...'; ?></td>
                    <td><?php echo date('M d, Y', strtotime($req['date_submitted'])); ?></td>
                    <td class="status-<?php echo $req['approved'] ? 'approved' : 'pending'; ?>">
                        <?php echo $req['approved'] ? 'Approved' : 'Pending'; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if (!empty($crediting_requests)): ?>
    <div class="section">
        <h2>Subject Crediting Requests</h2>
        <table>
            <thead>
                <tr>
                    <th>Subjects</th>
                    <th>Previous School</th>
                    <th>Date Submitted</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($crediting_requests as $req): ?>
                <tr>
                    <td><?php echo htmlspecialchars(substr($req['subjects_to_credit'], 0, 60)).'...'; ?></td>
                    <td><?php echo htmlspecialchars($req['previous_school'] ?? 'N/A'); ?></td>
                    <td><?php echo date('M d, Y', strtotime($req['date_submitted'])); ?></td>
                    <td class="status-<?php echo $req['status']; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $req['status'])); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <div class="footer">
        <p>This is an official document generated from LSPU Computer Studies Citizen's Charter Portal</p>
        <p>For verification, contact the registrar's office</p>
    </div>
</body>
</html>
<?php
