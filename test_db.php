<?php
// Test database connection and create table
$conn = new mysqli("localhost", "root", "", "citizenproj");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected to database successfully!<br>";

// Show existing tables
$result = $conn->query("SHOW TABLES");
echo "<h3>Existing tables:</h3>";
while ($row = $result->fetch_array()) {
    echo "- " . $row[0] . "<br>";
}

// Create the table directly
$sql = "CREATE TABLE IF NOT EXISTS ojt_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(255),
    student_id VARCHAR(50),
    student_email VARCHAR(255),
    company_preference VARCHAR(255),
    company_address TEXT,
    preferred_schedule VARCHAR(100),
    skills TEXT,
    requirements_complete TINYINT DEFAULT 0,
    resume_file VARCHAR(255),
    parent_consent VARCHAR(255),
    enrollment_form VARCHAR(255),
    medical_cert VARCHAR(255),
    letter_inquiry VARCHAR(255),
    letter_response VARCHAR(255),
    application_letter VARCHAR(255),
    recommendation_letter VARCHAR(255),
    acceptance_letter VARCHAR(255),
    internship_plan VARCHAR(255),
    internship_contract_lspu VARCHAR(255),
    internship_contract_company VARCHAR(255),
    moa_draft VARCHAR(255),
    certificate_employment VARCHAR(255),
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "<h3>Table 'ojt_requests' created successfully!</h3>";
} else {
    echo "<h3>Error creating table: " . $conn->error . "</h3>";
}

// Show tables again
$result = $conn->query("SHOW TABLES");
echo "<h3>Tables after creation:</h3>";
while ($row = $result->fetch_array()) {
    echo "- " . $row[0] . "<br>";
}

$conn->close();
?>