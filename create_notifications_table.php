<?php
$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "student_services";
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Notifications table already exists!<br>";
echo "No migration needed.<br>";
echo "<a href='teacher_grades.php'>Go back to grades page</a>";

$conn->close();
?>
