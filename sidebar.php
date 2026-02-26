<aside class="sidebar" id="sidebar">
    <nav class="sidebar-menu">
        <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-th-large"></i>
            <span>Dashboard</span>
        </a>
        <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
        <a href="notification.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'notification.php' ? 'active' : ''; ?>">
            <i class="fas fa-bell"></i>
            <span>Notifications</span>
        </a>
        <a href="group_index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == ' newsfeed.php' ? 'active' : ''; ?>">
            <i class="fas fa-bullhorn"></i>
            <span>Announcements</span>
        </a>
    </nav>
</aside>
