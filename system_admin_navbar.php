<style>
.navbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 65px;
    background: #1a202c;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 2rem;
    z-index: 1000;
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
    color: #f56565;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 8px;
    transition: all 0.3s ease;
}
.menu-toggle:hover {
    background: rgba(245, 101, 101, 0.1);
}
.navbar-brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 700;
    color: #f56565;
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
    background: rgba(245, 101, 101, 0.1);
    border-radius: 10px;
}
.user-avatar {
    width: 35px;
    height: 35px;
    background: #f56565;
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
    color: white;
}
.user-role {
    font-size: 0.75rem;
    color: #f56565;
}

.sidebar {
    position: fixed;
    left: 0;
    top: 65px;
    width: 260px;
    height: calc(100vh - 65px);
    background: #2d3748;
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
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
    color: #cbd5e0;
    text-decoration: none;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
}
.menu-item:hover {
    background: rgba(245, 101, 101, 0.1);
    color: #f56565;
    border-left-color: #f56565;
}
.menu-item.active {
    background: rgba(245, 101, 101, 0.1);
    color: #f56565;
    border-left-color: #f56565;
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
</style>

<nav class="navbar">
    <div class="navbar-left">
        <button class="menu-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <div class="navbar-brand">
            <img src="logo-ccs.webp" alt="Logo">
            <span>System Admin</span>
        </div>
    </div>
    <div class="navbar-right">
        <div class="user-info">
            <div class="user-avatar">SA</div>
            <div class="user-details">
                <span class="user-name">System Admin</span>
                <span class="user-role">Super Administrator</span>
            </div>
        </div>
    </div>
</nav>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-menu">
        <a href="system_admin_dashboard.php" class="menu-item">
            <i class="fas fa-tachometer-alt"></i>
            <span class="menu-text">Dashboard</span>
        </a>
        <a href="system_admin_users.php" class="menu-item">
            <i class="fas fa-users"></i>
            <span class="menu-text">User Management</span>
        </a>
        <a href="system_admin_admins.php" class="menu-item">
            <i class="fas fa-user-shield"></i>
            <span class="menu-text">Admin Management</span>
        </a>
        <a href="system_admin_database.php" class="menu-item">
            <i class="fas fa-database"></i>
            <span class="menu-text">Database</span>
        </a>
        <a href="system_admin_reports.php" class="menu-item">
            <i class="fas fa-chart-bar"></i>
            <span class="menu-text">Reports</span>
        </a>
        <a href="system_admin_settings.php" class="menu-item">
            <i class="fas fa-cog"></i>
            <span class="menu-text">Settings</span>
        </a>
        <a href="system_admin_emergency.php" class="menu-item">
            <i class="fas fa-exclamation-triangle"></i>
            <span class="menu-text">Emergency</span>
        </a>
        <a href="login.php" class="menu-item" style="margin-top: 2rem; color: #f56565;">
            <i class="fas fa-sign-out-alt"></i>
            <span class="menu-text">Logout</span>
        </a>
    </div>
</aside>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContainer = document.getElementById('mainContainer');
    
    sidebar.classList.toggle('collapsed');
    if (mainContainer) mainContainer.classList.toggle('collapsed');
}
</script>