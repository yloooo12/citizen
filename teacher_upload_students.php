<?php
session_start();

// Debug
if (!isset($_SESSION['user_type'])) {
    die('No session user_type. Please login first.');
}

if ($_SESSION['user_type'] !== 'teacher') {
    die('User type is: ' . $_SESSION['user_type'] . '. Must be teacher.');
}

$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "student_services";
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

$teacher_id = $_SESSION['id_number'];
$subject = $_SESSION['assigned_lecture'];
$warnings = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    $filename = $_FILES['csv_file']['name'];
    
    $enrolled = 0;
    $errors = [];
    
    // Metadata from CSV header
    $csv_semester = '';
    $csv_program = '';
    $csv_year = '';
    $csv_section = '';
    $csv_instructor = '';
    $csv_course_code = '';
    $csv_course_title = '';
    $csv_academic_year = '';
    $csv_total_students = '';
    
    if (strpos($filename, '.doc') !== false || strpos($filename, '.html') !== false) {
        $content = file_get_contents($file);
        $dom = new DOMDocument();
        @$dom->loadHTML($content);
        
        // Extract info from document
        $subject_code = $subject;
        $subject_title = '';
        $school_year = '2025-2026';
        $semester = '1st';
        $year_level = '';
        $section = '';
        
        // Extract subject code and title (e.g., ITST306 — UX/UI and Cross Platform Applications (WMA 306))
        if (preg_match('/<center[^>]*>.*?<span>([^<]+)<\/span>/is', $content, $matches)) {
            $subject_title = trim(strip_tags($matches[1]));
            if (preg_match('/([A-Z]+\s*\d+)/', $subject_title, $code_match)) {
                $subject_code = str_replace(' ', '', $code_match[1]);
            }
        }
        
        // Extract school year and semester (e.g., A.Y. 2025-2026 First (1st) Semester)
        if (preg_match('/A\.Y\.\s*(\d{4}-\d{4})\s*(?:First|Second|Intersem)\s*\((\d+(?:st|nd|rd|th))\)/i', $content, $matches)) {
            $school_year = $matches[1];
            $semester = $matches[2];
        }
        
        // Extract year level (e.g., Fourth Year)
        if (preg_match('/Year.*?(First|Second|Third|Fourth)\s*Year/i', $content, $matches)) {
            $year_map = ['First' => '1st', 'Second' => '2nd', 'Third' => '3rd', 'Fourth' => '4th'];
            $year_level = $year_map[$matches[1]] . ' Year';
        }
        
        // Extract section (e.g., 4C)
        if (preg_match('/Section.*?&nbsp;\s*([A-Z0-9\s\-]+)/i', $content, $matches)) {
            $section = trim(strip_tags($matches[1]));
        }
        
        $rows = $dom->getElementsByTagName('tr');
        
        foreach ($rows as $row) {
            $cells = $row->getElementsByTagName('td');
            if ($cells->length >= 2) {
                $id_number = trim($cells->item(0)->textContent);
                
                if (empty($id_number) || !preg_match('/^\d{4}-\d{4}$/', $id_number)) continue;
                
                $check = $conn->prepare("SELECT id_number FROM users WHERE id_number = ? AND user_type = 'student'");
                $check->bind_param("s", $id_number);
                $check->execute();
                $result = $check->get_result();
                
                if ($result->num_rows > 0) {
                    // Check if student already enrolled in same subject with different section
                    $check_dup = $conn->prepare("SELECT section FROM student_subjects WHERE student_id = ? AND subject_code = ? AND section != ?");
                    $check_dup->bind_param("sss", $id_number, $subject_code, $section);
                    $check_dup->execute();
                    $dup_result = $check_dup->get_result();
                    
                    if ($dup_result->num_rows > 0) {
                        $existing = $dup_result->fetch_assoc();
                        $errors[] = "Student $id_number already enrolled in $subject_code (Section {$existing['section']})";
                        continue;
                    }
                    
                    // Update student info
                    if ($year_level && $section) {
                        $update = $conn->prepare("UPDATE users SET year_level = ?, semester = ?, section = ? WHERE id_number = ?");
                        $update->bind_param("ssss", $year_level, $semester, $section, $id_number);
                        $update->execute();
                    }
                    
                    // Enroll in subject
                    $stmt = $conn->prepare("INSERT INTO student_subjects (student_id, subject_code, subject_title, teacher_id, school_year, semester, year_level, section) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE teacher_id = ?, subject_title = ?");
                    $stmt->bind_param("ssssssssss", $id_number, $subject_code, $subject_title, $teacher_id, $school_year, $semester, $year_level, $section, $teacher_id, $subject_title);
                    $stmt->execute();
                    $enrolled++;
                } else {
                    $errors[] = "Student $id_number not found";
                }
            }
        }
        
        $success = "$enrolled students enrolled in $subject_code ($year_level, $semester Sem, Section $section, S.Y. $school_year)";
    } else {
        // CSV format
        if (($handle = fopen($file, "r")) !== FALSE) {
            $in_student_list = false;
            
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Parse metadata from all columns
                foreach ($data as $cell) {
                    $cell = trim($cell);
                    if (empty($cell)) continue;
                    
                    // Check if colon is in the cell
                    if (strpos($cell, ':') !== false) {
                        list($key, $value) = explode(':', $cell, 2);
                        $key = trim($key);
                        $value = trim($value);
                        
                        if (stripos($key, 'Semester') !== false && stripos($key, 'Academic') === false) {
                            $csv_semester = $value;
                        } elseif (stripos($key, 'Program') !== false || (stripos($key, 'Year') !== false && stripos($key, 'Academic') === false) || stripos($key, 'Section') !== false) {
                            // Extract program, year, section from "B. S. Information Technology 4C"
                            if (preg_match('/(\d+)([A-Z])\s*$/', $value, $matches)) {
                                $year_num = $matches[1];
                                $suffix = ($year_num == 1) ? 'st' : (($year_num == 2) ? 'nd' : (($year_num == 3) ? 'rd' : 'th'));
                                $csv_year = $year_num . $suffix . ' Year';
                                $csv_section = $matches[0];
                                $csv_program = trim(preg_replace('/(\d+[A-Z])\s*$/', '', $value));
                            }
                        } elseif (stripos($key, 'Instructor') !== false) {
                            $csv_instructor = $value;
                        } elseif (stripos($key, 'Total') !== false && stripos($key, 'Students') !== false) {
                            $csv_total_students = $value;
                        } elseif (stripos($key, 'Course Code') !== false) {
                            $csv_course_code = $value;
                        } elseif (stripos($key, 'Course Title') !== false) {
                            $csv_course_title = $value;
                        } elseif (stripos($key, 'Academic') !== false) {
                            $csv_academic_year = $value;
                        }
                    }
                    
                    // Check for year pattern like "2025 - 2026" (only if not already set)
                    if (empty($csv_academic_year) && preg_match('/^\d{4}\s*-\s*\d{4}$/', $cell)) {
                        $csv_academic_year = trim($cell);
                    }
                    
                    if (stripos($cell, 'STUDENTS') !== false) {
                        $in_student_list = true;
                    }
                }
                
                // Process student names    
                if ($in_student_list && isset($data[1]) && !empty(trim($data[1]))) {    
                    $student_name = trim($data[1]);
                    
                    // Skip if it's a number or metadata or contains quotes
                    if (is_numeric($student_name) || strlen($student_name) < 3 || strpos($student_name, '"') !== false) continue;
                    
                    // Convert "Last, First Middle" to "First Middle Last" or search both formats
                    $check = $conn->prepare("SELECT id_number FROM users WHERE (CONCAT(last_name, ', ', first_name, ' ', IFNULL(middle_name, '')) LIKE ? OR CONCAT(first_name, ' ', last_name) LIKE ?) AND user_type = 'student' LIMIT 1");
                    $search = "%$student_name%";
                    $check->bind_param("ss", $search, $search);
                    $check->execute();
                    $result = $check->get_result();
                    
                    if ($result->num_rows > 0) {
                        $student = $result->fetch_assoc();
                        $id_number = $student['id_number'];
                        
                        // Check if student already enrolled in same subject with different section
                        $check_dup = $conn->prepare("SELECT section FROM student_subjects WHERE student_id = ? AND subject_code = ? AND section != ?");
                        $check_dup->bind_param("sss", $id_number, $csv_course_code, $csv_section);
                        $check_dup->execute();
                        $dup_result = $check_dup->get_result();
                        
                        if ($dup_result->num_rows > 0) {
                            $existing = $dup_result->fetch_assoc();
                            $errors[] = "Student '$student_name' already enrolled in $csv_course_code (Section {$existing['section']})";
                            continue;
                        }
                        
                        // Update student info  
                        if ($csv_year && $csv_section) {
                            $update = $conn->prepare("UPDATE users SET year_level = ?, semester = ?, section = ? WHERE id_number = ?");
                            $update->bind_param("ssss", $csv_year, $csv_semester, $csv_section, $id_number);
                            $update->execute();
                        }
                        
                        // Enroll in subject
                        $stmt = $conn->prepare("INSERT INTO student_subjects (student_id, subject_code, subject_title, program, year_level, section, teacher_id, school_year, semester) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE subject_title = ?, program = ?, school_year = ?");
                        $stmt->bind_param("ssssssssssss", $id_number, $csv_course_code, $csv_course_title, $csv_program, $csv_year, $csv_section, $teacher_id, $csv_academic_year, $csv_semester, $csv_course_title, $csv_program, $csv_academic_year);
                        $stmt->execute();
                        $enrolled++;
                    } else {
                        $errors[] = "Student '$student_name' not found";
                    }
                }
            }
            fclose($handle);
        }
    }
    
    if (!isset($success)) {
        $success = "$enrolled students enrolled";
    }
    if (count($errors) > 0) {
        foreach ($errors as $err) {
            if (strpos($err, 'already enrolled') !== false) {
                $warnings[] = $err;
            } else {
                $error_msg = isset($error_msg) ? $error_msg . '<br>' . $err : $err;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload Student List</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8fafc; }
        .upload-card { background: white; padding: 3rem; border-radius: 16px; max-width: 900px; margin: 0 auto; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        h1 { color: #2d3748; margin-bottom: 0.75rem; font-size: 2.25rem; }
        h1 i { color: #667eea; margin-right: 0.75rem; }
        .subject-info { color: #718096; margin-bottom: 2.5rem; font-size: 1.1rem; }
        .subject-info strong { color: #4a5568; }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 1rem; font-weight: 600; color: #4a5568; font-size: 1.1rem; }
        input[type="file"] { width: 100%; padding: 1.5rem; border: 3px dashed #cbd5e0; border-radius: 10px; background: #f7fafc; cursor: pointer; transition: all 0.2s; font-size: 1rem; }
        input[type="file"]:hover { border-color: #667eea; background: #edf2f7; }
        .btn { background: #667eea; color: white; padding: 1.25rem 2rem; border: none; border-radius: 10px; cursor: pointer; font-size: 1.15rem; font-weight: 600; transition: all 0.2s; width: 100%; }
        .btn:hover { background: #5568d3; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); }
        .success { background: #f0fdf4; border-left: 5px solid #22c55e; color: #166534; padding: 1.25rem 1.5rem; border-radius: 10px; margin-bottom: 1.5rem; font-size: 1.05rem; }
        .success i { color: #22c55e; margin-right: 0.75rem; font-size: 1.2rem; }
        .warning { background: #fffbeb; border-left: 5px solid #f59e0b; color: #92400e; padding: 1.25rem 1.5rem; border-radius: 10px; margin-bottom: 1.5rem; font-size: 1.05rem; }
        .warning i { color: #f59e0b; margin-right: 0.75rem; font-size: 1.2rem; }
        .error { background: #fef2f2; border-left: 5px solid #ef4444; color: #991b1b; padding: 1.25rem 1.5rem; border-radius: 10px; margin-bottom: 1.5rem; font-size: 1.05rem; }
        .error i { color: #ef4444; margin-right: 0.75rem; font-size: 1.2rem; }
        .info { background: #f8fafc; border: 1px solid #e2e8f0; padding: 1.5rem; border-radius: 10px; margin-top: 2.5rem; font-size: 1rem; }
        .info strong { color: #2d3748; display: block; margin-bottom: 0.75rem; font-size: 1.1rem; }
        .info small { color: #64748b; line-height: 1.7; font-size: 0.95rem; }
    </style>
</head>
<body>
    <?php include 'teacher_navbar.php'; ?>
    <div class="main-container" id="mainContainer">
        <div class="upload-card">
            <h1><i class="fas fa-upload"></i>Upload Student List</h1>
            <p class="subject-info">Subject: <strong><?php echo $subject; ?></strong></p>
            
            <?php if (isset($success)): ?>
                <div class="success"><i class="fas fa-check-circle"></i><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (count($warnings) > 0): ?>
                <div class="warning"><i class="fas fa-exclamation-triangle"></i><strong>Already Enrolled:</strong><br><?php echo implode('<br>', $warnings); ?></div>
            <?php endif; ?>
            <?php if (isset($error_msg)): ?>
                <div class="error"><i class="fas fa-exclamation-circle"></i><?php echo $error_msg; ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Upload Class List (CSV or DOC)</label>
                    <input type="file" name="csv_file" accept=".csv,.doc,.html" required>
                </div>
                <button type="submit" class="btn"><i class="fas fa-upload"></i> Upload Students</button>
            </form>
            
            <div class="info">
                <strong>Supported Formats:</strong><br>
                • CSV: ID NUMBER, NAME<br>
                • DOC/HTML: Official Class List from registrar<br>
                <small style="color: #718096; margin-top: 0.5rem; display: block;">System will automatically detect student ID numbers and enroll them.</small>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
