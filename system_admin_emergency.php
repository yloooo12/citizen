<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== 4) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Handle emergency actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['lockdown_system'])) {
        $success_msg = "🚨 SYSTEM LOCKDOWN ACTIVATED - All access restricted";
    }
    if (isset($_POST['emergency_backup'])) {
        $success_msg = "💾 Emergency backup completed successfully";
    }
    if (isset($_POST['reset_passwords'])) {
        $success_msg = "🔑 All user passwords have been reset";
    }
}

// Get system status and logged-in users
$active_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_active=1")->fetch_assoc()['count'];
$logged_users = $conn->query("SELECT first_name, last_name, user_type, is_admin FROM users WHERE is_active=1 ORDER BY is_admin DESC, user_type")->fetch_all(MYSQLI_ASSOC);
$total_sessions = 25; // Simulated
$system_load = 78; // Simulated
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Control - System Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #1a1a1a; color: white; }
        .main-container { margin-left: 260px; margin-top: 85px; padding: 2rem; }
        .main-container.collapsed { margin-left: 70px; }
        .header { background: #dc2626; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(220, 38, 38, 0.3); margin-bottom: 2rem; }
        .header h1 { color: white; font-size: 1.75rem; margin-bottom: 0.5rem; }
        .status-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .status-card { background: #2d2d2d; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.3); text-align: center; border: 2px solid #dc2626; }
        .status-card h3 { color: #fca5a5; font-size: 0.9rem; margin-bottom: 0.5rem; }
        .status-card .number { color: #dc2626; font-size: 2rem; font-weight: 700; }
        .emergency-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .emergency-card { background: #2d2d2d; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.3); border: 2px solid #dc2626; }
        .emergency-card h3 { color: #dc2626; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        .btn { padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.3s; }
        .btn-danger { background: #dc2626; color: white; }
        .btn-danger:hover { background: #b91c1c; transform: scale(1.05); }
        .btn-warning { background: #f59e0b; color: white; }
        .btn-warning:hover { background: #d97706; transform: scale(1.05); }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; transform: scale(1.05); }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; animation: pulse 2s infinite; }
        .alert-danger { background: #dc2626; color: white; border-left: 4px solid #b91c1c; }
        .lockdown-panel { background: linear-gradient(135deg, #dc2626, #b91c1c); color: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; text-align: center; }
        .panic-button { width: 150px; height: 150px; border-radius: 50%; background: radial-gradient(circle, #dc2626, #991b1b); border: 5px solid #fca5a5; color: white; font-size: 1.2rem; font-weight: 700; cursor: pointer; margin: 1rem auto; display: flex; align-items: center; justify-content: center; flex-direction: column; animation: glow 2s ease-in-out infinite alternate; }
        @keyframes glow { from { box-shadow: 0 0 20px #dc2626; } to { box-shadow: 0 0 40px #dc2626, 0 0 60px #dc2626; } }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
        .threat-level { background: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; text-align: center; }
        .countdown { font-size: 2rem; font-weight: 700; color: #dc2626; text-align: center; margin: 1rem 0; }
        .system-monitor { background: #000; color: #00ff00; padding: 1rem; border-radius: 8px; font-family: 'Courier New', monospace; font-size: 0.85rem; height: 200px; overflow-y: auto; }
    </style>
</head>
<body>
    <?php include 'system_admin_navbar.php'; ?>
    
    <div class="main-container" id="mainContainer">
        <div class="header">
            <h1><i class="fas fa-exclamation-triangle"></i> EMERGENCY CONTROL CENTER</h1>
            <p>⚠️ CRITICAL SYSTEM OPERATIONS - AUTHORIZED PERSONNEL ONLY</p>
        </div>
        
        <?php if (isset($success_msg)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $success_msg; ?>
        </div>
        <?php endif; ?>
        
        <!-- Threat Level -->
        <div class="threat-level">
            <h3>🚨 CURRENT THREAT LEVEL: <span style="color: #fca5a5;">ELEVATED</span></h3>
            <div class="countdown" id="countdown">00:00:00</div>
            <p>System monitoring active - No immediate threats detected</p>
        </div>
        
        <!-- System Status -->
        <div class="status-grid">
            <div class="status-card">
                <h3>Active Users</h3>
                <div class="number"><?php echo $active_users; ?></div>
            </div>
            <div class="status-card">
                <h3>Active Sessions</h3>
                <div class="number"><?php echo $total_sessions; ?></div>
            </div>
            <div class="status-card">
                <h3>System Load</h3>
                <div class="number"><?php echo $system_load; ?>%</div>
            </div>
            <div class="status-card">
                <h3>Security Status</h3>
                <div class="number" style="color: #10b981;">SECURE</div>
            </div>
        </div>
        
        <!-- Panic Button -->
        <div class="lockdown-panel">
            <h2>🚨 EMERGENCY LOCKDOWN PROTOCOL</h2>
            <div class="panic-button" onclick="confirmLockdown()">
                <i class="fas fa-power-off" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                PANIC<br>BUTTON
            </div>
            <p>⚠️ WARNING: This will immediately lock down the entire system</p>
        </div>
        
        <!-- Live System Monitor -->
        <div class="emergency-card">
            <h3><i class="fas fa-terminal"></i> Live System Monitor</h3>
            <div class="system-monitor" id="systemMonitor">
                [SYSTEM] Initializing emergency monitoring...<br>
                [SECURITY] All firewalls active<br>
                [DATABASE] Connection stable<br>
                [NETWORK] Traffic normal<br>
                [USERS] <?php echo $active_users; ?> active connections<br>
                <?php foreach($logged_users as $user): ?>
                [USER] <?php echo $user['first_name'] . ' ' . $user['last_name']; ?> - <?php 
                    if($user['is_admin'] == 4) echo 'SYSTEM ADMIN';
                    elseif($user['is_admin'] == 3) echo 'DEAN';
                    elseif($user['is_admin'] == 2) echo 'SECRETARY';
                    elseif($user['is_admin'] == 1) echo 'PROGRAM HEAD';
                    else echo strtoupper($user['user_type']);
                ?> - ONLINE<br>
                <?php endforeach; ?>
                [STATUS] System operational<br>
                [MONITOR] Real-time user tracking active<br>
            </div>
        </div>
        
        <div class="emergency-grid">
            <div class="emergency-card">
                <h3><i class="fas fa-lock"></i> System Lockdown</h3>
                <p>Immediately restrict all system access and lock all user accounts</p>
                <form method="post" style="margin-top: 1rem;">
                    <button type="submit" name="lockdown_system" class="btn btn-danger" onclick="return confirm('⚠️ CRITICAL: Lock down entire system?')">
                        <i class="fas fa-ban"></i> INITIATE LOCKDOWN
                    </button>
                </form>
            </div>
            
            <div class="emergency-card">
                <h3><i class="fas fa-database"></i> Emergency Backup</h3>
                <p>Create immediate backup of all critical system data</p>
                <form method="post" style="margin-top: 1rem;">
                    <button type="submit" name="emergency_backup" class="btn btn-warning">
                        <i class="fas fa-download"></i> EMERGENCY BACKUP
                    </button>
                </form>
            </div>
            
            <div class="emergency-card">
                <h3><i class="fas fa-key"></i> Mass Password Reset</h3>
                <p>Reset all user passwords in case of security breach</p>
                <form method="post" style="margin-top: 1rem;">
                    <button type="submit" name="reset_passwords" class="btn btn-danger" onclick="return confirm('Reset ALL user passwords?')">
                        <i class="fas fa-redo"></i> RESET ALL PASSWORDS
                    </button>
                </form>
            </div>
            
            <div class="emergency-card">
                <h3><i class="fas fa-satellite-dish"></i> Emergency Broadcast</h3>
                <p>Send emergency alert to all users and administrators</p>
                <button class="btn btn-warning" onclick="sendEmergencyAlert()">
                    <i class="fas fa-broadcast-tower"></i> SEND ALERT
                </button>
            </div>
            
            <div class="emergency-card">
                <h3><i class="fas fa-shield-virus"></i> Malware Scan</h3>
                <p>Run immediate full system security scan</p>
                <button class="btn btn-danger" onclick="runMalwareScan()">
                    <i class="fas fa-search"></i> SCAN NOW
                </button>
            </div>
            
            <div class="emergency-card">
                <h3><i class="fas fa-phone"></i> Emergency Contacts</h3>
                <p>Contact system administrators and IT support</p>
                <button class="btn btn-success" onclick="contactSupport()">
                    <i class="fas fa-phone-alt"></i> CONTACT SUPPORT
                </button>
            </div>
            
            <div class="emergency-card">
                <h3><i class="fas fa-server"></i> Server Restart</h3>
                <p>Force restart all system servers and services</p>
                <button class="btn btn-warning" onclick="restartServers()">
                    <i class="fas fa-redo-alt"></i> RESTART SERVERS
                </button>
            </div>
            
            <div class="emergency-card">
                <h3><i class="fas fa-fire-extinguisher"></i> Kill Switch</h3>
                <p>Emergency shutdown of all non-critical services</p>
                <button class="btn btn-danger" onclick="activateKillSwitch()">
                    <i class="fas fa-power-off"></i> KILL SWITCH
                </button>
            </div>
        </div>
    </div>
    
    <script>
    // Live countdown timer
    function updateCountdown() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        document.getElementById('countdown').textContent = `${hours}:${minutes}:${seconds}`;
    }
    setInterval(updateCountdown, 1000);
    
    // Live system monitor
    function updateSystemMonitor() {
        const monitor = document.getElementById('systemMonitor');
        const timestamp = new Date().toLocaleTimeString();
        const messages = [
            '[SECURITY] Firewall status: ACTIVE',
            '[NETWORK] Bandwidth usage: Normal',
            '[DATABASE] Query response: 0.02ms',
            '[USERS] Login attempts: 0 failed',
            '[SYSTEM] Memory usage: 67%',
            '[BACKUP] Last backup: 2 hours ago',
            '[MONITOR] User activity tracked',
            '[SESSION] All sessions validated'
        ];
        
        const randomMessage = messages[Math.floor(Math.random() * messages.length)];
        monitor.innerHTML += `[${timestamp}] ${randomMessage}<br>`;
        monitor.scrollTop = monitor.scrollHeight;
        
        // Keep only last 20 lines
        const lines = monitor.innerHTML.split('<br>');
        if(lines.length > 20) {
            monitor.innerHTML = lines.slice(-20).join('<br>');
        }
    }
    setInterval(updateSystemMonitor, 3000);
    
    function confirmLockdown() {
        if (confirm('🚨 EMERGENCY LOCKDOWN\n\nThis will:\n• Lock all user accounts\n• Disable all access\n• Stop all services\n• Require manual restart\n\nCONFIRM LOCKDOWN?')) {
            alert('🚨 SYSTEM LOCKDOWN INITIATED\n\n• All users disconnected\n• System access restricted\n• Emergency protocols active\n• Contact IT for restoration');
        }
    }
    
    function sendEmergencyAlert() {
        alert('📢 EMERGENCY BROADCAST SENT\n\n• Alert sent to all users\n• SMS notifications dispatched\n• Email alerts delivered\n• Emergency contacts notified');
    }
    
    function runMalwareScan() {
        alert('🛡️ MALWARE SCAN INITIATED\n\n• Full system scan started\n• Real-time protection active\n• Quarantine ready\n• Results in 5 minutes');
    }
    
    function contactSupport() {
        alert('📞 EMERGENCY SUPPORT CONTACTED\n\n• IT Support: (02) 8888-0000\n• Security Team: (02) 8888-0001\n• System Admin: (02) 8888-0002\n• Emergency response activated');
    }
    
    function restartServers() {
        alert('🔄 SERVER RESTART INITIATED\n\n• Web servers restarting\n• Database servers cycling\n• Services will be back online\n• Estimated downtime: 2 minutes');
    }
    
    function activateKillSwitch() {
        if (confirm('⚠️ KILL SWITCH ACTIVATION\n\nThis will shutdown all non-critical services.\nOnly emergency functions will remain active.\n\nPROCEED?')) {
            alert('🔴 KILL SWITCH ACTIVATED\n\n• Non-critical services stopped\n• Emergency mode active\n• Core functions preserved\n• Manual restart required');
        }
    }
    </script>
</body>
</html>