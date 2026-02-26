<?php
$conn = new mysqli("localhost", "root", "", "student_services");

// Drop old table and create new one
$sql = "DROP TABLE IF EXISTS student_subjects";
if ($conn->query($sql)) {
    echo "✅ Dropped old table<br>";
}

$sql = "CREATE TABLE student_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL,
    subject_code VARCHAR(20) NOT NULL,
    subject_title VARCHAR(255),
    year_level VARCHAR(20),
    section VARCHAR(50),
    teacher_id VARCHAR(20),
    school_year VARCHAR(20),
    semester VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_enrollment (student_id, subject_code, teacher_id)
)";

if ($conn->query($sql)) {
    echo "✅ Created new student_subjects table<br>";
} else {
    echo "❌ Error: " . $conn->error . "<br>";
}

$conn->close();
echo "<br><a href='teacher_upload_students.php'>Go to Upload Students</a>";
?>
