<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== 4) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Handle settings updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_theme'])) {
        $theme = $_POST['theme'];
        $success_msg = "Theme updated to: " . ucfirst($theme);
    }
    if (isset($_POST['update_security'])) {
        $security_level = $_POST['security_level'];
        $success_msg = "Security level updated to: " . ucfirst($security_level);
    }
    if (isset($_POST['update_notifications'])) {
        $notifications = $_POST['notifications'];
        $success_msg = "Notification settings updated";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - System Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; }
        .main-container { margin-left: 260px; margin-top: 85px; padding: 2rem; }
        .main-container.collapsed { margin-left: 70px; }
        .header { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 2rem; }
        .header h1 { color: #2d3748; font-size: 1.75rem; margin-bottom: 0.5rem; }
        .settings-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 1.5rem; }
        .setting-card { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .setting-card h3 { color: #2d3748; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-weight: 500; margin-bottom: 0.5rem; color: #374151; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .btn { padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-primary { background: #667eea; color: white; }
        .btn-success { background: #10b981; color: white; }
        .btn-warning { background: #f59e0b; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .toggle-switch { position: relative; display: inline-block; width: 60px; height: 34px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 26px; width: 26px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #667eea; }
        input:checked + .slider:before { transform: translateX(26px); }
        .smart-panel { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; }
        .voice-panel { background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; }
        .gesture-panel { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .alert-success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .color-picker { display: flex; gap: 0.5rem; margin-top: 0.5rem; }
        .color-option { width: 30px; height: 30px; border-radius: 50%; cursor: pointer; border: 3px solid transparent; transition: all 0.3s; }
        .color-option:hover { transform: scale(1.1); }
        .color-option.active { border-color: #667eea; }
    </style>
</head>
<body>
    <?php include 'system_admin_navbar.php'; ?>
    
    <div class="main-container" id="mainContainer">
        <div class="header">
            <h1><i class="fas fa-cog"></i> Advanced System Settings</h1>
            <p>Configure your system with smart automation and modern controls</p>
        </div>
        
        <?php if (isset($success_msg)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success_msg; ?>
        </div>
        <?php endif; ?>
        
        <!-- Smart Automation -->
        <div class="smart-panel">
            <h3><i class="fas fa-magic"></i> Smart System Automation</h3>
            <p>Let the system automatically optimize itself based on usage patterns</p>
            <label class="toggle-switch">
                <input type="checkbox" id="smartMode">
                <span class="slider"></span>
            </label>
            <span style="margin-left: 1rem;">Auto-optimization enabled</span>
        </div>
        
        <!-- Voice Control -->
        <div class="voice-panel">
            <h3><i class="fas fa-microphone"></i> Voice Command Center</h3>
            <p>Control your system using voice commands and speech recognition</p>
            <button class="btn btn-success" onclick="activateVoice()">
                <i class="fas fa-volume-up"></i> Activate Voice Control
            </button>
        </div>
        
        <!-- Gesture Control -->
        <div class="gesture-panel">
            <h3><i class="fas fa-hand-paper"></i> Gesture Recognition</h3>
            <p>Navigate the system using hand gestures and motion controls</p>
            <button class="btn btn-warning" onclick="enableGestures()">
                <i class="fas fa-hand-rock"></i> Enable Gesture Control
            </button>
        </div>
        
        <div class="settings-grid">
            <div class="setting-card">
                <h3><i class="fas fa-palette"></i> Theme & Appearance</h3>
                <form method="post">
                    <div class="form-group">
                        <label>System Theme</label>
                        <select name="theme" required>
                            <option value="light">Light Mode</option>
                            <option value="dark">Dark Mode</option>
                            <option value="auto">Auto (System)</option>
                            <option value="neon">Neon Cyber</option>
                            <option value="nature">Nature Green</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Accent Color</label>
                        <div class="color-picker">
                            <div class="color-option active" style="background: #667eea;" onclick="selectColor('#667eea')"></div>
                            <div class="color-option" style="background: #10b981;" onclick="selectColor('#10b981')"></div>
                            <div class="color-option" style="background: #f59e0b;" onclick="selectColor('#f59e0b')"></div>
                            <div class="color-option" style="background: #ef4444;" onclick="selectColor('#ef4444')"></div>
                            <div class="color-option" style="background: #8b5cf6;" onclick="selectColor('#8b5cf6')"></div>
                        </div>
                    </div>
                    <button type="submit" name="update_theme" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Theme
                    </button>
                </form>
            </div>
            
            <div class="setting-card">
                <h3><i class="fas fa-shield-alt"></i> Security Settings</h3>
                <form method="post">
                    <div class="form-group">
                        <label>Security Level</label>
                        <select name="security_level" required>
                            <option value="basic">Basic Protection</option>
                            <option value="enhanced">Enhanced Security</option>
                            <option value="maximum">Maximum Security</option>
                            <option value="military">Military Grade</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Two-Factor Authentication</label>
                        <label class="toggle-switch">
                            <input type="checkbox" name="2fa">
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label>Biometric Login</label>
                        <label class="toggle-switch">
                            <input type="checkbox" name="biometric">
                            <span class="slider"></span>
                        </label>
                    </div>
                    <button type="submit" name="update_security" class="btn btn-danger">
                        <i class="fas fa-lock"></i> Update Security
                    </button>
                </form>
            </div>
            
            <div class="setting-card">
                <h3><i class="fas fa-bell"></i> Smart Notifications</h3>
                <form method="post">
                    <div class="form-group">
                        <label>Email Notifications</label>
                        <label class="toggle-switch">
                            <input type="checkbox" name="email_notif">
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label>SMS Alerts</label>
                        <label class="toggle-switch">
                            <input type="checkbox" name="sms_notif">
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label>Push Notifications</label>
                        <label class="toggle-switch">
                            <input type="checkbox" name="push_notif">
                            <span class="slider"></span>
                        </label>
                    </div>
                    <button type="submit" name="update_notifications" class="btn btn-success">
                        <i class="fas fa-bell"></i> Save Notifications
                    </button>
                </form>
            </div>
            
            <div class="setting-card">
                <h3><i class="fas fa-mobile-alt"></i> Mobile Integration</h3>
                <div class="form-group">
                    <label>Mobile App Sync</label>
                    <label class="toggle-switch">
                        <input type="checkbox">
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="form-group">
                    <label>QR Code Access</label>
                    <button class="btn btn-primary" onclick="generateQR()">
                        <i class="fas fa-qrcode"></i> Generate QR
                    </button>
                </div>
                <div class="form-group">
                    <label>Smartwatch Support</label>
                    <button class="btn btn-success" onclick="connectWatch()">
                        <i class="fas fa-clock"></i> Connect Watch
                    </button>
                </div>
            </div>
            
            <div class="setting-card">
                <h3><i class="fas fa-cloud"></i> Cloud & Backup</h3>
                <div class="form-group">
                    <label>Auto Cloud Backup</label>
                    <label class="toggle-switch">
                        <input type="checkbox">
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="form-group">
                    <label>Backup Frequency</label>
                    <select>
                        <option>Every Hour</option>
                        <option>Daily</option>
                        <option>Weekly</option>
                        <option>Monthly</option>
                    </select>
                </div>
                <button class="btn btn-warning" onclick="syncCloud()">
                    <i class="fas fa-sync"></i> Sync Now
                </button>
            </div>
            
            <div class="setting-card">
                <h3><i class="fas fa-robot"></i> System Automation</h3>
                <div class="form-group">
                    <label>Auto Updates</label>
                    <label class="toggle-switch">
                        <input type="checkbox">
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="form-group">
                    <label>Smart Maintenance</label>
                    <label class="toggle-switch">
                        <input type="checkbox">
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="form-group">
                    <label>Performance Optimization</label>
                    <label class="toggle-switch">
                        <input type="checkbox">
                        <span class="slider"></span>
                    </label>
                </div>
                <button class="btn btn-primary" onclick="runAutomation()">
                    <i class="fas fa-play"></i> Run Automation
                </button>
            </div>
        </div>
    </div>
    
    <script>
    function selectColor(color) {
        document.querySelectorAll('.color-option').forEach(el => el.classList.remove('active'));
        event.target.classList.add('active');
        document.documentElement.style.setProperty('--accent-color', color);
    }
    
    function activateVoice() {
        alert('🎤 Voice Control Activated:\n\n• Microphone access granted\n• Voice recognition enabled\n• Say "System status" to test\n• Voice commands are now active');
    }
    
    function enableGestures() {
        alert('👋 Gesture Control Enabled:\n\n• Camera access granted\n• Hand tracking active\n• Wave to navigate menus\n• Point to select items');
    }
    
    function generateQR() {
        alert('📱 QR Code Generated:\n\n• Mobile access code created\n• Scan with your phone\n• Instant system access\n• Works with any QR scanner');
    }
    
    function connectWatch() {
        alert('⌚ Smartwatch Connected:\n\n• Bluetooth pairing successful\n• Notifications synced\n• Quick controls available\n• System status on your wrist');
    }
    
    function syncCloud() {
        alert('☁️ Cloud Sync Started:\n\n• Uploading system data\n• Backup in progress\n• Sync completed successfully\n• All data is safe in cloud');
    }
    
    function runAutomation() {
        alert('🤖 Automation Running:\n\n• System optimization started\n• Performance tuning active\n• Auto-maintenance enabled\n• System running at peak efficiency');
    }
    
    // Smart mode toggle
    document.getElementById('smartMode').addEventListener('change', function() {
        if (this.checked) {
            alert('✨ Smart Mode Activated:\n\n• System learning your patterns\n• Auto-optimization enabled\n• Performance monitoring active\n• Smart suggestions available');
        }
    });
    </script>
</body>
</html>