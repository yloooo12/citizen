<?php
session_start();
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$conn2 = new mysqli("localhost", "root", "", "citizenproj");
if ($conn2->connect_error) die("Connection failed: " . $conn2->connect_error);

// Create notifications table if not exists
$conn2->query("CREATE TABLE IF NOT EXISTS student_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50),
    title VARCHAR(255),
    message TEXT,
    type VARCHAR(50),
    is_read TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Check all students who are now 4th year 1st semester
$result = $conn->query("SELECT DISTINCT student_id, program FROM student_subjects WHERE year_level='4th Year' AND semester='1st'");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $student_id = $row['student_id'];
        
        // Check if notification already sent
        $check = $conn2->query("SELECT id FROM student_notifications WHERE student_id='$student_id' AND type='ojt_eligible' LIMIT 1");
        if ($check->num_rows == 0) {
            // Get student details
            $user_result = $conn->query("SELECT first_name, last_name, email FROM users WHERE id_number='$student_id' LIMIT 1");
            if ($user_result && $user_data = $user_result->fetch_assoc()) {
                $name = $user_data['first_name'] . ' ' . $user_data['last_name'];
                $email = $user_data['email'];
                
                // Insert notification
                $title = "OJT Deployment Now Available";
                $message = "Congratulations! You are now eligible to submit your OJT deployment requirements. As a 4th Year 1st Semester student, you can now prepare and submit your OJT documents through the portal.";
                
                $conn2->query("INSERT INTO student_notifications (student_id, title, message, type, created_at) VALUES ('$student_id', '$title', '$message', 'ojt_eligible', NOW())");
                
                // Send email notification
                if ($email) {
                    $subject = "OJT Deployment Eligibility - LSPU CCS";
                    $email_body = "Dear $name,\n\nCongratulations! You are now eligible for OJT deployment.\n\nAs a 4th Year 1st Semester student, you can now:\n- Submit your OJT deployment requirements\n- Prepare required documents\n- Apply for OJT placement\n\nPlease log in to the student portal to begin your OJT deployment process.\n\nBest regards,\nLSPU College of Computer Studies";
                    
                    mail($email, $subject, $email_body, "From: noreply@lspu.edu.ph");
                }
            }
        }
    }
}

$conn->close();
$conn2->close();
?>