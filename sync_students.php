<?php
$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "student_services";
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

// Find students in student_subjects but not in users
$missing = $conn->query("SELECT DISTINCT ss.student_id 
                         FROM student_subjects ss
                         LEFT JOIN users u ON ss.student_id = u.id_number
                         WHERE u.id_number IS NULL");

$inserted = 0;
if ($missing && $missing->num_rows > 0) {
    while ($row = $missing->fetch_assoc()) {
        $id = $row['student_id'];
        $email = $id . '@example.com';
        $password = md5('123');
        
        $conn->query("INSERT INTO users (id_number, first_name, last_name, email, password) 
                      VALUES ('$id', 'Student', '$id', '$email', '$password')");
        $inserted++;
    }
}

echo "Synced $inserted students from student_subjects to users table.<br>";
echo "<a href='teacher_grades.php'>Go back to Grades</a>";

$conn->close();
?>
