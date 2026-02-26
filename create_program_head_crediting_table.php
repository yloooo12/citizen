<?php
// Create program head crediting table
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create program_head_crediting table
$sql = "CREATE TABLE IF NOT EXISTS program_head_crediting (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50),
    student_name VARCHAR(100),
    student_type VARCHAR(20),
    subjects_to_credit TEXT,
    transcript_info TEXT,
    transcript_file VARCHAR(255),
    credited_subjects TEXT,
    evaluation_remarks TEXT,
    program_head_approved TINYINT DEFAULT 0,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "✅ program_head_crediting table created successfully!<br>";
} else {
    echo "❌ Error creating table: " . $conn->error . "<br>";
}

// Transfer existing data from crediting_requests to program_head_crediting
$transfer_sql = "INSERT INTO program_head_crediting (student_id, student_type, status, created_at)
                 SELECT student_id, student_type, status, created_at 
                 FROM crediting_requests 
                 WHERE NOT EXISTS (
                     SELECT 1 FROM program_head_crediting 
                     WHERE program_head_crediting.student_id = crediting_requests.student_id
                 )";

if ($conn->query($transfer_sql) === TRUE) {
    echo "✅ Data transferred to program_head_crediting table!<br>";
} else {
    echo "❌ Error transferring data: " . $conn->error . "<br>";
}

echo "<br><strong>Table Structure:</strong><br>";
echo "- id: Primary key<br>";
echo "- student_id: Student ID number<br>";
echo "- student_name: Full name<br>";
echo "- student_type: Transferee/Shifter/Returnee<br>";
echo "- subjects_to_credit: Subjects for crediting<br>";
echo "- transcript_info: Previous school info<br>";
echo "- transcript_file: Uploaded transcript<br>";
echo "- credited_subjects: Program head evaluation<br>";
echo "- evaluation_remarks: Program head remarks<br>";
echo "- program_head_approved: 0=pending, 1=approved<br>";
echo "- status: pending/approved/declined<br>";

$conn->close();
?>