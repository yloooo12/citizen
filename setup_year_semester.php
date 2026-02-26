<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "student_services";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h3>Adding Year Level, Semester, and School Year columns</h3>";

$sql1 = "ALTER TABLE users ADD COLUMN year_level VARCHAR(50)";
if ($conn->query($sql1) === TRUE) {
    echo "✓ year_level column added<br>";
} else {
    if (strpos($conn->error, "Duplicate column name") !== false) {
        echo "✓ year_level column already exists<br>";
    } else {
        echo "✗ Error: " . $conn->error . "<br>";
    }
}

$sql2 = "ALTER TABLE users ADD COLUMN semester VARCHAR(50)";
if ($conn->query($sql2) === TRUE) {
    echo "✓ semester column added<br>";
} else {
    if (strpos($conn->error, "Duplicate column name") !== false) {
        echo "✓ semester column already exists<br>";
    } else {
        echo "✗ Error: " . $conn->error . "<br>";
    }
}

$sql3 = "ALTER TABLE users ADD COLUMN school_year VARCHAR(50)";
if ($conn->query($sql3) === TRUE) {
    echo "✓ school_year column added<br>";
} else {
    if (strpos($conn->error, "Duplicate column name") !== false) {
        echo "✓ school_year column already exists<br>";
    } else {
        echo "✗ Error: " . $conn->error . "<br>";
    }
}

echo "<br><strong>Done! You can now set year_level, semester, and school_year for users.</strong>";

$conn->close();
?>
