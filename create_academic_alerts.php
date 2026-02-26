<?php
$conn = new mysqli('localhost', 'root', '', 'student_services');
$conn->query('CREATE TABLE IF NOT EXISTS academic_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    student_id VARCHAR(50),
    course VARCHAR(100),
    grade VARCHAR(20),
    program_section VARCHAR(100),
    reason TEXT,
    intervention TEXT,
    instructor VARCHAR(100),
    alert_type VARCHAR(50),
    is_resolved TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_alert (user_id, course, alert_type)
)');
echo 'academic_alerts table created in student_services database';
$conn->close();
?>
