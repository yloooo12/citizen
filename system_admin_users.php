<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== 4) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_user'])) {
        $user_id = intval($_POST['user_id']);
        $conn->query("DELETE FROM users WHERE id = $user_id");
        header("Location: system_admin_users.php");
        exit;
    }
    if (isset($_POST['toggle_status'])) {
        $user_id = intval($_POST['user_id']);
        $conn->query("UPDATE users SET is_active = NOT is_active WHERE id = $user_id");
        header("Location: system_admin_users.php");
        exit;
    }
    if (isset($_POST['reset_password'])) {
        $user_id = intval($_POST['user_id']);
        $new_password = password_hash('123456', PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password = '$new_password' WHERE id = $user_id");
        header("Location: system_admin_users.php");
        exit;
    }
}

// Get all users
$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - System Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; }
        .main-container { margin-left: 260px; margin-top: 85px; padding: 2rem; }
        .main-container.collapsed { margin-left: 70px; }
        .header { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 2rem; }
        .header h1 { color: #2d3748; font-size: 1.75rem; margin-bottom: 0.5rem; }
        .users-table { background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 1rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .table th { background: #f7fafc; font-weight: 600; color: #2d3748; }
        .btn { padding: 0.5rem 1rem; border-radius: 6px; font-weight: 500; cursor: pointer; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.85rem; }
        .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-warning { background: #f59e0b; color: white; }
        .btn-success { background: #10b981; color: white; }
        .btn-secondary { background: #6b7280; color: white; }
        .status-badge { padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-inactive { background: #fee2e2; color: #991b1b; }
        .user-type { padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
        .type-student { background: #dbeafe; color: #1e40af; }
        .type-teacher { background: #fef3c7; color: #92400e; }
        .type-admin { background: #fecaca; color: #991b1b; }
    </style>
</head>
<body>
    <?php include 'system_admin_navbar.php'; ?>
    
    <div class="main-container" id="mainContainer">
        <div class="header">
            <h1><i class="fas fa-users"></i> User Management</h1>
            <p>Manage all users in the system</p>
        </div>
        
        <div class="users-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>ID Number</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['id_number']); ?></td>
                        <td>
                            <?php 
                            $admin_level = $user['is_admin'];
                            $user_type = $user['user_type'];
                            
                            if ($admin_level == 4) echo '<span class="user-type type-admin">System Admin</span>';
                            elseif ($admin_level == 3 || $user_type == 'dean') echo '<span class="user-type type-admin">Dean</span>';
                            elseif ($admin_level == 2 || $user_type == 'secretary') echo '<span class="user-type type-admin">Secretary</span>';
                            elseif ($admin_level == 1 || $user_type == 'program_head') echo '<span class="user-type type-admin">Program Head</span>';
                            elseif ($user_type == 'teacher') echo '<span class="user-type type-teacher">Teacher</span>';
                            else echo '<span class="user-type type-student">Student</span>';
                            ?>
                        </td>
                        <td>
                            <span class="status-badge <?php echo $user['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="toggle_status" class="btn btn-sm <?php echo $user['is_active'] ? 'btn-warning' : 'btn-success'; ?>">
                                    <i class="fas <?php echo $user['is_active'] ? 'fa-pause' : 'fa-play'; ?>"></i>
                                    <?php echo $user['is_active'] ? 'Disable' : 'Enable'; ?>
                                </button>
                            </form>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="reset_password" class="btn btn-sm btn-secondary" onclick="return confirm('Reset password to 123456?')">
                                    <i class="fas fa-key"></i> Reset
                                </button>
                            </form>
                            <?php if ($user['is_admin'] != 4): ?>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="delete_user" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>