<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

$token = $_GET['token'] ?? '';
$student = null;

if ($token) {
    $stmt = $conn->prepare("SELECT id_number, first_name, last_name, email, token FROM student_invitations WHERE token=? AND used=0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Registration Link</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; padding: 2rem; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #2d3748; margin-bottom: 1rem; }
        .link-box { background: #f0f4ff; padding: 1rem; border-radius: 8px; margin: 1rem 0; word-break: break-all; }
        .link-box a { color: #667eea; font-weight: 600; }
        .info { color: #718096; margin: 0.5rem 0; }
        .copy-btn { background: #667eea; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; margin-top: 1rem; }
        .copy-btn:hover { background: #5568d3; }
        .success { background: #d1fae5; color: #065f46; padding: 0.75rem; border-radius: 8px; margin-top: 1rem; display: none; }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($student): ?>
            <h2>Registration Link</h2>
            <div class="info"><strong>Name:</strong> <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
            <div class="info"><strong>ID:</strong> <?php echo htmlspecialchars($student['id_number']); ?></div>
            <div class="info"><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></div>
            
            <div class="link-box">
                <a href="http://localhost/citizenz/student_register.php?token=<?php echo $student['token']; ?>" target="_blank" id="regLink">
                    http://localhost/citizenz/student_register.php?token=<?php echo $student['token']; ?>
                </a>
            </div>
            
            <button class="copy-btn" onclick="copyLink()">Copy Link</button>
            <div class="success" id="successMsg">Link copied! Send this to the student.</div>
            
            <div style="margin-top: 1.5rem; padding: 1rem; background: #fef3c7; border-radius: 8px; color: #92400e;">
                <strong>Note:</strong> Copy this link and send it to the student via email, messenger, or any messaging app.
            </div>
        <?php else: ?>
            <h2>Invalid Token</h2>
            <p>The registration link is invalid or has already been used.</p>
        <?php endif; ?>
        
        <div style="margin-top: 1.5rem;">
            <a href="dean_registered.php" style="color: #667eea; text-decoration: none;">← Back to Students</a>
        </div>
    </div>
    
    <script>
        function copyLink() {
            const link = document.getElementById('regLink').href;
            navigator.clipboard.writeText(link).then(() => {
                document.getElementById('successMsg').style.display = 'block';
                setTimeout(() => {
                    document.getElementById('successMsg').style.display = 'none';
                }, 3000);
            });
        }
    </script>
</body>
</html>
