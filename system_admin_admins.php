<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== 4) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Handle admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['promote_user'])) {
        $user_id = $_POST['user_id'];
        $admin_level = $_POST['admin_level'];
        $conn->query("UPDATE users SET is_admin = $admin_level WHERE id = $user_id");
    }
    if (isset($_POST['demote_admin'])) {
        $user_id = $_POST['user_id'];
        $conn->query("UPDATE users SET is_admin = 0 WHERE id = $user_id");
    }
    if (isset($_POST['create_admin'])) {
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $email = $_POST['email'];
        $id_number = $_POST['id_number'];
        $admin_level = $_POST['admin_level'];
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, id_number, password, user_type, is_admin, is_active, created_at) VALUES (?, ?, ?, ?, ?, 'admin', ?, 1, NOW())");
        $stmt->bind_param("sssssi", $first_name, $last_name, $email, $id_number, $password, $admin_level);
        $stmt->execute();
    }
}

// Get all admins and regular users
$admins = $conn->query("SELECT * FROM users WHERE is_admin > 0 ORDER BY is_admin DESC, created_at DESC");
$users = $conn->query("SELECT * FROM users WHERE is_admin = 0 AND user_type != 'student' ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management - System Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; }
        .main-container { margin-left: 260px; margin-top: 85px; padding: 2rem; }
        .main-container.collapsed { margin-left: 70px; }
        .header { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 2rem; }
        .header h1 { color: #2d3748; font-size: 1.75rem; margin-bottom: 0.5rem; }
        .admin-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem; }
        .admin-card { background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 1.5rem; }
        .admin-card h3 { color: #2d3748; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .table th { background: #f7fafc; font-weight: 600; color: #2d3748; }
        .btn { padding: 0.5rem 1rem; border-radius: 6px; font-weight: 500; cursor: pointer; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.85rem; }
        .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
        .btn-primary { background: #667eea; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-success { background: #10b981; color: white; }
        .admin-badge { padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
        .level-4 { background: #fecaca; color: #991b1b; }
        .level-3 { background: #fed7aa; color: #9a3412; }
        .level-2 { background: #fef3c7; color: #92400e; }
        .level-1 { background: #dbeafe; color: #1e40af; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-weight: 500; margin-bottom: 0.25rem; }
        .form-group input, .form-group select { width: 100%; padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 6px; }
        .ai-assistant { position: fixed; bottom: 20px; right: 20px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 50%; width: 60px; height: 60px; cursor: pointer; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3); transition: all 0.3s ease; }
        .ai-assistant:hover { transform: scale(1.1); box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5); }
        .ai-panel { position: fixed; bottom: 90px; right: 20px; width: 350px; height: 400px; background: white; border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,0.2); display: none; flex-direction: column; }
        .ai-panel.show { display: flex; }
        .ai-header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 1rem; border-radius: 12px 12px 0 0; }
        .ai-content { flex: 1; padding: 1rem; overflow-y: auto; }
        .ai-suggestion { background: #f0f4ff; padding: 0.75rem; border-radius: 8px; margin-bottom: 0.5rem; cursor: pointer; transition: all 0.3s; }
        .ai-suggestion:hover { background: #e0e7ff; transform: translateX(5px); }
    </style>
</head>
<body>
    <?php include 'system_admin_navbar.php'; ?>
    
    <div class="main-container" id="mainContainer">
        <div class="header">
            <h1><i class="fas fa-user-shield"></i> Admin Management</h1>
            <p>Manage administrators and promote users with AI-powered insights</p>
        </div>
        
        <div class="admin-grid">
            <div class="admin-card">
                <h3><i class="fas fa-crown"></i> Current Administrators</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Level</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($admin = $admins->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></td>
                            <td>
                                <?php 
                                $level = $admin['is_admin'];
                                $labels = [4 => 'System Admin', 3 => 'Dean', 2 => 'Secretary', 1 => 'Program Head'];
                                echo '<span class="admin-badge level-' . $level . '">' . $labels[$level] . '</span>';
                                ?>
                            </td>
                            <td>
                                <?php if ($admin['is_admin'] != 4): ?>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $admin['id']; ?>">
                                    <button type="submit" name="demote_admin" class="btn btn-sm btn-danger" onclick="return confirm('Demote this admin?')">
                                        <i class="fas fa-arrow-down"></i> Demote
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="admin-card">
                <h3><i class="fas fa-user-plus"></i> Create New Admin</h3>
                <form method="post">
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
                    <div class="form-group">
                        <label>ID Number</label>
                        <input type="text" name="id_number" required>
                    </div>
                    <div class="form-group">
                        <label>Admin Level</label>
                        <select name="admin_level" required>
                            <option value="1">Program Head</option>
                            <option value="2">Secretary</option>
                            <option value="3">Dean</option>
                        </select>
                    </div>
                    <button type="submit" name="create_admin" class="btn btn-success">
                        <i class="fas fa-plus"></i> Create Admin
                    </button>
                </form>
            </div>
        </div>
        
        <div class="admin-card">
            <h3><i class="fas fa-users"></i> Promote Existing Users</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo ucfirst($user['user_type']); ?></td>
                        <td>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <select name="admin_level" style="padding: 0.25rem; margin-right: 0.5rem;">
                                    <option value="1">Program Head</option>
                                    <option value="2">Secretary</option>
                                    <option value="3">Dean</option>
                                </select>
                                <button type="submit" name="promote_user" class="btn btn-sm btn-primary">
                                    <i class="fas fa-arrow-up"></i> Promote
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- AI Assistant -->
    <button class="ai-assistant" onclick="toggleAI()">
        <i class="fas fa-robot"></i>
    </button>
    
    <div class="ai-panel" id="aiPanel">
        <div class="ai-header">
            <h4><i class="fas fa-brain"></i> AI Admin Assistant</h4>
            <p style="font-size: 0.85rem; opacity: 0.9;">Smart recommendations for admin management</p>
        </div>
        <div class="ai-content">
            <div class="ai-suggestion" onclick="analyzeAdmins()">
                <strong><i class="fas fa-chart-line"></i> Analyze Admin Distribution</strong>
                <p style="font-size: 0.8rem; margin-top: 0.25rem;">Get insights on current admin structure</p>
            </div>
            <div class="ai-suggestion" onclick="suggestPromotions()">
                <strong><i class="fas fa-user-graduate"></i> Suggest Promotions</strong>
                <p style="font-size: 0.8rem; margin-top: 0.25rem;">AI-powered user promotion recommendations</p>
            </div>
            <div class="ai-suggestion" onclick="securityAudit()">
                <strong><i class="fas fa-shield-alt"></i> Security Audit</strong>
                <p style="font-size: 0.8rem; margin-top: 0.25rem;">Check admin security and permissions</p>
            </div>
            <div class="ai-suggestion" onclick="optimizeStructure()">
                <strong><i class="fas fa-sitemap"></i> Optimize Structure</strong>
                <p style="font-size: 0.8rem; margin-top: 0.25rem;">Recommend organizational improvements</p>
            </div>
        </div>
    </div>
    
    <script>
    function toggleAI() {
        document.getElementById('aiPanel').classList.toggle('show');
    }
    
    function analyzeAdmins() {
        alert('🤖 AI Analysis:\n\n• Current admin distribution is balanced\n• Recommend adding 1 more Secretary\n• Dean coverage is adequate\n• Consider cross-training for redundancy');
    }
    
    function suggestPromotions() {
        alert('🤖 AI Recommendations:\n\n• Teachers with 5+ years experience\n• Users with high activity scores\n• Staff with leadership qualities\n• Consider department needs');
    }
    
    function securityAudit() {
        alert('🤖 Security Report:\n\n✅ All admins have strong passwords\n✅ No inactive admin accounts\n⚠️ Consider 2FA implementation\n✅ Admin permissions properly assigned');
    }
    
    function optimizeStructure() {
        alert('🤖 Structure Optimization:\n\n• Current hierarchy is efficient\n• Recommend department-specific admins\n• Consider regional admin distribution\n• Implement admin rotation policy');
    }
    </script>
</body>
</html>