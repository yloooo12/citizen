<?php
// Create System Admin account
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add missing columns if not exists
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active TINYINT DEFAULT 1");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_admin TINYINT DEFAULT 0");

// Create System Admin account
$admin_exists = $conn->query("SELECT id FROM users WHERE id_number = '000' LIMIT 1");
if ($admin_exists && $admin_exists->num_rows == 0) {
    $password = password_hash('sysadmin123', PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (first_name, last_name, email, id_number, password, user_type, is_admin, is_active, created_at) 
            VALUES ('System', 'Administrator', 'sysadmin@lspu.edu.ph', '000', '$password', 'admin', 4, 1, NOW())";
    
    if ($conn->query($sql)) {
        echo "✅ System Admin account created successfully!<br>";
        echo "📧 Email: sysadmin@lspu.edu.ph<br>";
        echo "🔑 Password: sysadmin123<br>";
        echo "🆔 ID: 000<br>";
    } else {
        echo "❌ Error creating System Admin: " . $conn->error;
    }
} else {
    echo "ℹ️ System Admin account already exists or check failed";
}

$conn->close();
?>
<br><br>
<a href="login.php" style="padding: 0.75rem 1.5rem; background: #667eea; color: white; text-decoration: none; border-radius: 8px;">Go to Login</a>