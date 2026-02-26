<?php
$conn = new mysqli("localhost", "root", "", "citizenproj");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Add missing columns
$conn->query("ALTER TABLE ojt_requests ADD COLUMN company_preference VARCHAR(255) AFTER student_email");
$conn->query("ALTER TABLE ojt_requests ADD COLUMN company_address TEXT AFTER company_preference");
$conn->query("ALTER TABLE ojt_requests ADD COLUMN preferred_schedule VARCHAR(100) AFTER company_address");
$conn->query("ALTER TABLE ojt_requests ADD COLUMN skills TEXT AFTER preferred_schedule");

echo "Missing columns added successfully!";
$conn->close();
?>