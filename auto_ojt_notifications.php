<?php
// Auto-create OJT notifications for all 4th year 1st semester students
$conn = new mysqli("localhost", "root", "", "student_services");
$conn2 = new mysqli("localhost", "root", "", "citizenproj");

if ($conn->connect_error || $conn2->connect_error) {
    die("Connection failed");
}

// Create notifications table
$conn2->query("CREATE TABLE IF NOT EXISTS student_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50),
    title VARCHAR(255),
    message TEXT,
    type VARCHAR(50),
    is_read TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Get all 4th year 1st semester students
$result = $conn->query("SELECT DISTINCT student_id FROM student_subjects WHERE year_level='4th Year' AND semester='1st'");

$count = 0;
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $student_id = $row['student_id'];
        
        // Check if notification already exists
        $check = $conn2->query("SELECT id FROM student_notifications WHERE student_id='$student_id' AND type='ojt_eligible' LIMIT 1");
        if ($check->num_rows == 0) {
            $title = "OJT Deployment Now Available";
            $message = "Congratulations! You are now eligible to submit your OJT deployment requirements. As a 4th Year 1st Semester student, you can now prepare and submit your OJT documents through the portal.";
            
            $conn2->query("INSERT INTO student_notifications (student_id, title, message, type, created_at) VALUES ('$student_id', '$title', '$message', 'ojt_eligible', NOW())");
            $count++;
        }
    }
}

echo "Created OJT notifications for $count students.";
$conn->close();
$conn2->close();
?>