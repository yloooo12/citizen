<?php
$first_name = $_SESSION["first_name"] ?? "Secretary";
$last_name = $_SESSION["last_name"] ?? "";
$user_id = $_SESSION["user_id"] ?? 0;

// Get unread notification count
$unread_count = 0;
if ($user_id) {
    $notif_conn = new mysqli("localhost", "root", "", "student_services");
    if (!$notif_conn->connect_error) {
        $stmt = $notif_conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $unread_count = $row['count'];
        }
        $stmt->close();
        $notif_conn->close();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["logout"])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}
?>
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
.notification-icon {
    position: relative;
    background: rgba(255,255,255,0.15);
    color: white;
    border: none;
    padding: 0.6rem;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1.1rem;
    transition: all 0.3s ease;
}
.notification-icon:hover {
    background: rgba(255,255,255,0.25);
    transform: translateY(-1px);
}
.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #ef4444;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: 700;
    border: 2px solid #667eea;
}
.notification-dropdown {
    position: absolute;
    top: 60px;
    right: 20px;
    width: 380px;
    max-height: 500px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    display: none;
    z-index: 10000;
    overflow: hidden;
}
.notification-dropdown.show {
    display: block;
    animation: slideDown 0.3s ease;
}
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.notif-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f9fafb;
}
.notif-header h4 {
    font-size: 0.95rem;
    color: #2d3748;
    font-weight: 600;
}
.notif-view-all {
    color: #667eea;
    font-size: 0.8rem;
    text-decoration: none;
    font-weight: 600;
}
.notif-view-all:hover {
    text-decoration: underline;
}
.notif-list {
    max-height: 400px;
    overflow-y: auto;
}
.notif-item {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #f1f5f9;
    cursor: pointer;
    transition: all 0.2s ease;
}
.notif-item:hover {
    background: #f9fafb;
}
.notif-item.unread {
    background: #f0f4ff;
}
.notif-item:last-child {
    border-bottom: none;
}
.notif-message {
    font-size: 0.85rem;
    color: #2d3748;
    margin-bottom: 0.4rem;
    line-height: 1.4;
}
.notif-time {
    font-size: 0.75rem;
    color: #a0aec0;
}
.notif-empty {
    padding: 2rem;
    text-align: center;
    color: #a0aec0;
    font-size: 0.9rem;
}
@media (max-width: 768px) {
    .notification-dropdown {
        right: 10px;
        width: calc(100vw - 20px);
        max-width: 380px;
    }
}
.welcome-text {
    font-weight: 600;
    color: white;
    font-size: 0.9rem;
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
@media (max-width: 768px) { 
    .header { padding: 0.75rem 1rem; }
    .logo-img { width: 35px; height: 35px; }
    .logo-text h1 { font-size: 0.85rem; }
    .logo-text p { display: none; }
    .welcome-text { display: none; }
    .logout-btn { padding: 0.4rem 0.6rem; font-size: 0.8rem; }
    .logout-btn span { display: none; }
    .toggle-btn { margin-right: 0.5rem; }
    .sidebar { transform: translateX(-100%); width: 250px; }
    .sidebar.show { transform: translateX(0); }
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
                <h1>Secretary Dashboard</h1>
                <p>Student Information Management</p>
            </div>
        </div>
        
        <div class="user-section">
            <div class="welcome-text">
                <span>Welcome, <?php echo htmlspecialchars($first_name . ($last_name ? ' ' . $last_name : '')); ?></span>
            </div>
            <button class="notification-icon" onclick="toggleNotifications()">
                <i class="fas fa-bell"></i>
                <?php if ($unread_count > 0): ?>
                    <span class="notification-badge" id="notifBadge"><?php echo $unread_count > 9 ? '9+' : $unread_count; ?></span>
                <?php endif; ?>
            </button>
            <button class="logout-btn" onclick="showLogoutModal()">
                <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
            </button>
        </div>
    </div>
</header>

<aside class="sidebar" id="sidebar">
    <nav class="sidebar-menu">
        <a href="secretary_dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'secretary_dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-th-large"></i>
            <span>Dashboard</span>
        </a>
        <a href="secretary_interviews.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'secretary_interviews.php' ? 'active' : ''; ?>">
            <i class="fas fa-microphone"></i>
            <span>Interview Requests</span>
        </a>
        <a href="secretary_crediting.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'secretary_crediting.php' ? 'active' : ''; ?>">
            <i class="fas fa-clipboard-check"></i>
            <span>Crediting Management</span>
        </a>

        <a href="secretary_view_students.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'secretary_view_students.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span>View Students</span>
        </a>
        <a href="secretary_reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'secretary_reports.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i>
            <span>Reports</span>
        </a>
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

<div class="notification-dropdown" id="notificationDropdown">
    <div class="notif-header">
        <h4><i class="fas fa-bell"></i> Notifications</h4>
        <a href="secretary_notifications.php" class="notif-view-all">View All</a>
    </div>
    <div class="notif-list" id="notifList">
        <div class="notif-empty"><i class="far fa-bell-slash"></i><br>Loading...</div>
    </div>
</div>

<script>
function showLogoutModal() {
    document.getElementById('logoutModal').classList.add('show');
}

function hideLogoutModal() {
    document.getElementById('logoutModal').classList.remove('show');
}

document.getElementById('logoutModal').addEventListener('click', function(e) {
    if (e.target === this) hideLogoutModal();
});

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContainer = document.getElementById('mainContainer');
    
    if (window.innerWidth <= 768) {
        sidebar.classList.toggle('show');
    } else {
        sidebar.classList.toggle('collapsed');
        if (mainContainer) mainContainer.classList.toggle('collapsed');
    }
}

function toggleNotifications() {
    const dropdown = document.getElementById('notificationDropdown');
    const isVisible = dropdown.classList.contains('show');
    
    if (!isVisible) {
        loadNotifications();
        dropdown.classList.add('show');
    } else {
        dropdown.classList.remove('show');
    }
}

function loadNotifications() {
    fetch('get_notifications.php')
        .then(res => res.json())
        .then(data => {
            const notifList = document.getElementById('notifList');
            if (data.notifications && data.notifications.length > 0) {
                notifList.innerHTML = data.notifications.map(n => `
                    <div class="notif-item ${n.is_read == 0 ? 'unread' : ''}" onclick="markAsRead(${n.id})">
                        <div class="notif-message">${n.message}</div>
                        <div class="notif-time"><i class="far fa-clock"></i> ${n.time_ago}</div>
                    </div>
                `).join('');
            } else {
                notifList.innerHTML = '<div class="notif-empty"><i class="far fa-bell-slash"></i><br>No notifications</div>';
            }
        });
}

function markAsRead(notifId) {
    fetch('mark_notification_read.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'notif_id=' + notifId
    }).then(() => {
        loadNotifications();
        updateBadge();
    });
}

function updateBadge() {
    fetch('get_notifications.php')
        .then(res => res.json())
        .then(data => {
            const badge = document.getElementById('notifBadge');
            if (data.unread_count > 0) {
                if (badge) {
                    badge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                } else {
                    document.querySelector('.notification-icon').innerHTML += `<span class="notification-badge" id="notifBadge">${data.unread_count > 9 ? '9+' : data.unread_count}</span>`;
                }
            } else if (badge) {
                badge.remove();
            }
        });
}

document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.querySelector('.toggle-btn');
    const notifDropdown = document.getElementById('notificationDropdown');
    const notifIcon = document.querySelector('.notification-icon');
    
    if (window.innerWidth <= 768 && sidebar.classList.contains('show')) {
        if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
            sidebar.classList.remove('show');
        }
    }
    
    if (notifDropdown && !notifDropdown.contains(event.target) && !notifIcon.contains(event.target)) {
        notifDropdown.classList.remove('show');
    }
});
</script>
