<!-- Navbar -->
<style>
    .navbar {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 65px;
        background: white;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 2rem;
        z-index: 1000;
        transition: all 0.3s ease;
    }
    .navbar-left {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }
    .menu-toggle {
        background: none;
        border: none;
        font-size: 1.5rem;
        color: #667eea;
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    .menu-toggle:hover {
        background: #f0f4ff;
    }
    .navbar-brand {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-weight: 700;
        color: #2d3748;
        font-size: 1.1rem;
    }
    .navbar-brand img {
        width: 40px;
        height: 40px;
    }
    .navbar-right {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .user-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem 1rem;
        background: #f7fafc;
        border-radius: 10px;
    }
    .user-avatar {
        width: 35px;
        height: 35px;
        background: #48bb78;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
    }
    .user-details {
        display: flex;
        flex-direction: column;
    }
    .user-name {
        font-weight: 600;
        font-size: 0.9rem;
        color: #2d3748;
    }
    .user-role {
        font-size: 0.75rem;
        color: #718096;
    }
    
    .notif-icon {
        position: relative;
        color: #667eea;
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
        right: 1rem;
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
    
    .notif-dropdown-item {
        padding: 1.25rem;
        border-bottom: 1px solid #f3f4f6;
        transition: all 0.2s ease;
        cursor: pointer;
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
    
    .notif-empty {
        padding: 2rem;
        text-align: center;
        color: #718096;
        font-size: 0.85rem;
    }
    
    /* Sidebar */
    .sidebar {
        position: fixed;
        left: 0;
        top: 65px;
        width: 260px;
        height: calc(100vh - 65px);
        background: white;
        box-shadow: 2px 0 10px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        z-index: 999;
        overflow-y: auto;
    }
    .sidebar.collapsed {
        width: 70px;
    }
    .sidebar-menu {
        padding: 1rem 0;
    }
    .menu-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.875rem 1.5rem;
        color: #4a5568;
        text-decoration: none;
        transition: all 0.3s ease;
        border-left: 3px solid transparent;
    }
    .menu-item:hover {
        background: #f0f4ff;
        color: #667eea;
        border-left-color: #667eea;
    }
    .menu-item.active {
        background: #f0f4ff;
        color: #667eea;
        border-left-color: #667eea;
        font-weight: 600;
    }
    .menu-item i {
        font-size: 1.25rem;
        width: 24px;
        text-align: center;
    }
    .menu-text {
        transition: opacity 0.3s ease;
    }
    .sidebar.collapsed .menu-text {
        opacity: 0;
        display: none;
    }
    
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
        }
        .sidebar.show {
            transform: translateX(0);
        }
    }
    
    /* Main Container */
    .main-container {
        margin-left: 260px;
        margin-top: 65px;
        padding: 2rem;
        transition: margin-left 0.3s ease;
    }
    .main-container.collapsed {
        margin-left: 70px;
    }
    @media (max-width: 768px) {
        .main-container {
            margin-left: 0 !important;
        }
    }
</style>

<nav class="navbar">
    <div class="navbar-left">
        <button class="menu-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <div class="navbar-brand">
            <img src="logo-ccs.webp" alt="Logo">
            <span>Teacher Portal</span>
        </div>
    </div>
    <div class="navbar-right">
        <?php
        // Get teacher notification count
        $teacher_id = $_SESSION['id_number'] ?? '';
        $notif_count = 0;
        if ($teacher_id) {
            $conn_notif = new mysqli("localhost", "root", "", "student_services");
            if (!$conn_notif->connect_error) {
                $result = $conn_notif->query("SELECT COUNT(*) as count FROM teacher_notifications WHERE teacher_id='$teacher_id' AND is_read=0");
                if ($result) $notif_count = $result->fetch_assoc()['count'];
                $conn_notif->close();
            }
        }
        ?>
        <div class="notif-icon" onclick="toggleNotifDropdown()">
            <i class="fas fa-bell"></i>
            <?php if ($notif_count > 0): ?>
                <span class="notif-badge"><?php echo $notif_count > 99 ? '99+' : $notif_count; ?></span>
            <?php endif; ?>
            
            <div class="notif-dropdown" id="notifDropdown">
                <div class="notif-dropdown-header">
                    <h3>Notifications</h3>
                </div>
                <div class="notif-dropdown-list" id="notificationList">
                    <!-- Notifications loaded via JS -->
                </div>
                <div class="notif-dropdown-footer">
                    <a href="teacher_notifications.php">View All Notifications</a>
                </div>
            </div>
        </div>
        <?php if (isset($_SESSION['id_number']) && ($_SESSION['id_number'] == '246' || $_SESSION['id_number'] == '009')): ?>
        <a href="switch_account.php" style="padding: 0.5rem 1rem; background: #48bb78; color: white; border-radius: 8px; text-decoration: none; font-weight: 600; margin-right: 0.5rem;">
            <i class="fas fa-exchange-alt"></i> Switch
        </a>
        <?php endif; ?>
        <div class="user-info">
            <div class="user-avatar">T</div>
            <div class="user-details">
                <span class="user-name"><?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?></span>
                <span class="user-role">Teacher</span>
            </div>
        </div>
    </div>
</nav>



<aside class="sidebar" id="sidebar">
    <div class="sidebar-menu">
        <a href="teacher_dashboard.php" class="menu-item">
            <i class="fas fa-home"></i>
            <span class="menu-text">Dashboard</span>
        </a>
                <a href="teacher_classes.php" class="menu-item">
            <i class="fas fa-book"></i>
            <span class="menu-text">Classes</span>
        </a>
        <a href="teacher_students.php" class="menu-item">
            <i class="fas fa-users"></i>
            <span class="menu-text">My Students</span>
        </a>
        <a href="teacher_grades.php" class="menu-item">
            <i class="fas fa-chart-line"></i>
            <span class="menu-text">Grades</span>
        </a>
        <a href="admin_inc.php" class="menu-item">
            <i class="fas fa-file-alt"></i>
            <span class="menu-text">INC Requests</span>
        </a>
        <a href="teacher_upload_students.php" class="menu-item">
            <i class="fas fa-upload"></i>
            <span class="menu-text">Upload Students</span>
        </a>


        <a href="login.php" class="menu-item" style="margin-top: 2rem; color: #e53e3e;">
            <i class="fas fa-sign-out-alt"></i>
            <span class="menu-text">Logout</span>
        </a>
    </div>
</aside>

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

// Auto-highlight active menu
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop();
    const menuItems = document.querySelectorAll('.sidebar .menu-item');
    
    menuItems.forEach(item => {
        const href = item.getAttribute('href');
        if (href === currentPage) {
            item.classList.add('active');
        }
    });
});

function toggleNotifDropdown() {
    const dropdown = document.getElementById('notifDropdown');
    const isVisible = dropdown.classList.contains('show');
    
    if (!isVisible) {
        loadNotifications();
        dropdown.classList.add('show');
    } else {
        dropdown.classList.remove('show');
    }
}

function loadNotifications() {
    fetch('get_teacher_notifications.php')
    .then(response => response.json())
    .then(data => {
        const list = document.getElementById('notificationList');
        if (data.length === 0) {
            list.innerHTML = '<div class="notif-empty">No notifications</div>';
        } else {
            list.innerHTML = data.map(notif => 
                `<div class="notif-dropdown-item ${notif.is_read == 0 ? 'unread' : ''}">
                    <div class="notif-message">${notif.message}</div>
                    <div class="notif-time">${notif.created_at}</div>
                </div>`
            ).join('');
        }
        // Don't auto-mark as read - let user control this
    });
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('notifDropdown');
    const bell = document.querySelector('.notif-icon');
    if (dropdown && !bell.contains(e.target)) {
        dropdown.classList.remove('show');
    }
});
</script>