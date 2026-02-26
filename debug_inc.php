<?php
session_start();
$conn = new mysqli("localhost", "root", "", "student_services");

echo "<h3>Debug INC Requests</h3>";

// Check if teacher is logged in
$is_teacher = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'teacher';
$teacher_name = $is_teacher ? $_SESSION['last_name'] . ', ' . $_SESSION['first_name'] : '';

echo "Is Teacher: " . ($is_teacher ? 'Yes' : 'No') . "<br>";
echo "Teacher Name: " . $teacher_name . "<br><br>";

// Show all inc_requests
echo "<h4>All INC Requests:</h4>";
$result = $conn->query("SELECT * FROM inc_requests ORDER BY id DESC LIMIT 10");
if ($result && $result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Student Name</th><th>Student ID</th><th>Professor</th><th>Subject</th><th>Date</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['student_name'] . "</td>";
        echo "<td>" . $row['student_id'] . "</td>";
        echo "<td>" . $row['professor'] . "</td>";
        echo "<td>" . $row['subject'] . "</td>";
        echo "<td>" . $row['date_submitted'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No INC requests found in database.";
}

// Show filtered results for teacher
if ($is_teacher) {
    echo "<h4>Filtered for Teacher '$teacher_name':</h4>";
    $stmt = $conn->prepare("SELECT * FROM inc_requests WHERE (professor LIKE ? OR professor = ?) ORDER BY id DESC");
    $like_param = "%$teacher_name%";
    $stmt->bind_param("ss", $like_param, $teacher_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Student Name</th><th>Professor</th><th>Subject</th></tr>";
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['student_name'] . "</td>";
            echo "<td>" . $row['professor'] . "</td>";
            echo "<td>" . $row['subject'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No requests found for this teacher.";
    }
}

$conn->close();
?>
<br><a href="admin_inc.php">Back to INC Requests</a>