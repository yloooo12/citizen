<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: login.php");
    exit;
}

$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "student_services";
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
require_once 'send_email.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_number = trim($_POST['id_number']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    
    if (!empty($id_number) && !empty($first_name) && !empty($last_name) && !empty($email)) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+7 days'));
        
        $check = $conn->prepare("SELECT id FROM teacher_invitations WHERE id_number=?");
        $check->bind_param("s", $id_number);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows == 0) {
            $stmt = $conn->prepare("INSERT INTO teacher_invitations (id_number, first_name, last_name, email, token, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $id_number, $first_name, $last_name, $email, $token, $expires);
            
            if ($stmt->execute()) {
                sendTeacherRegistrationEmail($email, $first_name, $last_name, $id_number, $token);
                $message = "Teacher invitation sent successfully!";
            } else {
                $message = "Error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $message = "Teacher with this ID already exists.";
        }
        $check->close();
    } else {
        $message = "All fields are required.";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Teacher - Dean Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; }
        .container { max-width: 500px; margin: 100px auto; background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2d3748; margin-bottom: 1.5rem; text-align: center; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; font-weight: 600; color: #2d3748; margin-bottom: 0.5rem; }
        input { width: 100%; padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 8px; }
        input:focus { border-color: #667eea; outline: none; }
        .btn { width: 100%; background: #667eea; color: white; padding: 0.875rem; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; margin-top: 1rem; }
        .btn:hover { background: #5568d3; }
        .message { padding: 1rem; background: #e6f4ea; color: #1e7e34; border-radius: 8px; margin-bottom: 1rem; text-align: center; }
        .back-link { text-align: center; margin-top: 1rem; }
        .back-link a { color: #667eea; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add Teacher</h1>
        
        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label>Teacher ID Number</label>
                <input type="text" name="id_number" required>
            </div>
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="first_name" required>
            </div>
            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="last_name" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <button type="submit" class="btn">Send Teacher Invitation</button>
        </form>
        
        <div class="back-link">
            <a href="dean_teachers.php">← Back to Teachers</a>
        </div>
    </div>
</body>
</html>