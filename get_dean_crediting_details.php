<?php
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

$id = $_GET['id'] ?? 0;

$stmt = $conn->prepare("SELECT * FROM dean_crediting WHERE id = ?");
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