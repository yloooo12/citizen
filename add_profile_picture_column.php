<?php
// Script to add profile_picture column to users table
$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "student_services";

$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add profile_picture column
$sql = "ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) NULL";
if ($conn->query($sql) === TRUE) {
    echo "Column profile_picture added successfully to users table";
} else {
    if (strpos($conn->error, 'Duplicate column name') !== false) {
        echo "Column profile_picture already exists";
    } else {
        echo "Error: " . $conn->error;
    }
}

$conn->close();
?>
