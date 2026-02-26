<?php
session_start();

$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "student_services";
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$token = $_GET['token'] ?? '';
$error = "";
$teacher_data = null;

// Verify token
if ($token) {
    $stmt = $conn->prepare("SELECT id_number, first_name, last_name, email FROM teacher_invitations WHERE token=? AND expires_at > NOW() AND used=0");
    if ($stmt) {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $teacher_data = $result->fetch_assoc();
        } else {
            $error = "Invalid or expired token";
        }
        $stmt->close();
    } else {
        $error = "Database error: " . $conn->error;
    }
}

// Handle registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && $teacher_data) {
    $contact = $_POST['contact_number'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (id_number, first_name, last_name, email, contact_number, password, status, user_type) VALUES (?, ?, ?, ?, ?, ?, 'approved', 'teacher')");
    if ($stmt) {
        $stmt->bind_param("ssssss", $teacher_data['id_number'], $teacher_data['first_name'], $teacher_data['last_name'], $teacher_data['email'], $contact, $password);
    
        if ($stmt->execute()) {
            // Mark invitation as used
            $update = $conn->prepare("UPDATE teacher_invitations SET used=1 WHERE token=?");
            if ($update) {
                $update->bind_param("s", $token);
                $update->execute();
                $update->close();
            }
            
            // Create notification for dean
            $notif_message = "New teacher registered: " . $teacher_data['first_name'] . " " . $teacher_data['last_name'] . " (" . $teacher_data['id_number'] . ")";
            $notif_message = mysqli_real_escape_string($conn, $notif_message);
            $conn->query("INSERT INTO dean_notifications (user_id, message, is_read, created_at) VALUES (NULL, '$notif_message', 0, NOW())");
            
            header("Location: login.php?registered=1");
            exit;
        } else {
            $error = "Registration failed: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "Database error: " . $conn->error;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Teacher Registration</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .container { background: white; padding: 2.5rem; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); max-width: 500px; width: 100%; }
        h1 { color: #2d3748; margin-bottom: 0.5rem; font-size: 1.75rem; }
        .subtitle { color: #718096; margin-bottom: 2rem; }
        .info-box { background: #f0f4ff; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .info-box p { color: #4a5568; font-size: 0.9rem; margin: 0.25rem 0; }
        .form-group { margin-bottom: 1.25rem; }
        label { display: block; font-weight: 600; color: #2d3748; margin-bottom: 0.5rem; font-size: 0.9rem; }
        input, select { width: 100%; padding: 0.875rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; }
        input:focus, select:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .btn { width: 100%; background: #667eea; color: white; padding: 1rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 1rem; }
        .btn:hover { background: #5568d3; }
        .btn:disabled { background: #a0aec0; cursor: not-allowed; }
        .error { background: #fee; color: #c00; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
        .modal-content { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 12px; text-align: center; min-width: 300px; }
        .spinner { border: 3px solid #f3f3f3; border-top: 3px solid #667eea; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 1rem; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .success-icon { color: #28a745; font-size: 3rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php elseif ($teacher_data): ?>
            <h1>Complete Your Teacher Registration</h1>
            <p class="subtitle">Fill in your teaching assignment details</p>
            
            <div class="info-box">
                <p><strong>Teacher ID:</strong> <?php echo $teacher_data['id_number']; ?></p>
                <p><strong>Name:</strong> <?php echo $teacher_data['first_name'] . ' ' . $teacher_data['last_name']; ?></p>
                <p><strong>Email:</strong> <?php echo $teacher_data['email']; ?></p>
            </div>
            
            <form method="post">
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" name="contact_number" placeholder="09XXXXXXXXX" maxlength="11" required>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Create your password" required>
                </div>
                
                <button type="submit" class="btn" id="submitBtn">Complete Registration</button>
            </form>
        <?php endif; ?>
    </div>
    
    <div id="loadingModal" class="modal">
        <div class="modal-content">
            <div class="spinner"></div>
            <p>Processing registration...</p>
        </div>
    </div>
    
    <div id="successModal" class="modal">
        <div class="modal-content">
            <div class="success-icon">✓</div>
            <h3>Registration Successful!</h3>
            <p>You can now login with your credentials.</p>
        </div>
    </div>
    
    <script>
        document.querySelector('form').addEventListener('submit', function() {
            document.getElementById('submitBtn').disabled = true;
            document.getElementById('loadingModal').style.display = 'block';
        });
        
        function showSuccess() {
            document.getElementById('loadingModal').style.display = 'none';
            document.getElementById('successModal').style.display = 'block';
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 2000);
        }
    </script>
</body>
</html>