<?php
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

$id = $_GET['id'] ?? 0;
$result = $conn->query("SELECT * FROM program_head_crediting WHERE id='$id'");

if ($result && $row = $result->fetch_assoc()) {
    echo json_encode([
        'student_name' => $row['student_name'],
        'student_id' => $row['student_id'],
        'student_type' => $row['student_type'],
        'subject_code' => $row['subjects_to_credit'],
        'school_taken' => $row['transcript_info'],
        'transcript_file' => $row['transcript_file']
    ]);
} else {
    echo json_encode(['error' => 'Request not found']);
}

$conn->close();
?>