<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "student_services");
$student_id = $_SESSION['id_number'];

$emails = [];
$result = $conn->query("SELECT * FROM email_logs WHERE recipient_email = (SELECT email FROM users WHERE id_number='$student_id') ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $emails[] = $row;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Notifications</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; }
        .main-container { margin-left: 260px; margin-top: 85px; padding: 2rem; }
        .header { background: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .email-card { background: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .email-subject { font-size: 1.2rem; font-weight: 600; color: #2d3748; margin-bottom: 0.5rem; }
        .email-date { color: #718096; font-size: 0.9rem; margin-bottom: 1rem; }
        .email-message { color: #4a5568; white-space: pre-line; line-height: 1.6; }
        .btn-back { padding: 0.75rem 1.5rem; background: #667eea; color: white; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-block; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="main-container">
        <div class="header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h1><i class="fas fa-envelope"></i> Email Notifications</h1>
                <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </div>
        
        <?php if (empty($emails)): ?>
            <div class="email-card" style="text-align: center; color: #718096;">
                <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <p>No email notifications yet</p>
            </div>
        <?php else: ?>
            <?php foreach ($emails as $email): ?>
                <div class="email-card">
                    <div class="email-subject"><i class="fas fa-envelope-open"></i> <?php echo htmlspecialchars($email['subject']); ?></div>
                    <div class="email-date"><i class="fas fa-clock"></i> <?php echo date('F j, Y g:i A', strtotime($email['created_at'])); ?></div>
                    <div class="email-message"><?php echo htmlspecialchars($email['message']); ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>
