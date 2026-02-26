<?php
session_start();
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

$id = intval($_GET['id']);
$result = $conn->query("SELECT * FROM unscheduled_requests WHERE id=$id");
$request = $result->fetch_assoc();

if (!$request) {
    die("Request not found");
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Unscheduled Subject Approval Document</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h2 { margin: 5px 0; }
        .content { margin: 20px 0; line-height: 1.8; }
        .signature-section { margin-top: 50px; }
        .signature-box { border-top: 2px solid #000; width: 300px; margin-top: 60px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        td { padding: 8px; }
        .label { font-weight: bold; width: 200px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>LAGUNA STATE POLYTECHNIC UNIVERSITY</h2>
        <h3>College of Computer Studies</h3>
        <p>Unscheduled Subject Request Approval</p>
    </div>
    
    <div class="content">
        <p><strong>Date:</strong> <?php echo date('F d, Y'); ?></p>
        
        <table>
            <tr>
                <td class="label">Student Name:</td>
                <td><?php echo htmlspecialchars($request['student_name']); ?></td>
            </tr>
            <tr>
                <td class="label">Student ID:</td>
                <td><?php echo htmlspecialchars($request['student_id']); ?></td>
            </tr>
            <tr>
                <td class="label">Email:</td>
                <td><?php echo htmlspecialchars($request['student_email']); ?></td>
            </tr>
            <tr>
                <td class="label">Date Submitted:</td>
                <td><?php echo date('F d, Y h:i A', strtotime($request['date_submitted'])); ?></td>
            </tr>
            <tr>
                <td class="label">Date Approved:</td>
                <td><?php echo $request['approved_at'] ? date('F d, Y h:i A', strtotime($request['approved_at'])) : 'N/A'; ?></td>
            </tr>
        </table>
        
        <p><strong>Subject Code:</strong> <?php echo htmlspecialchars($request['subject_code']); ?></p>
        <p><strong>Subject Name:</strong> <?php echo htmlspecialchars($request['subject_name']); ?></p>
        
        <p><strong>Reason for Request:</strong></p>
        <p style="border: 1px solid #ccc; padding: 15px; background: #f9f9f9; white-space: pre-wrap;"><?php echo htmlspecialchars($request['reason']); ?></p>
        
        <p><strong>Evaluation File:</strong> <a href="uploads/evaluations/<?php echo htmlspecialchars($request['eval_file']); ?>" target="_blank">View Attached File</a></p>
        
        <?php if ($request['dean_remarks']): ?>
        <p><strong>Dean's Remarks:</strong></p>
        <p style="border: 1px solid #ccc; padding: 15px; background: #f9f9f9;"><?php echo htmlspecialchars($request['dean_remarks']); ?></p>
        <?php endif; ?>
        
        <div class="signature-section">
            <p><strong>Status:</strong> <span style="color: green; font-weight: bold;">APPROVED</span></p>
            
            <?php if ($request['dean_signature']): ?>
            <p><strong>Dean's Digital Signature:</strong></p>
            <img src="uploads/signatures/<?php echo htmlspecialchars($request['dean_signature']); ?>" style="max-width: 300px; border: 1px solid #ccc; padding: 10px;">
            <?php endif; ?>
            
            <div class="signature-box">
                <p style="text-align: center; margin-top: 10px;">
                    <strong>Dean, College of Computer Studies</strong><br>
                    Laguna State Polytechnic University
                </p>
            </div>
        </div>
    </div>
    
    <script>
        window.onload = function() {
            <?php if (isset($_GET['action']) && $_GET['action'] == 'download'): ?>
            window.print();
            <?php endif; ?>
        }
    </script>
</body>
</html>
