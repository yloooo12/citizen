<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "student_services";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "ALTER TABLE grades ADD COLUMN units INT DEFAULT 3";
if ($conn->query($sql) === TRUE) {
    echo "Units column added successfully to grades table!";
} else {
    if (strpos($conn->error, "Duplicate column name") !== false) {
        echo "Units column already exists in grades table.";
    } else {
        echo "Error: " . $conn->error;
    }
}

$conn->close();
?>
