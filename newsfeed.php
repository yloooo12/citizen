<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$first_name = $_SESSION["first_name"] ?? '';
$last_name = $_SESSION["last_name"] ?? '';

$conn = new mysqli("localhost", "root", "", "citizenproj");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

// Get announcements with like status
$user_id = $_SESSION["user_id"];
$announcements = $conn->query("SELECT a.*, u.first_name, u.last_name, 
    (SELECT COUNT(*) FROM announcement_likes WHERE announcement_id = a.id) as like_count,
    (SELECT COUNT(*) FROM announcement_likes WHERE announcement_id = a.id AND user_id = $user_id) as user_liked
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
    <title>Newsfeed - LSPU CCS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; color: #333; overflow: hidden; transition: background 0.3s ease, color 0.3s ease; }
        
        body.dark-mode {
            background: #1a202c;
            color: #e2e8f0;
        }
        
        body.dark-mode .page-header,
        body.dark-mode .announcement-card,
        body.dark-mode .empty-state,
        body.dark-mode .footer {
            background: #2d3748;
            border-color: #4a5568;
        }
        
        body.dark-mode .page-header h1,
        body.dark-mode .announcement-author,
        body.dark-mode .announcement-title,
        body.dark-mode .announcement-content,
        body.dark-mode .comment-author,
        body.dark-mode .comment-text,
        body.dark-mode .empty-state h3 {
            color: #e2e8f0;
        }
        
        body.dark-mode .announcement-role,
        body.dark-mode .announcement-time,
        body.dark-mode .action-btn,
        body.dark-mode .empty-state p,
        body.dark-mode .footer {
            color: #cbd5e0;
        }
        
        body.dark-mode .announcement-actions {
            border-top-color: #4a5568;
        }
        
        body.dark-mode .action-btn:hover {
            background: #374151;
        }
        
        body.dark-mode .comment-input input {
            background: #374151;
            border-color: #4a5568;
            color: #e2e8f0;
        }
        
        body.dark-mode .comment-input input::placeholder {
            color: #9ca3af;
        }
        
        body.dark-mode .comment-content {
            background: #374151;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .main-container {
            margin-left: 260px;
            margin-top: 65px;
            padding: 2rem;
            height: calc(100vh - 65px);
            overflow-y: auto;
            transition: all 0.3s ease;
        }

        .main-container.collapsed {
            margin-left: 70px;
        }

        .newsfeed-wrapper {
            max-width: 950px;
            margin: 0 auto;
            animation: fadeIn 0.5s ease;
        }

        .page-header {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            animation: slideIn 0.6s ease;
        }

        .page-header h1 {
            font-size: 1rem;
            font-weight: 600;
            color: #000;
            margin: 0;
        }

        .announcement-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: box-shadow 0.2s;
            animation: slideIn 0.4s ease;
        }

        .announcement-card:hover {
            box-shadow: 0 0 0 1px rgba(0,0,0,0.08), 0 2px 4px rgba(0,0,0,0.08);
        }

        .announcement-header {
            padding: 1rem 1rem 0.75rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .admin-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #0a66c2;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.125rem;
            flex-shrink: 0;
        }

        .announcement-meta {
            flex: 1;
            min-width: 0;
        }

        .announcement-author {
            font-weight: 600;
            color: #000;
            font-size: 0.875rem;
            line-height: 1.3;
        }

        .announcement-role {
            color: #666;
            font-size: 0.75rem;
            line-height: 1.3;
        }

        .announcement-time {
            color: #666;
            font-size: 0.75rem;
            margin-top: 0.125rem;
        }

        .announcement-body {
            padding: 0.5rem 1rem 1rem;
        }

        .announcement-title {
            font-size: 1rem;
            font-weight: 600;
            color: #000;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }

        .announcement-content {
            color: #000;
            line-height: 1.5;
            font-size: 0.875rem;
        }

        .announcement-actions {
            padding: 0.5rem 1rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            flex: 1;
            padding: 0.625rem;
            background: transparent;
            border: none;
            color: #666;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: background 0.2s;
        }

        .action-btn:hover {
            background: #f3f4f6;
        }

        .action-btn.liked {
            color: #0a66c2;
        }

        .action-btn i {
            font-size: 1rem;
        }

        .comments-section {
            padding: 0 1rem 1rem;
            display: none;
        }

        .comments-section.show {
            display: block;
        }

        .comment-input {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .comment-input input {
            flex: 1;
            padding: 0.625rem;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            font-size: 0.875rem;
            outline: none;
        }

        .comment-input input:focus {
            border-color: #0a66c2;
        }

        .comment-item {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .comment-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #0a66c2;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.75rem;
            font-weight: 600;
            flex-shrink: 0;
        }

        .comment-content {
            flex: 1;
            background: #f3f4f6;
            padding: 0.5rem 0.75rem;
            border-radius: 12px;
        }

        .comment-author {
            font-weight: 600;
            font-size: 0.8125rem;
            color: #000;
            margin-bottom: 0.125rem;
        }

        .comment-text {
            font-size: 0.8125rem;
            color: #000;
            line-height: 1.4;
        }

        .announcement-media {
            margin-top: 0.75rem;
            margin-left: -1rem;
            margin-right: -1rem;
        }

        .announcement-media img {
            width: 100%;
            display: block;
        }

        .announcement-media video {
            width: 100%;
            display: block;
        }

        .empty-state {
            background: white;
            border-radius: 16px;
            padding: 4rem 2rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .empty-state i {
            font-size: 5rem;
            color: #cbd5e0;
            margin-bottom: 1.5rem;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: #718096;
        }

        .footer {
            text-align: center;
            padding: 1rem;
            color: #718096;
            font-size: 0.75rem;
            background: white;
            margin-left: 260px;
            transition: all 0.3s ease;
            border-top: 1px solid #e8ecf4;
        }

        .footer.collapsed {
            margin-left: 70px;
        }

        @media (max-width: 768px) {
            .main-container {
                margin-left: 0;
                padding: 1rem;
                margin-top: 55px;
            }

            .footer {
                margin-left: 0;
            }

            .newsfeed-wrapper {
                max-width: 100%;
            }

            .page-header {
                padding: 1.5rem;
            }

            .page-header h1 {
                font-size: 1.25rem;
            }

            .announcement-card {
                margin-bottom: 1rem;
            }

            .announcement-header {
                padding: 1rem;
            }

            .announcement-body {
                padding: 1rem;
            }

            .announcement-title {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main class="main-container" id="mainContainer">
        <div class="newsfeed-wrapper">
            <div class="page-header">
                <h1>Recent Announcements</h1>
            </div>

            <?php if ($announcements && $announcements->num_rows > 0): ?>
                <?php while($post = $announcements->fetch_assoc()): ?>
                <div class="announcement-card">
                    <div class="announcement-header">
                        <div class="admin-avatar">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div class="announcement-meta">
                            <div class="announcement-author">
                                <?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); ?>
                            </div>
                            <div class="announcement-role">LSPU-CCS Admin</div>
                            <div class="announcement-time">
                                <i class="far fa-clock"></i>
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
                        <div class="announcement-content"><?php echo $post['content']; ?>
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
                    <div class="announcement-actions">
                        <button class="action-btn <?php echo $post['user_liked'] ? 'liked' : ''; ?>" onclick="toggleLike(<?php echo $post['id']; ?>, this)">
                            <i class="<?php echo $post['user_liked'] ? 'fas' : 'far'; ?> fa-thumbs-up"></i>
                            <span>Like</span>
                            <?php if ($post['like_count'] > 0): ?>
                                <span class="count">(<?php echo $post['like_count']; ?>)</span>
                            <?php endif; ?>
                        </button>
                        <button class="action-btn" onclick="toggleComments(<?php echo $post['id']; ?>)">
                            <i class="far fa-comment"></i>
                            <span>Comment</span>
                        </button>
                    </div>
                    <div class="comments-section" id="comments-<?php echo $post['id']; ?>">
                        <div class="comment-input">
                            <input type="text" placeholder="Write a comment..." id="comment-input-<?php echo $post['id']; ?>" onkeypress="if(event.key==='Enter') postComment(<?php echo $post['id']; ?>)">
                        </div>
                        <div id="comments-list-<?php echo $post['id']; ?>"></div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-bullhorn"></i>
                    <h3>No Announcements Yet</h3>
                    <p>Check back later for updates and announcements from LSPU-CCS</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer" id="footer">
        <p>&copy; 2024 Laguna State Polytechnic University - Department of Computer Studies</p>
        <p>INTEGRITY • PROFESSIONALISM • INNOVATION</p>
    </footer>

    <?php include 'chatbot.php'; ?>

    <script>
    // Define functions first
    window.toggleLike = function(postId, btn) {
        console.log('toggleLike called for post:', postId);
        fetch('announcement_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=toggle_like&announcement_id=' + postId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const icon = btn.querySelector('i');
                if (data.liked) {
                    btn.classList.add('liked');
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                } else {
                    btn.classList.remove('liked');
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                }
                
                let countSpan = btn.querySelector('.count');
                if (data.count > 0) {
                    if (!countSpan) {
                        countSpan = document.createElement('span');
                        countSpan.className = 'count';
                        btn.appendChild(countSpan);
                    }
                    countSpan.textContent = '(' + data.count + ')';
                } else if (countSpan) {
                    countSpan.remove();
                }
            }
        });
    }

    window.toggleComments = function(postId) {
        const commentsSection = document.getElementById('comments-' + postId);
        commentsSection.classList.toggle('show');
        if (commentsSection.classList.contains('show')) {
            document.getElementById('comment-input-' + postId).focus();
        }
    }

    window.postComment = function(postId) {
        const input = document.getElementById('comment-input-' + postId);
        const commentText = input.value.trim();
        if (!commentText) return;

        fetch('announcement_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=post_comment&announcement_id=' + postId + '&comment_text=' + encodeURIComponent(commentText)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const commentsList = document.getElementById('comments-list-' + postId);
                const commentHTML = `
                    <div class="comment-item">
                        <div class="comment-avatar">${data.comment.avatar}</div>
                        <div class="comment-content">
                            <div class="comment-author">${data.comment.author}</div>
                            <div class="comment-text">${data.comment.text}</div>
                        </div>
                    </div>
                `;
                commentsList.insertAdjacentHTML('beforeend', commentHTML);
                input.value = '';
            }
        });
    }

    window.loadComments = function(postId) {
        fetch('announcement_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=get_comments&announcement_id=' + postId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.comments.length > 0) {
                const commentsList = document.getElementById('comments-list-' + postId);
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

    // Load all comments on page load
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[id^="comments-list-"]').forEach(function(el) {
            const postId = el.id.replace('comments-list-', '');
            loadComments(postId);
        });
    });

    window.toggleSidebar = function() {
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

    const sidebarEl = document.getElementById('sidebar');
    const mainContainerEl = document.getElementById('mainContainer');
    const footerEl = document.getElementById('footer');

    if (sidebarEl && mainContainerEl) {
        sidebarEl.addEventListener('mouseenter', function() {
            if (window.innerWidth > 768 && sidebarEl.classList.contains('collapsed')) {
                mainContainerEl.style.marginLeft = '260px';
                if (footerEl) footerEl.style.marginLeft = '260px';
            }
        });

        sidebarEl.addEventListener('mouseleave', function() {
            if (window.innerWidth > 768 && sidebarEl.classList.contains('collapsed')) {
                mainContainerEl.style.marginLeft = '70px';
                if (footerEl) footerEl.style.marginLeft = '70px';
            }
        });
    }

    document.addEventListener('click', function(event) {
        const toggleBtn = document.querySelector('.toggle-btn');
        
        if (window.innerWidth <= 768 && sidebarEl && sidebarEl.classList.contains('show')) {
            if (!sidebarEl.contains(event.target) && !toggleBtn.contains(event.target)) {
                sidebarEl.classList.remove('show');
            }
        }
    });

    </script>
</body>
</html>
<?php $conn->close(); ?>
