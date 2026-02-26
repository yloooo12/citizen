<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "citizenproj");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Get announcements with stats
$announcements = $conn->query("SELECT a.*, u.first_name, u.last_name, 
    (SELECT COUNT(*) FROM announcement_likes WHERE announcement_id = a.id) as like_count,
    (SELECT COUNT(*) FROM announcement_comments WHERE announcement_id = a.id) as comment_count
    FROM announcements a 
    LEFT JOIN users u ON a.admin_id = u.id 
    ORDER BY a.created_at DESC");

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Announcements - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; color: #333; }
        .header { background: #667eea; padding: 1rem 2rem; position: fixed; top: 0; left: 0; right: 0; z-index: 100; box-shadow: 0 2px 15px rgba(102, 126, 234, 0.25); }
        .sidebar { position: fixed; left: 0; top: 65px; width: 260px; height: calc(100vh - 65px); background: white; box-shadow: 4px 0 25px rgba(0,0,0,0.06); z-index: 99; overflow-y: auto; transition: all 0.3s ease; border-right: 1px solid #e8ecf4; }
        .sidebar.collapsed { width: 70px; }
        .sidebar-menu { padding: 1.5rem 0; }
        .sidebar-menu a { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; margin: 0.25rem 0.75rem; color: #4a5568; text-decoration: none; transition: all 0.3s ease; font-size: 0.95rem; font-weight: 500; border-radius: 12px; }
        .sidebar-menu a:hover { background: #f0f4ff; color: #667eea; }
        .sidebar-menu a.active { background: #667eea; color: white; font-weight: 600; }
        .sidebar-menu a i { width: 22px; text-align: center; font-size: 1.1rem; }
        .main-container { margin-left: 260px; margin-top: 85px; padding: 2rem; transition: margin-left 0.3s ease; }
        .main-container.collapsed { margin-left: 70px; }
        .page-header { background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; }
        .page-header h1 { font-size: 1.25rem; font-weight: 600; color: #000; }
        .announcement-card { background: white; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 1rem; }
        .announcement-header { padding: 1rem; display: flex; align-items: flex-start; gap: 0.75rem; border-bottom: 1px solid #e5e7eb; }
        .admin-avatar { width: 48px; height: 48px; border-radius: 50%; background: #0a66c2; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 1.125rem; }
        .announcement-meta { flex: 1; }
        .announcement-author { font-weight: 600; color: #000; font-size: 0.875rem; }
        .announcement-role { color: #666; font-size: 0.75rem; }
        .announcement-time { color: #666; font-size: 0.75rem; margin-top: 0.125rem; }
        .announcement-body { padding: 1rem; }
        .announcement-title { font-size: 1rem; font-weight: 600; color: #000; margin-bottom: 0.5rem; }
        .announcement-content { color: #000; line-height: 1.5; font-size: 0.875rem; }
        .announcement-media { margin-top: 0.75rem; }
        .announcement-media img, .announcement-media video { width: 100%; display: block; }
        .announcement-stats { padding: 0.75rem 1rem; border-top: 1px solid #e5e7eb; display: flex; gap: 1.5rem; color: #666; font-size: 0.875rem; }
        .stat-item { display: flex; align-items: center; gap: 0.5rem; }
        .stat-item i { color: #0a66c2; }
        .comments-section { padding: 0 1rem 1rem; border-top: 1px solid #e5e7eb; }
        .view-comments-btn { background: transparent; border: none; color: #0a66c2; font-size: 0.875rem; font-weight: 600; cursor: pointer; padding: 0.75rem 1rem; width: 100%; text-align: left; }
        .view-comments-btn:hover { background: #f3f4f6; }
        .comments-list { padding: 1rem; display: none; }
        .comments-list.show { display: block; }
        .comment-item { display: flex; gap: 0.5rem; margin-bottom: 0.75rem; }
        .comment-avatar { width: 32px; height: 32px; border-radius: 50%; background: #0a66c2; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.75rem; font-weight: 600; }
        .comment-content { flex: 1; background: #f3f4f6; padding: 0.5rem 0.75rem; border-radius: 12px; }
        .comment-author { font-weight: 600; font-size: 0.8125rem; color: #000; }
        .comment-text { font-size: 0.8125rem; color: #000; line-height: 1.4; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .main-container { margin-left: 0; margin-top: 55px; padding: 1rem; }
        }
    </style>
</head>
<body>
    <?php $page_title = 'View Announcements'; include 'admin_header.php'; ?>

    <aside class="sidebar" id="sidebar">
        <nav class="sidebar-menu">
            <a href="admin_dashboard.php"><i class="fas fa-th-large"></i><span>Dashboard</span></a>
            <a href="admin_inc.php"><i class="fas fa-file-alt"></i><span>INC Requests</span></a>
            <a href="admin_crediting.php"><i class="fas fa-graduation-cap"></i><span>Crediting Requests</span></a>
            <a href="admin_students.php"><i class="fas fa-users"></i><span>Students</span></a>
            <a href="admin_notification.php"><i class="fas fa-bell"></i><span>Notifications</span></a>
            <a href="post_announcement.php"><i class="fas fa-bullhorn"></i><span>Post Announcement</span></a>
            <a href="admin_announcements.php" class="active"><i class="fas fa-eye"></i><span>View Announcements</span></a>
            <a href="admin_upload_grades.php"><i class="fas fa-upload"></i><span>Upload Grades</span></a>
        </nav>
    </aside>

    <main class="main-container" id="mainContainer">
        <div class="page-header">
            <h1>All Announcements</h1>
        </div>

        <?php if ($announcements && $announcements->num_rows > 0): ?>
            <?php while($post = $announcements->fetch_assoc()): ?>
            <div class="announcement-card">
                <div class="announcement-header">
                    <div class="admin-avatar"><i class="fas fa-user-shield"></i></div>
                    <div class="announcement-meta">
                        <div class="announcement-author"><?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); ?></div>
                        <div class="announcement-role">LSPU-CCS Admin</div>
                        <div class="announcement-time">
                            <?php 
                                $time = strtotime($post['created_at']);
                                $diff = time() - $time;
                                if ($diff < 60) echo 'Just now';
                                elseif ($diff < 3600) echo floor($diff/60) . ' minutes ago';
                                elseif ($diff < 86400) echo floor($diff/3600) . ' hours ago';
                                else echo date('M d, Y - h:i A', $time);
                            ?>
                        </div>
                    </div>
                </div>
                <div class="announcement-body">
                    <div class="announcement-title"><?php echo $post['title']; ?></div>
                    <div class="announcement-content"><?php echo $post['content']; ?></div>
                    <?php if ($post['media_path']): ?>
                    <div class="announcement-media">
                        <?php if ($post['media_type'] == 'image'): ?>
                            <img src="<?php echo htmlspecialchars($post['media_path']); ?>" alt="Announcement media">
                        <?php elseif ($post['media_type'] == 'video'): ?>
                            <video controls>
                                <source src="<?php echo htmlspecialchars($post['media_path']); ?>" type="video/mp4">
                            </video>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="announcement-stats">
                    <div class="stat-item">
                        <i class="fas fa-thumbs-up"></i>
                        <span><?php echo $post['like_count']; ?> Likes</span>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-comment"></i>
                        <span><?php echo $post['comment_count']; ?> Comments</span>
                    </div>
                </div>
                <?php if ($post['comment_count'] > 0): ?>
                <div class="comments-section">
                    <button class="view-comments-btn" onclick="toggleComments(<?php echo $post['id']; ?>)">
                        View all <?php echo $post['comment_count']; ?> comments
                    </button>
                    <div class="comments-list" id="comments-<?php echo $post['id']; ?>"></div>
                </div>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="announcement-card">
                <div class="announcement-body" style="text-align:center; padding:2rem; color:#666;">
                    No announcements yet
                </div>
            </div>
        <?php endif; ?>
    </main>

    <?php include 'admin_logout_modal.php'; ?>

    <script>
        function toggleComments(postId) {
            const commentsList = document.getElementById('comments-' + postId);
            if (!commentsList.classList.contains('show')) {
                loadComments(postId);
            }
            commentsList.classList.toggle('show');
        }

        function loadComments(postId) {
            fetch('announcement_actions.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=get_comments&announcement_id=' + postId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.comments.length > 0) {
                    const commentsList = document.getElementById('comments-' + postId);
                    commentsList.innerHTML = '';
                    data.comments.forEach(comment => {
                        const commentHTML = `
                            <div class="comment-item">
                                <div class="comment-avatar">${comment.avatar}</div>
                                <div class="comment-content">
                                    <div class="comment-author">${comment.author}</div>
                                    <div class="comment-text">${comment.text}</div>
                                </div>
                            </div>
                        `;
                        commentsList.insertAdjacentHTML('beforeend', commentHTML);
                    });
                }
            });
        }

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
    </script>
</body>
</html>
