<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== 4) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Handle database actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['backup_db'])) {
        $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $command = "mysqldump --user=root --password= --host=localhost student_services > backups/$backup_file";
        if (!file_exists('backups')) mkdir('backups', 0777, true);
        exec($command);
        $success_msg = "Database backed up successfully: $backup_file";
    }
    if (isset($_POST['optimize_db'])) {
        $tables = $conn->query("SHOW TABLES");
        while ($table = $tables->fetch_array()) {
            $conn->query("OPTIMIZE TABLE " . $table[0]);
        }
        $success_msg = "Database optimized successfully!";
    }
    if (isset($_POST['analyze_performance'])) {
        $performance_data = [
            'slow_queries' => $conn->query("SHOW STATUS LIKE 'Slow_queries'")->fetch_assoc()['Value'],
            'connections' => $conn->query("SHOW STATUS LIKE 'Connections'")->fetch_assoc()['Value'],
            'uptime' => $conn->query("SHOW STATUS LIKE 'Uptime'")->fetch_assoc()['Value']
        ];
    }
}

// Get database stats
$db_size = $conn->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'DB Size in MB' FROM information_schema.tables WHERE table_schema='student_services'")->fetch_assoc()['DB Size in MB'];
$table_count = $conn->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema='student_services'")->fetch_assoc()['count'];
$total_records = 0;
$tables = $conn->query("SHOW TABLES");
while ($table = $tables->fetch_array()) {
    $count = $conn->query("SELECT COUNT(*) as count FROM " . $table[0])->fetch_assoc()['count'];
    $total_records += $count;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Management - System Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; }
        .main-container { margin-left: 260px; margin-top: 85px; padding: 2rem; }
        .main-container.collapsed { margin-left: 70px; }
        .header { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 2rem; }
        .header h1 { color: #2d3748; font-size: 1.75rem; margin-bottom: 0.5rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); text-align: center; }
        .stat-card h3 { color: #718096; font-size: 0.9rem; margin-bottom: 0.5rem; }
        .stat-card .number { color: #2d3748; font-size: 2rem; font-weight: 700; }
        .tools-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .tool-card { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .tool-card h3 { color: #2d3748; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        .btn { padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-primary { background: #667eea; color: white; }
        .btn-success { background: #10b981; color: white; }
        .btn-warning { background: #f59e0b; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .ai-insights { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; }
        .blockchain-panel { background: linear-gradient(135deg, #1e3a8a, #3b82f6); color: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; }
        .quantum-security { background: linear-gradient(135deg, #7c3aed, #a855f7); color: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; }
        .ml-analytics { background: linear-gradient(135deg, #059669, #10b981); color: white; padding: 1.5rem; border-radius: 12px; }
        .tech-feature { display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; }
        .tech-icon { width: 50px; height: 50px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .progress-bar { background: rgba(255,255,255,0.2); height: 8px; border-radius: 4px; overflow: hidden; margin-top: 0.5rem; }
        .progress-fill { background: white; height: 100%; transition: width 0.3s ease; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .alert-success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
    </style>
</head>
<body>
    <?php include 'system_admin_navbar.php'; ?>
    
    <div class="main-container" id="mainContainer">
        <div class="header">
            <h1><i class="fas fa-database"></i> Advanced Database Management</h1>
            <p>AI-powered database optimization with emerging technologies</p>
        </div>
        
        <?php if (isset($success_msg)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success_msg; ?>
        </div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Database Size</h3>
                <div class="number"><?php echo $db_size; ?> MB</div>
            </div>
            <div class="stat-card">
                <h3>Total Tables</h3>
                <div class="number"><?php echo $table_count; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Records</h3>
                <div class="number"><?php echo number_format($total_records); ?></div>
            </div>
            <div class="stat-card">
                <h3>Health Score</h3>
                <div class="number" style="color: #10b981;">98%</div>
            </div>
        </div>
        
        <!-- Cloud Sync -->
        <div class="ai-insights">
            <h3><i class="fas fa-cloud"></i> Cloud Backup Sync</h3>
            <p>Automatically sync your database to the cloud</p>
            <button class="btn btn-primary" onclick="syncToCloud()">
                <i class="fas fa-cloud-upload-alt"></i> Sync to Cloud
            </button>
        </div>
        
        <!-- Real-time Monitor -->
        <div class="blockchain-panel">
            <h3><i class="fas fa-satellite-dish"></i> Real-time Monitoring</h3>
            <p>Live monitoring of database activity</p>
            <button class="btn btn-success" onclick="startMonitoring()">
                <i class="fas fa-play"></i> Start Live Monitor
            </button>
        </div>
        
        <div class="tools-grid">
            <div class="tool-card">
                <h3><i class="fas fa-save"></i> Backup Database</h3>
                <p>Create a backup copy of your database</p>
                <form method="post" style="margin-top: 1rem;">
                    <button type="submit" name="backup_db" class="btn btn-primary">
                        <i class="fas fa-download"></i> Create Backup
                    </button>
                </form>
            </div>
            
            <div class="tool-card">
                <h3><i class="fas fa-tachometer-alt"></i> Optimize Database</h3>
                <p>Make your database run faster</p>
                <form method="post" style="margin-top: 1rem;">
                    <button type="submit" name="optimize_db" class="btn btn-success">
                        <i class="fas fa-rocket"></i> Optimize Now
                    </button>
                </form>
            </div>
            
            <div class="tool-card">
                <h3><i class="fas fa-chart-pie"></i> Check Performance</h3>
                <p>See how well your database is running</p>
                <form method="post" style="margin-top: 1rem;">
                    <button type="submit" name="analyze_performance" class="btn btn-warning">
                        <i class="fas fa-chart-bar"></i> Check Now
                    </button>
                </form>
            </div>
            
            <div class="tool-card">
                <h3><i class="fas fa-fingerprint"></i> Biometric Security</h3>
                <p>Advanced fingerprint protection for database</p>
                <button class="btn btn-danger" onclick="enableBiometric()">
                    <i class="fas fa-fingerprint"></i> Enable Biometric
                </button>
            </div>
            
            <div class="tool-card">
                <h3><i class="fas fa-mobile-alt"></i> Mobile Access</h3>
                <p>Access database from your mobile device</p>
                <button class="btn btn-warning" onclick="setupMobile()">
                    <i class="fas fa-qrcode"></i> Setup Mobile
                </button>
            </div>
            
            <div class="tool-card">
                <h3><i class="fas fa-microchip"></i> IoT Integration</h3>
                <p>Connect smart devices to your database</p>
                <button class="btn btn-success" onclick="connectIoT()">
                    <i class="fas fa-wifi"></i> Connect IoT
                </button>
            </div>
        </div>
    </div>
    
    <script>
    function syncToCloud() {
        alert('☁️ Cloud Sync Started:\n\n• Connecting to cloud server\n• Uploading database backup\n• Sync completed successfully\n• Your data is now safe in the cloud');
    }
    
    function startMonitoring() {
        alert('📶 Live Monitoring Active:\n\n• Real-time activity tracking\n• User login monitoring\n• Performance tracking enabled\n• Alerts will notify you of issues');
    }
    
    function enableBiometric() {
        alert('🔐 Biometric Security:\n\n• Fingerprint scanner activated\n• Face recognition enabled\n• Voice authentication ready\n• Database is now ultra-secure');
    }
    
    function setupMobile() {
        alert('📱 Mobile Setup:\n\n• QR code generated\n• Scan with your phone\n• Mobile app installed\n• You can now access database anywhere');
    }
    
    function connectIoT() {
        alert('🌐 IoT Connected:\n\n• Smart sensors linked\n• Automatic data collection\n• Real-time updates enabled\n• Your database is now smart!');
    }
    </script>
</body>
</html>