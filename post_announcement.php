<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "citizenproj";
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Only allow admin
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: login.php");
    exit;
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? "");
    $content = trim($_POST["content"] ?? "");
    $admin_id = $_SESSION["user_id"] ?? 0;
    $media_type = null;
    $media_path = null;

    // Check file upload errors
    if (isset($_FILES['media'])) {
        if ($_FILES['media']['error'] !== UPLOAD_ERR_OK && $_FILES['media']['error'] !== UPLOAD_ERR_NO_FILE) {
            $error = "File upload error: " . $_FILES['media']['error'];
        }
    }

    // Handle file upload
    if (!$error && isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['media']['tmp_name'];
        $fileName = basename($_FILES['media']['name']);
        $fileType = mime_content_type($fileTmp);
        $allowed = ['image/jpeg','image/png','image/gif','image/webp','video/mp4','video/webm','video/ogg'];
        if (in_array($fileType, $allowed)) {
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            if (!is_dir('uploads')) mkdir('uploads');
            $newName = 'uploads/announcement_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            if (move_uploaded_file($fileTmp, $newName)) {
                $media_type = strpos($fileType, 'image') === 0 ? 'image' : 'video';
                $media_path = $newName;
            } else {
                $error = "Failed to move uploaded file.";
            }
        } else {
            $error = "Invalid file type.";
        }
    }

    if ($title && $content && !$error) {
        $stmt = $conn->prepare("INSERT INTO announcements (title, content, admin_id, media_type, media_path) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiss", $title, $content, $admin_id, $media_type, $media_path);
        $stmt->execute();
        if ($stmt->error) {
            die("Database error: " . $stmt->error);
        }
        $new_id = $conn->insert_id;
        $stmt->close();
        
        // Send notifications to all students
        require_once __DIR__ . '/PHPMailer/src/Exception.php';
        require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/PHPMailer/src/SMTP.php';
        
        // Send notifications to all students (exclude admin accounts)
        $title_plain = strip_tags($title);
        $content_plain = strip_tags($content);
        $notif_msg = "New announcement: $title_plain";
        $students = $conn->query("SELECT id, id_number, email FROM users WHERE id_number NOT IN ('999', '246') AND status='approved'");
        
        if ($students && $students->num_rows > 0) {
            while ($student = $students->fetch_assoc()) {
                // Portal notification
                $notif_stmt = $conn->prepare("INSERT INTO notifications (id_number, message, created_at) VALUES (?, ?, NOW())");
                $notif_stmt->bind_param("ss", $student['id_number'], $notif_msg);
                $notif_stmt->execute();
                $notif_stmt->close();
                
                // Email notification
                if (!empty($student['email'])) {
                    try {
                        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'ludoviceylo26@gmail.com';
                        $mail->Password = 'xdnt znus npyg bxuq';
                        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;
                        $mail->setFrom('ludoviceylo26@gmail.com', 'LSPU-CCS Admin');
                        $mail->addAddress($student['email']);
                        $mail->isHTML(false);
                        $mail->Subject = 'New Announcement: ' . $title_plain;
                        $mail->Body = "New announcement from LSPU-CCS:\n\n$title_plain\n\n$content_plain\n\nView full announcement at the portal.";
                        $mail->send();
                    } catch (Exception $e) {
                        error_log("Email error: " . $e->getMessage());
                    }
                }
            }
        }
        
        $_SESSION['announcement_posted'] = true;
        header("Location: post_announcement.php");
        exit;
    } elseif (!$error) {
        $error = "Title and content are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Announcement - LSPU CCS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; color: #333; }
        .header { background: #667eea; padding: 1rem 2rem; position: fixed; top: 0; left: 0; right: 0; z-index: 100; box-shadow: 0 2px 15px rgba(102, 126, 234, 0.25); }
        .nav-container { display: flex; justify-content: space-between; align-items: center; }
        .logo-section { display: flex; align-items: center; gap: 1rem; }
        .logo-img { width: 45px; height: 45px; border-radius: 10px; object-fit: cover; box-shadow: 0 4px 12px rgba(0,0,0,0.15); border: 2px solid rgba(255,255,255,0.2); }
        .logo-text h1 { font-size: 1.1rem; font-weight: 700; color: white; line-height: 1.2; }
        .logo-text p { font-size: 0.75rem; color: rgba(255,255,255,0.9); }
        .user-section { display: flex; align-items: center; gap: 1rem; }
        .welcome-text { font-weight: 600; color: white; font-size: 0.9rem; }
        .logout-btn { background: rgba(255,255,255,0.15); color: white; border: 1.5px solid rgba(255,255,255,0.3); padding: 0.5rem 1.2rem; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; font-size: 0.85rem; text-decoration: none; display: inline-block; }
        .logout-btn:hover { background: rgba(255,255,255,0.25); transform: translateY(-1px); }
        .toggle-btn { background: rgba(255,255,255,0.15); border: none; color: white; font-size: 1.25rem; cursor: pointer; padding: 0.6rem; margin-right: 1rem; border-radius: 8px; }
        .sidebar { position: fixed; left: 0; top: 65px; width: 260px; height: calc(100vh - 65px); background: white; box-shadow: 4px 0 25px rgba(0,0,0,0.06); z-index: 99; overflow-y: auto; transition: all 0.3s ease; border-right: 1px solid #e8ecf4; }
        .sidebar.collapsed { width: 70px; }
        .sidebar.collapsed .sidebar-menu a span { display: none; }
        .sidebar.collapsed .sidebar-menu a { justify-content: center; padding: 1rem; }
        .sidebar.collapsed:hover { width: 260px; }
        .sidebar.collapsed:hover .sidebar-menu a span { display: inline; }
        .sidebar.collapsed:hover .sidebar-menu a { justify-content: flex-start; padding: 1rem 1.5rem; }
        .sidebar-menu { padding: 1.5rem 0; }
        .sidebar-menu a { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; margin: 0.25rem 0.75rem; color: #4a5568; text-decoration: none; transition: all 0.3s ease; font-size: 0.95rem; font-weight: 500; border-radius: 12px; }
        .sidebar-menu a:hover { background: #f0f4ff; color: #667eea; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15); }
        .sidebar-menu a.active { background: #667eea; color: white; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); font-weight: 600; }
        .sidebar-menu a i { width: 22px; text-align: center; font-size: 1.1rem; }
        .main-container { margin-left: 260px; margin-top: 85px; padding: 2rem; transition: margin-left 0.3s ease; min-height: calc(100vh - 85px); }
        .main-container.collapsed { margin-left: 70px; }
        .post-card { background: white; border: 1px solid #d1d5db; padding: 2rem 3rem; max-width: 800px; margin: 0 auto; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
        .post-header { margin-bottom: 2rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 1rem; }
        .post-header h2 { font-size: 1.5rem; color: #1f2937; font-weight: 600; }
        .form-group { margin-bottom: 0; }
        .editor-title { width: 100%; padding: 0.5rem 0; border: none; border-bottom: 1px solid #e5e7eb; font-family: inherit; font-size: 1.125rem; font-weight: 600; color: #1f2937; background: transparent; outline: none; min-height: 2rem; }
        .editor-title:empty:before { content: attr(data-placeholder); color: #9ca3af; font-weight: 400; }
        .toolbar { display: flex; gap: 0.5rem; padding: 0.75rem 0; border-bottom: 1px solid #e5e7eb; margin-bottom: 1rem; flex-wrap: wrap; }
        .toolbar select { padding: 0.375rem 0.5rem; border: 1px solid #d1d5db; font-size: 0.875rem; color: #374151; background: white; cursor: pointer; }
        .tool-btn { padding: 0.375rem 0.625rem; border: 1px solid #d1d5db; background: white; color: #6b7280; cursor: pointer; transition: all 0.2s; font-size: 0.875rem; }
        .tool-btn:hover { background: #f3f4f6; color: #374151; }
        .tool-btn.active { background: #667eea; color: white; border-color: #667eea; }
        .editor { width: 100%; padding: 1.5rem 0; border: none; font-family: inherit; font-size: 1rem; line-height: 1.75; color: #374151; background: transparent; min-height: 300px; outline: none; }
        .editor:empty:before { content: attr(data-placeholder); color: #9ca3af; }
        .file-upload { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; border: 1px solid #d1d5db; background: white; cursor: pointer; transition: all 0.2s; font-size: 0.875rem; color: #6b7280; margin-top: 1rem; }
        .file-upload:hover { background: #f9fafb; border-color: #667eea; color: #667eea; }
        .file-upload i { font-size: 0.875rem; }
        .file-upload input { display: none; }
        .preview { margin-top: 1rem; display: none; }
        .preview img, .preview video { max-width: 100%; border: 1px solid #e5e7eb; }
        .btn-group { display: flex; justify-content: flex-end; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb; }
        .btn-post { padding: 0.625rem 2rem; font-weight: 500; cursor: pointer; font-size: 0.9rem; border: none; background: #667eea; color: white; transition: all 0.2s; }
        .btn-post:hover { background: #5568d3; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        @keyframes scaleIn { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); top: 55px; height: calc(100vh - 55px); }
            .sidebar.show { transform: translateX(0); }
            .main-container { margin-left: 0; margin-top: 55px; padding: 1rem; }
            .post-card { padding: 1.5rem; }
            .post-header h2 { font-size: 1.25rem; }
            .form-group input { font-size: 1rem; }
            .toolbar { gap: 0.375rem; padding: 0.5rem 0; }
            .toolbar select, .tool-btn { font-size: 0.8rem; padding: 0.3rem 0.5rem; }
            .editor { font-size: 0.95rem; min-height: 250px; }
            .btn-post { padding: 0.6rem 1.5rem; font-size: 0.875rem; }
        }
    </style>
</head>
<body>
    <?php $page_title = 'Post Announcement'; include 'admin_header.php'; ?>

    <aside class="sidebar" id="sidebar">
        <nav class="sidebar-menu">
            <a href="admin_dashboard.php"><i class="fas fa-th-large"></i><span>Dashboard</span></a>
            <a href="admin_inc.php"><i class="fas fa-file-alt"></i><span>INC Requests</span></a>
            <a href="admin_crediting.php"><i class="fas fa-graduation-cap"></i><span>Crediting Requests</span></a>
            <a href="admin_students.php"><i class="fas fa-users"></i><span>Students</span></a>
            <a href="admin_notification.php"><i class="fas fa-bell"></i><span>Notifications</span></a>
            <a href="post_announcement.php" class="active"><i class="fas fa-bullhorn"></i><span>Post Announcement</span></a>
            <a href="admin_announcements.php"><i class="fas fa-eye"></i><span>View Announcements</span></a>
            <a href="admin_upload_grades.php"><i class="fas fa-upload"></i><span>Upload Grades</span></a>
        </nav>
    </aside>

    <main class="main-container" id="mainContainer">
        <div class="post-card">
            <div class="post-header">
                <h2>Create Announcement</h2>
            </div>
            
            <?php if ($error): ?>
                <div class="alert"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <div class="toolbar">
                        <select id="fontSizeTitle" onchange="changeFontSize('titleEditor', 'fontSizeTitle')">
                            <option value="2rem">H1</option>
                            <option value="1.75rem">H2</option>
                            <option value="1.5rem">H3</option>
                            <option value="1.25rem">H4</option>
                            <option value="1.125rem" selected>H5</option>
                            <option value="1rem">H6</option>
                        </select>
                        <button type="button" class="tool-btn" onclick="focusEditor('titleEditor'); toggleBold()"><i class="fas fa-bold"></i></button>
                        <button type="button" class="tool-btn" onclick="focusEditor('titleEditor'); toggleItalic()"><i class="fas fa-italic"></i></button>
                        <button type="button" class="tool-btn" onclick="focusEditor('titleEditor'); toggleUnderline()"><i class="fas fa-underline"></i></button>
                        <button type="button" class="tool-btn" onclick="focusEditor('titleEditor'); setAlign('left')"><i class="fas fa-align-left"></i></button>
                        <button type="button" class="tool-btn" onclick="focusEditor('titleEditor'); setAlign('center')"><i class="fas fa-align-center"></i></button>
                        <button type="button" class="tool-btn" onclick="focusEditor('titleEditor'); setAlign('right')"><i class="fas fa-align-right"></i></button>
                        <button type="button" class="tool-btn" onclick="focusEditor('titleEditor'); insertLink()"><i class="fas fa-link"></i></button>
                    </div>
                    <div contenteditable="true" id="titleEditor" class="editor-title" data-placeholder="Announcement Title"></div>
                    <input type="hidden" name="title" id="titleHidden" required>
                </div>
                
                <div class="form-group">
                    <div class="toolbar">
                        <select id="fontSizeContent" onchange="changeFontSize('contentEditor', 'fontSizeContent')">
                            <option value="2rem">H1</option>
                            <option value="1.75rem">H2</option>
                            <option value="1.5rem">H3</option>
                            <option value="1.25rem">H4</option>
                            <option value="1.125rem">H5</option>
                            <option value="1rem" selected>H6</option>
                        </select>
                        <button type="button" class="tool-btn" onclick="focusEditor('contentEditor'); toggleBold()"><i class="fas fa-bold"></i></button>
                        <button type="button" class="tool-btn" onclick="focusEditor('contentEditor'); toggleItalic()"><i class="fas fa-italic"></i></button>
                        <button type="button" class="tool-btn" onclick="focusEditor('contentEditor'); toggleUnderline()"><i class="fas fa-underline"></i></button>
                        <button type="button" class="tool-btn" onclick="focusEditor('contentEditor'); setAlign('left')"><i class="fas fa-align-left"></i></button>
                        <button type="button" class="tool-btn" onclick="focusEditor('contentEditor'); setAlign('center')"><i class="fas fa-align-center"></i></button>
                        <button type="button" class="tool-btn" onclick="focusEditor('contentEditor'); setAlign('right')"><i class="fas fa-align-right"></i></button>
                        <button type="button" class="tool-btn" onclick="focusEditor('contentEditor'); insertLink()"><i class="fas fa-link"></i></button>
                    </div>
                    <div contenteditable="true" id="contentEditor" class="editor" data-placeholder="What's the announcement?"></div>
                    <textarea name="content" id="contentHidden" style="display:none;" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="media" class="file-upload">
                        <i class="fas fa-image"></i>
                        <span>Photo/Video</span>
                        <input type="file" name="media" id="media" accept="image/*,video/*" onchange="previewMedia(this)">
                    </label>
                    <div class="preview" id="preview"></div>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-post" onclick="saveContent()">Post</button>
                </div>
            </form>
        </div>
    </main>

    <div id="successModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10000; align-items:center; justify-content:center;">
        <div style="background:white; border-radius:16px; padding:2rem; max-width:400px; text-align:center; box-shadow:0 20px 60px rgba(0,0,0,0.3); animation:scaleIn 0.3s ease;">
            <div style="width:60px; height:60px; background:#10b981; border-radius:50%; margin:0 auto 1.5rem; display:flex; align-items:center; justify-content:center;">
                <i class="fas fa-check" style="font-size:2rem; color:white;"></i>
            </div>
            <h3 style="font-size:1.5rem; color:#2d3748; margin-bottom:0.5rem; font-weight:700;">Success!</h3>
            <p style="color:#718096; margin-bottom:1.5rem;">Announcement posted successfully and notifications sent to all students.</p>
            <button onclick="closeSuccessModal()" style="background:#667eea; color:white; border:none; padding:0.75rem 2rem; border-radius:8px; font-weight:600; cursor:pointer; font-size:1rem;">OK</button>
        </div>
    </div>

    <?php include 'admin_logout_modal.php'; ?>

    <script>
        <?php if (isset($_SESSION['announcement_posted'])): ?>
            document.getElementById('successModal').style.display = 'flex';
            <?php unset($_SESSION['announcement_posted']); ?>
        <?php endif; ?>

        function closeSuccessModal() {
            document.getElementById('successModal').style.display = 'none';
            window.location.href = 'admin_dashboard.php';
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

        function focusEditor(editorId) {
            document.getElementById(editorId).focus();
        }

        function changeFontSize(editorId, selectId) {
            focusEditor(editorId);
            const size = document.getElementById(selectId).value;
            document.execCommand('fontSize', false, '7');
            const fontElements = document.querySelectorAll('font[size="7"]');
            fontElements.forEach(el => {
                el.removeAttribute('size');
                el.style.fontSize = size;
            });
        }

        function toggleBold() {
            document.execCommand('bold', false, null);
        }

        function toggleItalic() {
            document.execCommand('italic', false, null);
        }

        function toggleUnderline() {
            document.execCommand('underline', false, null);
        }

        function setAlign(align) {
            document.execCommand('justify' + align.charAt(0).toUpperCase() + align.slice(1), false, null);
        }

        function insertLink() {
            const url = prompt('Enter URL:');
            if (url) {
                document.execCommand('createLink', false, url);
            }
        }

        function saveContent() {
            const titleEditor = document.getElementById('titleEditor');
            const titleHidden = document.getElementById('titleHidden');
            const contentEditor = document.getElementById('contentEditor');
            const contentHidden = document.getElementById('contentHidden');
            
            titleHidden.value = titleEditor.innerHTML;
            contentHidden.value = contentEditor.innerHTML;
        }

        function previewMedia(input) {
            const preview = document.getElementById('preview');
            preview.innerHTML = '';
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (file.type.startsWith('image/')) {
                        preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
                    } else if (file.type.startsWith('video/')) {
                        preview.innerHTML = '<video controls src="' + e.target.result + '"></video>';
                    }
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        }

        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.querySelector('.toggle-btn');
            if (window.innerWidth <= 768 && sidebar.classList.contains('show')) {
                if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    </script>
</body>
<?php include 'admin_logout_modal.php'; ?></body>
</html>
<?php $conn->close(); ?>