<?php
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Drop old table if exists
$conn->query("DROP TABLE IF EXISTS deployment_ojt_requests");

// Create new ojt_requests table
$result = $conn->query("CREATE TABLE IF NOT EXISTS ojt_requests (
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
)");

if ($result) {
    echo "OJT requests table created successfully!";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>