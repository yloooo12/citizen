<?php
session_start();
require_once 'send_email_alerts.php';

$conn = new mysqli("localhost", "root", "", "student_services");
$student_id = $_SESSION["id_number"] ?? '';

// Get one alert
$result = $conn->query("SELECT * FROM academic_alerts WHERE student_id='$student_id' AND is_resolved=0 LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $email_result = $conn->query("SELECT email, first_name FROM users WHERE id_number='$student_id'");
    if ($email_result && $email_row = $email_result->fetch_assoc()) {
        echo "Sending email to: " . $email_row['email'] . "<br>";
        echo "Alert: " . $row['course'] . "<br><br>";
        
        if (sendAcademicAlertEmail($email_row['email'], $email_row['first_name'], $row)) {
            echo "Email sent successfully!";
        } else {
            echo "Email failed to send";
        }
    } else {
        echo "No user email found";
    }
} else {
    echo "No alerts found";
}
$conn->close();
?>
