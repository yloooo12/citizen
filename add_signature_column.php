<?php
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

$sql = "ALTER TABLE program_head_crediting ADD COLUMN signature_file VARCHAR(255) NULL AFTER evaluation_remarks";

if ($conn->query($sql)) {
    echo "Signature column added successfully!";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>