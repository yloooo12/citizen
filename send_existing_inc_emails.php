<?php
require_once 'configure_email.php';
$conn = new mysqli("localhost", "root", "", "student_services");

echo "<h2>Sending Emails for Existing INC Alerts...</h2>";

$result = $conn->query("SELECT aa.id, aa.student_id, aa.course, aa.instructor, u.email, u.first_name 
                        FROM academic_alerts aa
                        INNER JOIN users u ON aa.student_id = u.id_number
                        WHERE aa.alert_type = 'INC' 
                        AND aa.is_resolved = 0
                        AND NOT EXISTS (
                            SELECT 1 FROM email_logs 
                            WHERE recipient_email = u.email 
                            AND subject LIKE '%Incomplete Grade%'
                            AND message LIKE CONCAT('%', aa.course, '%')
                        )");

$count = 0;
while ($row = $result->fetch_assoc()) {
    $subject = 'Academic Alert: Incomplete Grade';
    $message = "Dear {$row['first_name']},\n\nYou have an INCOMPLETE (INC) grade for {$row['course']} due to missing final exam (score: 0).\n\nPlease contact your instructor: {$row['instructor']} to complete the requirements.\n\nProgram: B.S. Information Technology\nSemester: 2nd 2025-2026\n\nThank you.\nLSPU-CCS";
    
    sendEmail($row['email'], $subject, $message, $row['first_name']);
    echo "✓ Email sent to {$row['first_name']} ({$row['email']}) - {$row['course']}<br>";
    $count++;
}

echo "<br><strong>Done! Sent $count emails.</strong>";
$conn->close();
?>
