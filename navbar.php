<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#667eea">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="LSPU CCS">
<link rel="apple-touch-icon" href="logo-ccs.webp">

<style>
.header {
    background: #667eea;
    padding: 1rem 2rem;
    z-index: 9999;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    box-shadow: 0 2px 15px rgba(102, 126, 234, 0.25);
}

.nav-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo-section {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.logo-img {
    width: 45px;
    height: 45px;
}

.logo-text h1 {
    font-size: 1.1rem;
    font-weight: 700;
    color: white;
    line-height: 1.2;
}

.logo-text p {
    font-size: 0.75rem;
    color: rgba(255,255,255,0.9);
}

.user-section {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.welcome-text {
    font-weight: 600;
    color: white;
    font-size: 0.9rem;
}

.notif-icon {
    position: relative;
    color: white;
    font-size: 1.25rem;
    padding: 0.5rem;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.notif-icon:hover {
    transform: scale(1.1);
}

.notif-badge {
    position: absolute;
    top: 0;
    right: 0;
    background: #ef4444;
    color: white;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 0.15rem 0.4rem;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
}

.notif-dropdown {
    position: fixed;
    top: 65px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.2);
    width: 400px;
    max-height: 500px;
    overflow: hidden;
    display: none;
    z-index: 10000;
    border: 1px solid #e5e7eb;
}

@media (min-width: 769px) {
    .notif-dropdown {
        right: 1rem;
        left: auto;
    }
}

.notif-dropdown.show {
    display: block;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.notif-dropdown-header {
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notif-dropdown-header h3 {
    font-size: 1rem;
    font-weight: 700;
    color: #2d3748;
}

.notif-dropdown-list {
    max-height: 380px;
    overflow-y: auto;
}

.notif-dropdown-list::-webkit-scrollbar {
    width: 6px;
}

.notif-dropdown-list::-webkit-scrollbar-track {
    background: #f3f4f6;
}

.notif-dropdown-list::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 3px;
}

.notif-dropdown-list::-webkit-scrollbar-thumb:hover {
    background: #a0aec0;
}

.notif-dropdown-item {
    padding: 1.25rem;
    border-bottom: 1px solid #f3f4f6;
    transition: all 0.2s ease;
    cursor: pointer;
    position: relative;
}

.notif-dropdown-item:hover {
    background: #f9fafb;
}

.notif-dropdown-item.unread {
    background: #f0f4ff;
}

.notif-dropdown-item .notif-message {
    font-size: 0.9rem;
    color: #2d3748;
    margin-bottom: 0.5rem;
    line-height: 1.5;
    word-wrap: break-word;
}

.notif-dropdown-item .notif-message strong {
    display: block;
    margin-bottom: 0.5rem;
    color: #667eea;
    font-size: 0.95rem;
}

.notif-dropdown-item .notif-time {
    font-size: 0.75rem;
    color: #718096;
}

.notif-dropdown-footer {
    padding: 0.75rem;
    text-align: center;
    border-top: 1px solid #e5e7eb;
}

.notif-dropdown-footer a {
    color: #667eea;
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
}

.notif-dropdown-footer a:hover {
    text-decoration: underline;
}

.notif-empty {
    padding: 2rem;
    text-align: center;
    color: #718096;
    font-size: 0.85rem;
}

.logout-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}

.logout-modal.show {
    display: flex;
    animation: fadeIn 0.3s ease;
}

.logout-modal-content {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    max-width: 400px;
    width: 90%;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    animation: scaleIn 0.3s ease;
}

@keyframes scaleIn {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.logout-modal-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.logout-modal-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #fef3c7;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #f59e0b;
    font-size: 1.5rem;
}

.logout-modal-header h3 {
    font-size: 1.25rem;
    color: #2d3748;
    font-weight: 700;
}

.logout-modal-body {
    color: #718096;
    margin-bottom: 1.5rem;
    line-height: 1.6;
}

.logout-modal-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

.modal-btn {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    font-size: 0.9rem;
}

.modal-btn-cancel {
    background: #f3f4f6;
    color: #374151;
}

.modal-btn-cancel:hover {
    background: #e5e7eb;
}

.modal-btn-confirm {
    background: #ef4444;
    color: white;
}

.modal-btn-confirm:hover {
    background: #dc2626;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.logout-btn {
    background: rgba(255,255,255,0.15);
    color: white;
    border: 1.5px solid rgba(255,255,255,0.3);
    padding: 0.5rem 1.2rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.85rem;
}

.logout-btn:hover {
    background: rgba(255,255,255,0.25);
    transform: translateY(-1px);
}

.toggle-btn {
    background: rgba(255,255,255,0.15);
    border: none;
    color: white;
    font-size: 1.25rem;
    cursor: pointer;
    padding: 0.6rem;
    margin-right: 1rem;
    border-radius: 8px;
}

.dark-mode-toggle {
    width: 60px;
    height: 30px;
    background: rgba(255,255,255,0.2);
    border-radius: 15px;
    position: relative;
    cursor: pointer;
    transition: background 0.3s ease;
    border: 2px solid rgba(255,255,255,0.3);
}

.dark-mode-toggle:hover {
    background: rgba(255,255,255,0.3);
}

.toggle-slider {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 22px;
    height: 22px;
    background: white;
    border-radius: 50%;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.toggle-slider i {
    font-size: 0.7rem;
    color: #667eea;
}

body.dark-mode .toggle-slider {
    left: 32px;
    background: #2d3748;
}

body.dark-mode .toggle-slider i {
    color: #fbbf24;
}

.sidebar {
    position: fixed;
    left: 0;
    top: 65px;
    width: 260px;
    height: calc(100vh - 65px);
    background: white;
    box-shadow: 4px 0 25px rgba(0,0,0,0.06);
    z-index: 99;
    overflow-y: auto;
    transition: all 0.3s ease;
    border-right: 1px solid #e8ecf4;
    display: flex;
    flex-direction: column;
}

.sidebar.collapsed {
    width: 70px;
}

.sidebar.collapsed .sidebar-menu a span {
    display: none;
}

.sidebar.collapsed .sidebar-menu a {
    justify-content: center;
    padding: 1rem;
}

.sidebar.collapsed:hover {
    width: 260px;
}

.sidebar.collapsed:hover .sidebar-menu a span {
    display: inline;
}

.sidebar.collapsed:hover .sidebar-menu a {
    justify-content: flex-start;
    padding: 1rem 1.5rem;
}

.sidebar-menu {
    padding: 1.5rem 0;
    flex: 1;
    overflow-y: auto;
}

.sidebar-menu a {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.5rem;
    margin: 0.25rem 0.75rem 0.25rem 0.75rem;
    color: #4a5568;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 0.95rem;
    font-weight: 500;
    border-radius: 12px;
}

.sidebar-menu a:hover {
    background: #f0f4ff;
    color: #667eea;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
}

.sidebar-menu a.active {
    background: #667eea;
    color: white;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    font-weight: 600;
}

.sidebar-menu a i {
    width: 22px;
    text-align: center;
    font-size: 1.1rem;
}

.logout-link {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.5rem;
    margin: 0.25rem 0.75rem;
    color: #ef4444;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 0.95rem;
    font-weight: 500;
    border-radius: 12px;
    border-top: 1px solid #e2e8f0;
    margin-top: 0.5rem;
}

.logout-link:hover {
    background: #fef2f2;
    color: #dc2626;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.15);
}

.logout-link i {
    width: 22px;
    text-align: center;
    font-size: 1.1rem;
}

@media (max-width: 768px) {
    .header {
        padding: 0.75rem 1rem;
    }

    .logo-img {
        width: 35px;
        height: 35px;
    }

    .logo-text h1 {
        font-size: 0.85rem;
    }

    .logo-text p {
        display: none;
    }

    .toggle-btn {
        margin-right: 0.5rem;
    }

    .welcome-text {
        display: none;
    }

    .logout-btn {
        padding: 0.4rem 0.6rem;
        font-size: 0.8rem;
    }

    .logout-btn span {
        display: none;
    }

    .sidebar {
        transform: translateX(-100%);
        width: 250px;
    }

    .sidebar.show {
        transform: translateX(0);
    }

    .notif-icon {
        font-size: 1.1rem;
        padding: 0.4rem;
    }
    
    .notif-badge {
        top: 0;
        right: 0;
        font-size: 0.6rem;
        padding: 0.1rem 0.3rem;
        min-width: 14px;
    }
    
    .notif-dropdown {
        width: 400px !important;
        right: 1rem !important;
        left: auto !important;
        top: 56px !important;
        max-width: calc(100vw - 2rem) !important;
        max-height: 500px !important;
        border-radius: 12px !important;
        box-shadow: 0 8px 30px rgba(0,0,0,0.2) !important;
        border: 1px solid #e5e7eb !important;
        transform: none !important;
    }
    
    .notif-dropdown-header {
        padding: 0.65rem 0.75rem;
    }
    
    .notif-dropdown-header h3 {
        font-size: 0.85rem;
    }

    .notif-dropdown-list {
        max-height: calc(60vh - 90px);
    }
    
    .notif-dropdown-item {
        padding: 0.75rem;
    }
    
    .notif-dropdown-item .notif-message {
        font-size: 0.75rem;
        line-height: 1.3;
        margin-bottom: 0.3rem;
    }
    
    .notif-dropdown-item .notif-message strong {
        font-size: 0.8rem;
        margin-bottom: 0.25rem;
    }
    
    .notif-dropdown-item .notif-time {
        font-size: 0.65rem;
    }
    
    .notif-dropdown-footer {
        padding: 0.55rem;
    }
    
    .notif-dropdown-footer a {
        font-size: 0.75rem;
    }
    
    .notif-empty {
        padding: 1.25rem;
        font-size: 0.75rem;
    }
}

@media (max-width: 480px) {
    .logo-text h1 {
        font-size: 0.75rem;
    }
    
    .user-section {
        gap: 0.5rem;
    }
    
    .notif-icon {
        font-size: 1rem;
        padding: 0.35rem;
    }
    
    .notif-dropdown {
        width: 350px !important;
        right: 0.5rem !important;
        left: auto !important;
        max-width: calc(100vw - 1rem) !important;
        max-height: 450px !important;
        border-radius: 12px !important;
        box-shadow: 0 8px 30px rgba(0,0,0,0.2) !important;
        border: 1px solid #e5e7eb !important;
        transform: none !important;
    }
    
    .notif-dropdown-header {
        padding: 0.6rem 0.7rem;
    }
    
    .notif-dropdown-header h3 {
        font-size: 0.8rem;
    }
    
    .notif-dropdown-list {
        max-height: calc(55vh - 85px);
    }
    
    .notif-dropdown-item {
        padding: 0.65rem;
    }
    
    .notif-dropdown-item .notif-message {
        font-size: 0.7rem;
    }
    
    .notif-dropdown-item .notif-message strong {
        font-size: 0.75rem;
    }
}
body.dark-mode {
    background: #1a202c;
    color: #e2e8f0;
}

body.dark-mode .header {
    background: #2d3748;
}

body.dark-mode .sidebar {
    background: #2d3748;
    border-right: 1px solid #4a5568;
}

body.dark-mode .sidebar-menu a {
    color: #cbd5e0;
}

body.dark-mode .sidebar-menu a:hover {
    background: #4a5568;
    color: #667eea;
}

body.dark-mode .sidebar-menu a.active {
    background: #667eea;
    color: white;
}

body.dark-mode .logout-link {
    color: #fca5a5;
    border-top-color: #4a5568;
}

body.dark-mode .logout-link:hover {
    background: #7f1d1d;
    color: #fecaca;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.15);
}

@media (min-width: 769px) {
    body.dark-mode .notif-dropdown {
        background: #2d3748;
        border: 1px solid #4a5568;
    }

    body.dark-mode .notif-dropdown-header,
    body.dark-mode .notif-dropdown-header h3 {
        color: #e2e8f0;
        border-bottom-color: #4a5568;
    }

    body.dark-mode .notif-dropdown-item {
        border-bottom-color: #4a5568;
    }

    body.dark-mode .notif-dropdown-item:hover {
        background: #374151;
    }

    body.dark-mode .notif-dropdown-item.unread {
        background: #374151;
    }

    body.dark-mode .notif-dropdown-item .notif-message {
        color: #e2e8f0;
    }

    body.dark-mode .notif-dropdown-footer {
        border-top-color: #4a5568;
    }
}

@media (max-width: 768px) {
    body.dark-mode .notif-dropdown {
        background: #2d3748 !important;
        border: 1px solid #4a5568 !important;
    }
    
    body.dark-mode .notif-dropdown-header {
        background: #2d3748;
        border-bottom: 1px solid #4a5568;
    }
    
    body.dark-mode .notif-dropdown-header h3 {
        color: #e2e8f0;
    }
    
    body.dark-mode .notif-dropdown-item {
        background: #2d3748;
        border-bottom-color: #4a5568;
    }
    
    body.dark-mode .notif-dropdown-item:hover {
        background: #374151;
    }
    
    body.dark-mode .notif-dropdown-item.unread {
        background: #374151;
    }
    
    body.dark-mode .notif-dropdown-item .notif-message {
        color: #e2e8f0;
    }
    
    body.dark-mode .notif-dropdown-footer {
        background: #2d3748;
        border-top-color: #4a5568;
    }
}

body.dark-mode .logout-modal-content {
    background: #2d3748;
    color: #e2e8f0;
}

body.dark-mode .logout-modal-header h3 {
    color: #e2e8f0;
}

body.dark-mode .modal-btn-cancel {
    background: #4a5568;
    color: #e2e8f0;
}

body.dark-mode .modal-btn-cancel:hover {
    background: #374151;
}
</style>

<header class="header">
    <div class="nav-container">
        <div class="logo-section">
            <button class="toggle-btn" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <img src="logo-ccs.webp" alt="LSPU Logo" class="logo-img">
            <div class="logo-text">
                <h1>LSPU Computer Studies</h1>
                <p>Citizen's Charter Portal</p>
            </div>
        </div>
        
        <div class="user-section">
            <div class="dark-mode-toggle" onclick="toggleDarkMode()">
                <div class="toggle-slider" id="toggleSlider">
                    <i class="fas fa-moon" id="darkModeIcon"></i>
                </div>
            </div>
            
            <div class="notif-icon" onclick="toggleNotifDropdown()">
                <i class="fas fa-bell"></i>
                <?php
                $conn_notif = new mysqli("localhost", "root", "", "student_services");
                $user_id = $_SESSION["id_number"] ?? '';
                $unread_count = 0;
                if (!$conn_notif->connect_error && $user_id) {
                    $result = $conn_notif->query("SELECT COUNT(*) as count FROM notifications WHERE user_id='$user_id' AND is_read=0");
                    if ($result) $unread_count = $result->fetch_assoc()['count'];
                }
                if ($unread_count > 0): ?>
                    <span class="notif-badge"><?php echo $unread_count > 99 ? '99+' : $unread_count; ?></span>
                <?php endif; ?>
                
                <div class="notif-dropdown" id="notifDropdown">
                    <div class="notif-dropdown-header">
                        <h3>Notifications</h3>
                    </div>
                    <div class="notif-dropdown-list">
                        <?php
                        if (!$conn_notif->connect_error && $user_id) {
                            $result = $conn_notif->query("SELECT id, message, created_at, is_read FROM notifications WHERE user_id='$user_id' ORDER BY created_at DESC LIMIT 5");
                            if ($result && $result->num_rows > 0) {
                                while($notif = $result->fetch_assoc()) {
                                    $unread_class = $notif['is_read'] ? '' : 'unread';
                                    echo '<div class="notif-dropdown-item ' . $unread_class . '">';
                                    echo '<div class="notif-message">' . nl2br(htmlspecialchars($notif['message'])) . '</div>';
                                    echo '<div class="notif-time">' . date('M d, Y - h:i A', strtotime($notif['created_at'])) . '</div>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<div class="notif-empty">No notifications</div>';
                            }
                        }
                        $conn_notif->close();
                        ?>
                    </div>
                    <div class="notif-dropdown-footer">
                        <a href="notification.php">View All Notifications</a>
                    </div>
                </div>
            </div>
            
            <?php if (isset($first_name) && $first_name): ?>
            <div class="welcome-text" style="display: flex; align-items: center; gap: 0.75rem;">
                <span>Welcome, <?php echo htmlspecialchars($first_name . (isset($last_name) && $last_name ? ' ' . $last_name : '')); ?></span>
                <?php
                // Get profile picture from database
                $conn_profile = new mysqli("localhost", "root", "", "student_services");
                $user_id_profile = $_SESSION["user_id"] ?? 0;
                $profile_pic = '';
                if (!$conn_profile->connect_error && $user_id_profile) {
                    $stmt = $conn_profile->prepare("SELECT profile_picture FROM users WHERE id=?");
                    if ($stmt) {
                        $stmt->bind_param("i", $user_id_profile);
                        $stmt->execute();
                        $stmt->bind_result($profile_pic);
                        $stmt->fetch();
                        $stmt->close();
                    }
                    $conn_profile->close();
                }
                if (!empty($profile_pic)): ?>
                    <img src="<?php echo htmlspecialchars($profile_pic) . '?t=' . time(); ?>" alt="Profile" style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 2px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                <?php else: ?>
                    <div style="width: 35px; height: 35px; border-radius: 50%; background: linear-gradient(135deg, #764ba2 0%, #667eea 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 0.85rem; border: 2px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                        <?php echo strtoupper(substr($first_name, 0, 1) . (isset($last_name) ? substr($last_name, 0, 1) : '')); ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</header>

<aside class="sidebar" id="sidebar">
    <nav class="sidebar-menu">
        <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-th-large"></i>
            <span>Dashboard</span>
        </a>
        <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Student Services
            </span>
        </a>
        <a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
        <a href="student_view_grades.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'student_view_grades.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i>
            <span>My Grades</span>
        </a>

        <a href="notification.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'notification.php' ? 'active' : ''; ?>">
            <i class="fas fa-bell"></i>
            <span>Notifications</span>
        </a>
        <!--<a href="newsfeed.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'newsfeed.php' ? 'active' : ''; ?>">
            <i class="fas fa-newspaper"></i>
            <span>Newsfeed</span>
        </a>-->
    </nav>
    <a href="#" onclick="showLogoutModal(); return false;" class="logout-link">
        <i class="fas fa-sign-out-alt"></i>
        <span>Logout</span>
    </a>
</aside>

<div class="logout-modal" id="logoutModal">
    <div class="logout-modal-content">
        <div class="logout-modal-header">
            <div class="logout-modal-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3>Confirm Logout</h3>
        </div>
        <div class="logout-modal-body">
            Are you sure you want to logout? You will need to login again to access your account.
        </div>
        <div class="logout-modal-actions">
            <button class="modal-btn modal-btn-cancel" onclick="hideLogoutModal()">Cancel</button>
            <form method="post" style="display:inline;">
                <button type="submit" name="logout" class="modal-btn modal-btn-confirm">Yes, Logout</button>
            </form>
        </div>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContainer = document.getElementById('mainContainer');
    const footer = document.getElementById('footer');
    
    if (window.innerWidth <= 768) {
        sidebar.classList.toggle('show');
    } else {
        sidebar.classList.toggle('collapsed');
        if (mainContainer) mainContainer.classList.toggle('collapsed');
        if (footer) footer.classList.toggle('collapsed');
    }
}

const sidebar = document.getElementById('sidebar');
const mainContainer = document.getElementById('mainContainer');
const footer = document.getElementById('footer');

if (sidebar && mainContainer) {
    sidebar.addEventListener('mouseenter', function() {
        if (window.innerWidth > 768 && sidebar.classList.contains('collapsed')) {
            mainContainer.style.marginLeft = '260px';
            if (footer) footer.style.marginLeft = '260px';
        }
    });

    sidebar.addEventListener('mouseleave', function() {
        if (window.innerWidth > 768 && sidebar.classList.contains('collapsed')) {
            mainContainer.style.marginLeft = '70px';
            if (footer) footer.style.marginLeft = '70px';
        }
    });
}

document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.querySelector('.toggle-btn');
    
    if (window.innerWidth <= 768 && sidebar.classList.contains('show')) {
        if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
            sidebar.classList.remove('show');
        }
    }
    
    // Close notification dropdown when clicking outside
    const notifDropdown = document.getElementById('notifDropdown');
    const notifIcon = document.querySelector('.notif-icon');
    if (notifDropdown && !notifIcon.contains(event.target)) {
        notifDropdown.classList.remove('show');
    }
});

function toggleNotifDropdown() {
    const dropdown = document.getElementById('notifDropdown');
    dropdown.classList.toggle('show');
}

function showLogoutModal() {
    document.getElementById('logoutModal').classList.add('show');
}

function hideLogoutModal() {
    document.getElementById('logoutModal').classList.remove('show');
}

document.getElementById('logoutModal').addEventListener('click', function(e) {
    if (e.target === this) hideLogoutModal();
});

function toggleDarkMode() {
    const isDark = document.body.classList.contains('dark-mode');
    const icon = document.getElementById('darkModeIcon');
    
    if (isDark) {
        // Switch to light mode
        document.body.classList.remove('dark-mode');
        document.documentElement.classList.add('light-mode');
        icon.classList.remove('fa-sun');
        icon.classList.add('fa-moon');
        localStorage.setItem('darkMode', 'disabled');
    } else {
        // Switch to dark mode
        document.body.classList.add('dark-mode');
        document.documentElement.classList.remove('light-mode');
        icon.classList.remove('fa-moon');
        icon.classList.add('fa-sun');
        localStorage.setItem('darkMode', 'enabled');
    }
}

window.addEventListener('DOMContentLoaded', function() {
    const darkMode = localStorage.getItem('darkMode');
    if (darkMode === 'enabled') {
        document.body.classList.add('dark-mode');
        document.documentElement.classList.remove('light-mode');
        const icon = document.getElementById('darkModeIcon');
        if (icon) {
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
        }
    } else {
        document.body.classList.remove('dark-mode');
        document.documentElement.classList.add('light-mode');
        const icon = document.getElementById('darkModeIcon');
        if (icon) {
            icon.classList.remove('fa-sun');
            icon.classList.add('fa-moon');
        }
    }
});

if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('service-worker.js')
        .then(reg => console.log('PWA Service Worker registered:', reg))
        .catch(err => console.log('Service Worker failed:', err));
}

let deferredPrompt;
window.addEventListener('beforeinstallprompt', (e) => {
    console.log('Install prompt triggered!');
    e.preventDefault();
    deferredPrompt = e;
    
    setTimeout(() => {
        const installBtn = document.createElement('button');
        installBtn.id = 'installBtn';
        installBtn.innerHTML = '<i class="fas fa-download"></i> Install App';
        installBtn.style.cssText = 'position:fixed;bottom:20px;left:20px;background:#10b981;color:white;border:none;padding:0.75rem 1.5rem;border-radius:8px;font-weight:600;cursor:pointer;z-index:1000;box-shadow:0 4px 12px rgba(16,185,129,0.3);transition:all 0.3s;font-family:Inter,sans-serif;';
        installBtn.onmouseover = () => installBtn.style.transform = 'translateY(-2px)';
        installBtn.onmouseout = () => installBtn.style.transform = '';
        installBtn.onclick = async () => {
            console.log('Install button clicked');
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            console.log('User choice:', outcome);
            if (outcome === 'accepted') installBtn.remove();
            deferredPrompt = null;
        };
        document.body.appendChild(installBtn);
        console.log('Install button added to page');
    }, 1000);
});
</script>
