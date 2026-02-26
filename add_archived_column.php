<?php
$conn = new mysqli("localhost", "root", "", "student_services");

$conn->query("ALTER TABLE student_subjects ADD COLUMN IF NOT EXISTS archived TINYINT DEFAULT 0");

echo "Archived column added successfully!";

$conn->close();
?>
