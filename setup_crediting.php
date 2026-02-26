<?php
// Setup crediting tables and automatic crediting for transferees, shifters, returnees

$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create crediting_requests table
$conn->query("CREATE TABLE IF NOT EXISTS crediting_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50),
    student_type VARCHAR(20),
    status VARCHAR(20) DEFAULT 'pending',
    documents_submitted TINYINT DEFAULT 0,
    evaluator_id VARCHAR(50),
    evaluation_notes TEXT,
    approved_subjects TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Add student_type column to users table if not exists
$result = $conn->query("DESCRIBE users");
$has_student_type = false;
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ($row['Field'] == 'student_type') {
            $has_student_type = true;
            break;
        }
    }
}

if (!$has_student_type) {
    $conn->query("ALTER TABLE users ADD COLUMN student_type VARCHAR(20) DEFAULT 'Freshmen'");
    echo "✅ Added student_type column to users table.<br>";
}

echo "✅ Crediting system setup complete!<br>";
echo "<br><strong>How it works:</strong><br>";
echo "1. Students with student_type = 'Transferee', 'Shifter', or 'Returnee' will automatically get crediting requests<br>";
echo "2. Academic alert will appear on their dashboard<br>";
echo "3. They can view and manage their crediting request through crediting.php<br>";
echo "<br><strong>To test:</strong><br>";
echo "1. Update a student's student_type to 'Transferee', 'Shifter', or 'Returnee'<br>";
echo "2. Student logs in to dashboard<br>";
echo "3. Automatic crediting alert should appear<br>";

$conn->close();
?>