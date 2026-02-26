<?php
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

// Update existing INC alerts to have 'INC' as reason
$conn->query("UPDATE academic_alerts SET reason='INC' WHERE student_id='0122-0348' AND alert_type='INC' AND reason='Missing Finals Exam'");
echo "Updated INC alert reasons\n";

$conn->close();
?>