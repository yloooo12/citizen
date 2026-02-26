<?php
require_once 'send_email.php';

header('Content-Type: application/json');

$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "student_services";
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

$data = json_decode(file_get_contents('php://input'), true);

$email = $data['email'] ?? '';
$name = $data['name'] ?? '';
$id = $data['id'] ?? '';
$old_token = $data['token'] ?? '';

$success = false;
$message = '';

if ($email && $name && $id && $old_token) {
    $stmt = $conn->prepare("SELECT expires_at FROM student_invitations WHERE token=? AND used=0");
    $stmt->bind_param("s", $old_token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $expires_at = strtotime($row['expires_at']);
        $now = time();
        
        if ($expires_at > $now) {
            $names = explode(' ', $name, 2);
            $first_name = $names[0];
            $last_name = $names[1] ?? '';
            
            $success = sendRegistrationEmail($email, $first_name, $last_name, $id, $old_token);
            $message = $success ? 'Email sent' : 'Failed to send email';
        } else {
            $new_token = bin2hex(random_bytes(32));
            $new_expiry = date('Y-m-d H:i:s', strtotime('+7 days'));
            
            $update = $conn->prepare("UPDATE student_invitations SET token=?, expires_at=? WHERE token=? AND used=0");
            $update->bind_param("sss", $new_token, $new_expiry, $old_token);
            $update->execute();
            
            $names = explode(' ', $name, 2);
            $first_name = $names[0];
            $last_name = $names[1] ?? '';
            
            $success = sendRegistrationEmail($email, $first_name, $last_name, $id, $new_token);
            $message = $success ? 'Email sent' : 'Failed to send email';
        }
    } else {
        $message = 'Invalid token';
    }
    $stmt->close();
}

$conn->close();
echo json_encode(['success' => $success, 'message' => $message]);
