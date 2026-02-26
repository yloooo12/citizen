<?php
session_start();
$conn = new mysqli("localhost", "root", "", "student_services");
$student_id = $_SESSION["id_number"] ?? '';

// Update the alert to unresolved and update details
$conn->query("UPDATE academic_alerts 
              SET is_resolved=0, 
                  course='Admission Interview Request', 
                  grade='PENDING',
                  reason='Interview request submitted',
                  intervention='Waiting for secretary to send interview schedule'
              WHERE student_id='$student_id' AND alert_type='INTERVIEW'");

echo "Interview alert updated! Check your dashboard now.";
$conn->close();
?>
