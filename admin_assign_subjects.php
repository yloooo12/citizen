<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "student_services");
$message = "";
$error = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_id = $_POST['teacher_id'];
    $subjects = $_POST['subjects'] ?? [];
    
    if ($teacher_id && !empty($subjects)) {
        // Get existing subjects for this teacher
        $existing = [];
        $result = $conn->query("SELECT DISTINCT subject_code FROM student_subjects WHERE teacher_id = '$teacher_id'");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $existing[] = $row['subject_code'];
            }
        }
        
        // Add new subjects
        $added = 0;
        foreach ($subjects as $subject) {
            if (!in_array($subject, $existing)) {
                // Copy students from existing subject assignment
                $conn->query("INSERT INTO student_subjects (student_id, subject_code, teacher_id) 
                    SELECT student_id, '$subject', '$teacher_id' 
                    FROM student_subjects 
                    WHERE teacher_id = '$teacher_id' 
                    GROUP BY student_id");
                $added++;
            }
        }
        
        $message = "✅ Successfully assigned $added new subject(s) to teacher!";
    } else {
        $error = "❌ Please select teacher and at least one subject.";
    }
}

// Get all teachers
$teachers = [];
$result = $conn->query("SELECT id_number, first_name, last_name FROM users WHERE user_type = 'teacher' ORDER BY last_name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $teachers[] = $row;
    }
}

// Get all subjects
$subjects = [];
$result = $conn->query("SELECT DISTINCT subject_code FROM student_subjects ORDER BY subject_code");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row['subject_code'];
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Subjects to Teachers - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; padding: 2rem; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 2.5rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2d3748; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem; }
        .subtitle { color: #718096; margin-bottom: 2rem; }
        .alert { padding: 1rem 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; }
        .alert-success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .alert-error { background: #fee2e2; color: #b91c1c; border-left: 4px solid #ef4444; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-weight: 600; color: #2d3748; margin-bottom: 0.5rem; }
        .form-group select { width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 1rem; }
        .subjects-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.75rem; }
        .subject-checkbox { display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; background: #f9fafb; border-radius: 8px; border: 2px solid #e2e8f0; cursor: pointer; transition: all 0.3s; }
        .subject-checkbox:hover { border-color: #667eea; background: #f0f4ff; }
        .subject-checkbox input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; }
        .subject-checkbox input[type="checkbox"]:checked + label { color: #667eea; font-weight: 600; }
        .subject-checkbox label { cursor: pointer; flex: 1; }
        .btn { background: #667eea; color: white; border: none; padding: 0.875rem 2rem; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 1rem; transition: all 0.3s; }
        .btn:hover { background: #5568d3; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102,126,234,0.3); }
        .back-link { display: inline-flex; align-items: center; gap: 0.5rem; color: #667eea; text-decoration: none; margin-top: 2rem; font-weight: 500; }
        .back-link:hover { color: #5568d3; }
        .current-subjects { background: #f0f4ff; padding: 1rem; border-radius: 8px; margin-top: 1rem; }
        .current-subjects h4 { color: #667eea; margin-bottom: 0.5rem; }
        .current-subjects ul { list-style: none; padding: 0; }
        .current-subjects li { padding: 0.5rem; background: white; margin-bottom: 0.25rem; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-chalkboard-teacher"></i> Assign Subjects to Teachers</h1>
        <p class="subtitle">Assign multiple subjects to teachers for handling different classes</p>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle" style="font-size: 1.5rem;"></i>
                <span><?php echo $message; ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle" style="font-size: 1.5rem;"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label><i class="fas fa-user-tie"></i> Select Teacher:</label>
                <select name="teacher_id" id="teacherSelect" required onchange="loadCurrentSubjects(this.value)">
                    <option value="">-- Choose Teacher --</option>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?php echo $teacher['id_number']; ?>">
                            <?php echo $teacher['last_name'] . ', ' . $teacher['first_name'] . ' (' . $teacher['id_number'] . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div id="currentSubjects" style="display: none;" class="current-subjects">
                <h4><i class="fas fa-list"></i> Currently Assigned Subjects:</h4>
                <ul id="currentSubjectsList"></ul>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-book"></i> Select Subjects to Assign:</label>
                <div class="subjects-grid">
                    <?php foreach ($subjects as $subject): ?>
                        <div class="subject-checkbox">
                            <input type="checkbox" name="subjects[]" value="<?php echo $subject; ?>" id="subj_<?php echo $subject; ?>">
                            <label for="subj_<?php echo $subject; ?>"><?php echo $subject; ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-plus-circle"></i> Assign Subjects
            </button>
        </form>
        
        <a href="dean_student_upload.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Admin Dashboard
        </a>
    </div>

    <script>
        function loadCurrentSubjects(teacherId) {
            if (!teacherId) {
                document.getElementById('currentSubjects').style.display = 'none';
                return;
            }
            
            fetch('get_teacher_subjects.php?teacher_id=' + teacherId)
                .then(response => response.json())
                .then(data => {
                    const list = document.getElementById('currentSubjectsList');
                    list.innerHTML = '';
                    
                    if (data.subjects && data.subjects.length > 0) {
                        data.subjects.forEach(subject => {
                            const li = document.createElement('li');
                            li.innerHTML = '<i class="fas fa-check-circle" style="color: #10b981;"></i> ' + subject;
                            list.appendChild(li);
                        });
                        document.getElementById('currentSubjects').style.display = 'block';
                    } else {
                        list.innerHTML = '<li style="color: #718096;"><i class="fas fa-info-circle"></i> No subjects assigned yet</li>';
                        document.getElementById('currentSubjects').style.display = 'block';
                    }
                });
        }
    </script>
</body>
</html>
