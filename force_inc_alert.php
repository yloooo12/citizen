<?php
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

// Check existing alerts
$result = $conn->query("SELECT * FROM academic_alerts WHERE student_id='0122-0348'");
echo "Existing alerts for 0122-0348: " . $result->num_rows . "\n";

// Force create INC alert
$conn->query("DELETE FROM academic_alerts WHERE student_id='0122-0348' AND alert_type='INC'");
$conn->query("INSERT INTO academic_alerts (student_id, course, grade, program_section, reason, intervention, instructor, semester, school_year, alert_type, is_resolved) 
              VALUES ('0122-0348', 'ITEC103 (Intermediate Programming)', 'INC', 'B. S. Information Technology', 'Failed Grade', 'Contact instructor: Bernardino, Mark', 'Bernardino, Mark', '2nd', '2025 - 2026', 'INC', 0)");

// Insert notification
$notif_msg = "⚠️ Academic Alert: You have an INCOMPLETE (INC) grade for ITEC103 (Intermediate Programming). Contact instructor: Bernardino, Mark to complete requirements.";
$conn->query("INSERT INTO notifications (user_id, message, type, is_read) VALUES ('0122-0348', '$notif_msg', 'academic_alert', 0)");

echo "INC alert created for 0122-0348 - ITEC103\n";

$conn->close();
?>