<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(['success' => false]);
    exit;
}

$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "student_services";
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

$student_id = $_SESSION['id_number'] ?? '';

$grades = [];
$result = $conn->query("SELECT teacher_id, column_name, grade FROM grades WHERE student_id='$student_id'");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $grades[$row['teacher_id']][$row['column_name']] = $row['grade'];
    }
}

$conn->close();
echo json_encode(['success' => true, 'grades' => $grades]);
?>
