<?php
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check and add is_admin column
$check1 = $conn->query("SHOW COLUMNS FROM users LIKE 'is_admin'");
if ($check1->num_rows == 0) {
    $result1 = $conn->query("ALTER TABLE users ADD COLUMN is_admin TINYINT DEFAULT 0");
    echo $result1 ? "✅ Added is_admin column<br>" : "❌ Error adding is_admin<br>";
} else {
    echo "ℹ️ is_admin column already exists<br>";
}

// Check and add is_active column
$check2 = $conn->query("SHOW COLUMNS FROM users LIKE 'is_active'");
if ($check2->num_rows == 0) {
    $result2 = $conn->query("ALTER TABLE users ADD COLUMN is_active TINYINT DEFAULT 1");
    echo $result2 ? "✅ Added is_active column<br>" : "❌ Error adding is_active<br>";
} else {
    echo "ℹ️ is_active column already exists<br>";
}

// Insert System Admin
$password = password_hash('sysadmin123', PASSWORD_DEFAULT);
$result3 = $conn->query("INSERT INTO users (first_name, last_name, email, id_number, password, user_type, is_admin, is_active, created_at) VALUES ('System', 'Administrator', 'sysadmin@lspu.edu.ph', '000', '$password', 'admin', 4, 1, NOW())");
if ($result3) {
    echo "✅ System Admin created successfully!<br>";
    echo "📧 Email: sysadmin@lspu.edu.ph<br>";
    echo "🔑 Password: sysadmin123<br>";
    echo "🆔 ID: 000<br>";
} else {
    echo "❌ Error creating System Admin: " . $conn->error . "<br>";
}

$conn->close();
?>
<br><br>
<a href="login.php" style="padding: 0.75rem 1.5rem; background: #667eea; color: white; text-decoration: none; border-radius: 8px;">Go to Login</a>