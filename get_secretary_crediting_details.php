<?php
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

$id = $_GET['id'] ?? 0;

// Get secretary crediting details with original request data
$query = "SELECT sc.*, phc.student_type, phc.subjects_to_credit, phc.transcript_info 
          FROM secretary_crediting sc 
          LEFT JOIN program_head_crediting phc ON sc.request_id = phc.id 
          WHERE sc.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    echo json_encode($data);
} else {
    echo json_encode(['error' => 'Request not found']);
}

$stmt->close();
$conn->close();
?>