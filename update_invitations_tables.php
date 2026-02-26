<?php
$conn = new mysqli('localhost', 'root', '', 'student_services');

echo "<h3>Updating Invitations Tables</h3>";

// Add columns to student_invitations
$sql1 = "ALTER TABLE student_invitations ADD COLUMN year_level VARCHAR(50)";
if ($conn->query($sql1) === TRUE) {
    echo "✓ year_level added to student_invitations<br>";
} else {
    if (strpos($conn->error, "Duplicate column") !== false) {
        echo "✓ year_level already exists in student_invitations<br>";
    } else {
        echo "✗ Error: " . $conn->error . "<br>";
    }
}

$sql2 = "ALTER TABLE student_invitations ADD COLUMN semester VARCHAR(50)";
if ($conn->query($sql2) === TRUE) {
    echo "✓ semester added to student_invitations<br>";
} else {
    if (strpos($conn->error, "Duplicate column") !== false) {
        echo "✓ semester already exists in student_invitations<br>";
    } else {
        echo "✗ Error: " . $conn->error . "<br>";
    }
}

$sql3 = "ALTER TABLE student_invitations ADD COLUMN school_year VARCHAR(50)";
if ($conn->query($sql3) === TRUE) {
    echo "✓ school_year added to student_invitations<br>";
} else {
    if (strpos($conn->error, "Duplicate column") !== false) {
        echo "✓ school_year already exists in student_invitations<br>";
    } else {
        echo "✗ Error: " . $conn->error . "<br>";
    }
}

// Add columns to teacher_invitations
$sql4 = "ALTER TABLE teacher_invitations ADD COLUMN year_level VARCHAR(50)";
if ($conn->query($sql4) === TRUE) {
    echo "✓ year_level added to teacher_invitations<br>";
} else {
    if (strpos($conn->error, "Duplicate column") !== false) {
        echo "✓ year_level already exists in teacher_invitations<br>";
    } else {
        echo "✗ Error: " . $conn->error . "<br>";
    }
}

$sql5 = "ALTER TABLE teacher_invitations ADD COLUMN semester VARCHAR(50)";
if ($conn->query($sql5) === TRUE) {
    echo "✓ semester added to teacher_invitations<br>";
} else {
    if (strpos($conn->error, "Duplicate column") !== false) {
        echo "✓ semester already exists in teacher_invitations<br>";
    } else {
        echo "✗ Error: " . $conn->error . "<br>";
    }
}

$sql6 = "ALTER TABLE teacher_invitations ADD COLUMN school_year VARCHAR(50)";
if ($conn->query($sql6) === TRUE) {
    echo "✓ school_year added to teacher_invitations<br>";
} else {
    if (strpos($conn->error, "Duplicate column") !== false) {
        echo "✓ school_year already exists in teacher_invitations<br>";
    } else {
        echo "✗ Error: " . $conn->error . "<br>";
    }
}

echo "<br><strong>Done! Tables updated successfully.</strong>";
$conn->close();
?>
