<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['id_number'] ?? '';
$first_name = $_SESSION['first_name'] ?? '';
$last_name = $_SESSION['last_name'] ?? '';
$email = $_SESSION['email'] ?? '';

// Create notification tables
$conn = new mysqli("localhost", "root", "", "citizenproj");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$conn->query("CREATE TABLE IF NOT EXISTS student_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50),
    title VARCHAR(255),
    message TEXT,
    type VARCHAR(50),
    is_read TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Force create OJT notification for current student
if ($student_id) {
    // Delete existing notification first
    $conn->query("DELETE FROM student_notifications WHERE student_id='$student_id' AND type='ojt_eligible'");
    
    // Insert new notification
    $title = "OJT Deployment Now Available";
    $message = "Congratulations! You are now eligible to submit your OJT deployment requirements. As a 4th Year 1st Semester student, you can now prepare and submit your OJT documents through the portal.";
    
    $result = $conn->query("INSERT INTO student_notifications (student_id, title, message, type, created_at) VALUES ('$student_id', '$title', '$message', 'ojt_eligible', NOW())");
    
    if ($result) {
        echo "✅ Notification created successfully!<br>";
        echo "Student ID: $student_id<br>";
        echo "Title: $title<br>";
        echo "Message: $message<br><br>";
        
        // Check if notification exists
        $check = $conn->query("SELECT * FROM student_notifications WHERE student_id='$student_id' AND type='ojt_eligible'");
        if ($check && $check->num_rows > 0) {
            echo "✅ Notification found in database!<br>";
            while ($row = $check->fetch_assoc()) {
                echo "ID: " . $row['id'] . "<br>";
                echo "Created: " . $row['created_at'] . "<br>";
                echo "Read: " . ($row['is_read'] ? 'Yes' : 'No') . "<br>";
            }
        } else {
            echo "❌ Notification NOT found in database!<br>";
        }
    } else {
        echo "❌ Failed to create notification: " . $conn->error;
    }
} else {
    echo "❌ No student ID found in session";
}

$conn->close();
?>
<br><br>
<a href="academic_alerts.php">Go to Academic Alerts</a> | 
<a href="profile.php">Go to Profile</a>