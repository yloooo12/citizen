<?php
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

$sql = "CREATE TABLE IF NOT EXISTS crediting_alerts (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    student_type VARCHAR(50) NOT NULL,
    program VARCHAR(100) DEFAULT 'BSIT',
    status VARCHAR(50) DEFAULT 'pending',
    reason TEXT DEFAULT 'Automatic crediting for student type',
    intervention TEXT DEFAULT 'Submit required documents for crediting evaluation',
    semester VARCHAR(50) DEFAULT '2nd',
    school_year VARCHAR(50) DEFAULT '2025 - 2026',
    is_resolved TINYINT(4) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX(student_id),
    INDEX(status)
)";

if ($conn->query($sql)) {
    echo "Crediting alerts table created successfully!<br>";
    
    // Create initial alert for transferee
    $student_id = '2023-00001';
    $conn->query("INSERT INTO crediting_alerts (student_id, student_type, status, reason, intervention) 
                  VALUES ('$student_id', 'Transferee', 'pending', 'Automatic crediting for Transferee', 'Submit required documents for crediting evaluation')");
    
    echo "Sample crediting alert created for student $student_id";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>