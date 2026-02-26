<?php
// Get unread admin notifications count
$notif_conn = new mysqli("localhost", "root", "", "citizenproj");
$admin_unread_count = 0;
if (!$notif_conn->connect_error) {
    $result = $notif_conn->query("SELECT COUNT(*) as count FROM admin_notifications WHERE is_read=0");
    if ($result) {
        $row = $result->fetch_assoc();
        $admin_unread_count = $row ? $row['count'] : 0;
    }
}
?>
<style>
.header {
    background: #667eea;
    padding: 1rem 2rem;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 100;
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
.toggle-btn {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1.2rem;
    transition: all 0.3s ease;
}
.toggle-btn:hover {
    background: rgba(255,255,255,0.3);
}
.logo-img {
    width: 45px;
    height: 45px;
    border-radius: 8px;
}
.logo-text h1 {
    color: white;
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0;
}
.logo-text p {
    color: rgba(255,255,255,0.9);
    font-size: 0.8rem;
    margin: 0;
}
.user-section {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    position: relative;
}
.welcome-text {
    color: white;
    font-size: 0.9rem;
    font-weight: 500;
}
.logout-btn {
    background: rgba(255,255,255,0.2);
    color: white;
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.logout-btn:hover {
    background: rgba(255,255,255,0.3);
}
.notif-icon {
    position: relative;
    color: white;
    font-size: 1.25rem;
    padding: 0.5rem;
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
    position: absolute;
    top: calc(100% + 0.5rem);
    right: -1rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    width: 350px;
    max-height: 400px;
    overflow-y: auto;
    display: none;
    z-index: 1000;
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
    max-height: 300px;
    overflow-y: auto;
}
.notif-dropdown-item {
    padding: 1rem;
    border-bottom: 1px solid #f3f4f6;
    transition: background 0.2s ease;
    cursor: pointer;
}
.notif-dropdown-item:hover {
    background: #f9fafb;
}
.notif-dropdown-item.unread {
    background: #f0f4ff;
}
.notif-dropdown-item .notif-message {
    font-size: 0.85rem;
    color: #2d3748;
    margin-bottom: 0.25rem;
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
@media (max-width: 768px) {
    .header { padding: 0.75rem 1rem; }
    .logo-img { width: 35px; height: 35px; }
    .logo-text h1 { font-size: 0.85rem; }
    .logo-text p { display: none; }
    .toggle-btn { width: 35px; height: 35px; font-size: 1rem; }
    .welcome-text { display: none; }
    .logout-btn { padding: 0.5rem 0.75rem; font-size: 0.8rem; }
    .logout-btn span { display: none; }
    .notif-icon { font-size: 1.1rem; padding: 0.4rem; }
    .notif-dropdown { width: 90vw; right: -0.5rem; }
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
                <p><?php echo isset($page_title) ? $page_title : 'Admin Panel'; ?></p>
            </div>
        </div>
        
        <div class="user-section">
            <div class="notif-icon" onclick="toggleAdminNotifDropdown()">
                <i class="fas fa-bell"></i>
                <?php if ($admin_unread_count > 0): ?>
                    <span class="notif-badge"><?php echo $admin_unread_count; ?></span>
                <?php endif; ?>
                
                <div class="notif-dropdown" id="adminNotifDropdown">
                    <div class="notif-dropdown-header">
                        <h3>Notifications</h3>
                    </div>
                    <div class="notif-dropdown-list">
                        <?php
                        if (!$notif_conn->connect_error) {
                            $result = $notif_conn->query("SELECT id, message, date_created, is_read FROM admin_notifications ORDER BY date_created DESC LIMIT 5");
                            if ($result && $result->num_rows > 0) {
                                while($notif = $result->fetch_assoc()) {
                                    $unread_class = $notif['is_read'] ? '' : 'unread';
                                    echo '<div class="notif-dropdown-item ' . $unread_class . '">';
                                    echo '<div class="notif-message">' . htmlspecialchars($notif['message']) . '</div>';
                                    echo '<div class="notif-time">' . date('M d, Y - h:i A', strtotime($notif['date_created'])) . '</div>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<div class="notif-empty">No notifications</div>';
                            }
                        }
                        ?>
                    </div>
                    <div class="notif-dropdown-footer">
                        <a href="admin_notification.php">View All Notifications</a>
                    </div>
                </div>
            </div>
            
            <div class="welcome-text">Admin Panel</div>
            
            <button type="button" onclick="showLogoutModal()" class="logout-btn" style="border:none;">
                <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
            </button>
        </div>
    </div>
</header>

<script>
function toggleAdminNotifDropdown() {
    const dropdown = document.getElementById('adminNotifDropdown');
    dropdown.classList.toggle('show');
}

document.addEventListener('click', function(event) {
    const notifDropdown = document.getElementById('adminNotifDropdown');
    const notifIcon = document.querySelector('.notif-icon');
    if (notifDropdown && notifIcon && !notifIcon.contains(event.target)) {
        notifDropdown.classList.remove('show');
    }
});
</script>
