<?php
$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "student_services";
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

$token = "56cc8bd32fe4c5eb021834dc024ba8b5d39c9b59913676e5688373c4e9dddda8";

echo "<h3>Debug Token: $token</h3>";

// Check if table exists
$result = $conn->query("SHOW TABLES LIKE 'teacher_invitations'");
if ($result->num_rows == 0) {
    echo "<p style='color:red'>Table 'teacher_invitations' does not exist!</p>";
    echo "<p>Run the SQL file: create_teacher_invitations_table.sql</p>";
} else {
    echo "<p style='color:green'>Table 'teacher_invitations' exists</p>";
    
    // Check all tokens
    $result = $conn->query("SELECT * FROM teacher_invitations");
    echo "<h4>All teacher invitations:</h4>";
    while ($row = $result->fetch_assoc()) {
        echo "<p>ID: {$row['id']}, Token: {$row['token']}, Expires: {$row['expires_at']}, Used: {$row['used']}</p>";
    }
    
    // Check specific token
    $stmt = $conn->prepare("SELECT * FROM teacher_invitations WHERE token=?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        echo "<p style='color:red'>Token not found in database</p>";
    } else {
        $data = $result->fetch_assoc();
        echo "<h4>Token found:</h4>";
        echo "<p>Name: {$data['first_name']} {$data['last_name']}</p>";
        echo "<p>Email: {$data['email']}</p>";
        echo "<p>Expires: {$data['expires_at']}</p>";
        echo "<p>Used: {$data['used']}</p>";
        echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";
        
        if ($data['expires_at'] < date('Y-m-d H:i:s')) {
            echo "<p style='color:red'>Token expired!</p>";
        }
        if ($data['used'] == 1) {
            echo "<p style='color:red'>Token already used!</p>";
        }
    }
}

$conn->close();
?>