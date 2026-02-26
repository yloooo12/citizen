<?php
session_start();
if (!isset($_SESSION["is_teacher"]) || $_SESSION["is_teacher"] !== true) {
    header("Location: login.php");
    exit;
}

$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "student_services";
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

$teacher_id = $_SESSION['id_number'] ?? '';

// Debug: Check teacher_id
echo "<!-- Teacher ID: " . $teacher_id . " -->";

// Get all subjects handled by this teacher (only subjects with non-archived students)
$teacher_subjects = [];
if ($teacher_id) {
    $subjects_result = $conn->query("SELECT DISTINCT subject_code FROM student_subjects WHERE teacher_id = '$teacher_id' AND (archived IS NULL OR archived = 0) ORDER BY subject_code");
    if ($subjects_result) {
        while ($row = $subjects_result->fetch_assoc()) {
            $teacher_subjects[] = $row['subject_code'];
        }
    }
}

// Get selected subject from GET parameter or use first subject
$subject = $_GET['subject'] ?? ($teacher_subjects[0] ?? $_SESSION['assigned_lecture'] ?? '');
$section = $_GET['section'] ?? '';
$semester = $_GET['semester'] ?? '';
$school_year = $_GET['school_year'] ?? '';

// Decode URL-encoded parameters
$subject = urldecode($subject);
$section = urldecode($section);
$semester = urldecode($semester);
$school_year = urldecode($school_year);

$students = [];
$seen = [];
if ($teacher_id && $subject) {
    $query = "SELECT u.id_number, u.first_name, u.last_name, ss.section, ss.year_level, ss.semester, ss.school_year 
              FROM users u 
              INNER JOIN student_subjects ss ON u.id_number = ss.student_id 
              WHERE ss.teacher_id = '$teacher_id' AND ss.subject_code = '$subject' 
              AND (ss.archived IS NULL OR ss.archived = 0)";
    
    // Add filters if provided
    if ($section) $query .= " AND ss.section = '$section'";
    if ($semester) $query .= " AND ss.semester = '$semester'";
    if ($school_year) $query .= " AND ss.school_year = '$school_year'";
    
    $query .= " ORDER BY ss.section ASC, u.last_name ASC";
    
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if (!isset($seen[$row['id_number']])) {
                $students[] = $row;
                $seen[$row['id_number']] = true;
            }
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Record - Teacher Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .main-container { margin-left: 260px; margin-top: 65px; padding: 2rem; transition: margin-left 0.3s ease; }
        .main-container.collapsed { margin-left: 70px; }
        @media (max-width: 768px) {
            .main-container { margin-left: 0 !important; }
        }
        .header-info { background: #667eea; color: white; padding: 2rem; border-radius: 16px; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .header-info p { opacity: 0.95; margin-top: 0.5rem; }
        .controls { background: white; padding: 1.25rem; border-radius: 12px; margin-bottom: 1.5rem; display: flex; gap: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .btn { background: #10b981; color: white; border: none; padding: 0.625rem 1.25rem; border-radius: 8px; cursor: pointer; font-weight: 500; transition: all 0.2s; }
        .btn:hover { background: #059669; transform: translateY(-1px); }
        .btn-save { background: #667eea; margin-left: auto; }
        .btn-save:hover { background: #5568d3; }
        .grades-table { background: white; border-radius: 12px; overflow-x: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
        th, td { padding: 0.5rem; text-align: center; border: 1px solid #f1f5f9; }
        th { background: #f8fafc; color: #475569; font-weight: 600; position: sticky; top: 0; white-space: nowrap; font-size: 0.75rem; border-bottom: 2px solid #e2e8f0; }
        td input { width: 50px; padding: 0.4rem; border: 1px solid #e2e8f0; border-radius: 6px; text-align: center; font-size: 0.8rem; transition: all 0.2s; }
        td input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .student-name { text-align: left; font-weight: 600; min-width: 180px; background: #fafbfc; position: sticky; left: 0; z-index: 10; }
        th.student-name { background: #f1f5f9; color: #1e293b; position: sticky; left: 0; z-index: 20; }
        .section-header { background: #e0e7ff; color: #4338ca; font-weight: 700; }
        .category-header { background: #f1f5f9; color: #64748b; font-weight: 600; }
        .total-col { background: #f8fafc; font-weight: 700; color: #475569; }
        th.total-col { background: #f1f5f9; color: #1e293b; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); }
        .modal-content { background: white; margin: 10% auto; padding: 2rem; border-radius: 16px; width: 500px; max-width: 90%; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        .modal-header { display: flex; justify-content: space-between; margin-bottom: 1.5rem; }
        .close { font-size: 1.5rem; cursor: pointer; color: #94a3b8; transition: color 0.2s; }
        .close:hover { color: #475569; }
        .column-input { width: 100%; padding: 0.75rem; margin-bottom: 1rem; border: 1px solid #e2e8f0; border-radius: 8px; }
        .remarks-cell { font-weight: 700; transition: all 0.2s; }
        .remarks-cell:hover { background: #f8fafc !important; }
        .remarks-cell.passed { color: #10b981; }
        .remarks-cell.failed { color: #ef4444; }
        .search-box { flex: 1; max-width: 300px; }
        .search-box input { width: 100%; padding: 0.625rem 1rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; }
        .search-box input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .loading-modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .loading-content { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 12px; text-align: center; }
        .loading-spinner { border: 4px solid #f3f3f3; border-top: 4px solid #667eea; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 1rem; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <?php include 'teacher_navbar.php'; ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const menuItems = document.querySelectorAll('.sidebar .menu-item');
        menuItems.forEach(item => {
            if (item.getAttribute('href') === 'teacher_grades.php') {
                item.classList.add('active');
            }
        });
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
    </script>
    
    <div class="main-container" id="mainContainer">
        <div class="header-info">
            <h1>CLASS RECORD</h1>
            <p style="font-size:0.8rem; opacity:0.7;">Teacher ID: <?php echo $teacher_id; ?> | Students: <?php echo count($students); ?></p>
            <p><strong>Course Code:</strong> <?php echo $subject; ?></p>
            <?php
            // Get course title and academic year from student_subjects
            $conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
            $info = $conn->query("SELECT DISTINCT subject_title, school_year FROM student_subjects WHERE subject_code = '$subject' AND teacher_id = '$teacher_id' LIMIT 1");
            if ($info && $row = $info->fetch_assoc()) {
                echo '<p><strong>Course Title:</strong> ' . $row['subject_title'] . '</p>';
                echo '<p><strong>Academic Year:</strong> ' . $row['school_year'] . '</p>';
            }
            $conn->close();
            ?>
            <?php if (count($teacher_subjects) > 1): ?>
            <div style="margin-top: 1rem;">
                <label style="font-size: 0.9rem; opacity: 0.9; display: block; margin-bottom: 0.5rem;">Select Subject:</label>
                <select onchange="window.location.href='teacher_grades.php?subject=' + this.value" style="padding: 0.75rem 1.5rem; border-radius: 8px; border: 2px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.2); color: white; font-weight: 600; font-size: 1rem; cursor: pointer;">
                    <?php foreach ($teacher_subjects as $subj): ?>
                        <option value="<?php echo $subj; ?>" <?php echo $subj === $subject ? 'selected' : ''; ?> style="color: #2d3748;"><?php echo $subj; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </div>
        
        <div style="background: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; display: flex; gap: 1rem;">
            <button class="btn" id="midtermBtn" onclick="switchTab('midterm')" style="background: #667eea;"><i class="fas fa-book"></i> MIDTERM</button>
            <button class="btn" id="finalsBtn" onclick="switchTab('finals')" style="background: #cbd5e0; color: #4a5568;"><i class="fas fa-graduation-cap"></i> FINALS</button>
        </div>
        
        <div style="background: white; padding: 1.25rem; border-radius: 12px; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                <div style="flex: 1;">
                    <label style="font-weight: 600; margin-bottom: 0.5rem; display: block; color: #475569;">Search Student:</label>
                    <input type="text" id="searchStudent" oninput="applyFilters()" placeholder="Search by name..." style="width: 100%; padding: 0.625rem; border: 1px solid #e2e8f0; border-radius: 8px;">
                </div>
                <div style="flex: 1;">
                    <label style="font-weight: 600; margin-bottom: 0.5rem; display: block; color: #475569;">Filter by Year Level:</label>
                    <select id="yearFilter" onchange="applyFilters()" style="width: 100%; padding: 0.625rem; border: 1px solid #e2e8f0; border-radius: 8px;">
                        <option value="">All Years</option>
                        <option value="1st Year">1st Year</option>
                        <option value="2nd Year">2nd Year</option>
                        <option value="3rd Year">3rd Year</option>
                        <option value="4th Year">4th Year</option>
                    </select>
                </div>
                <div style="flex: 1;">
                    <label style="font-weight: 600; margin-bottom: 0.5rem; display: block; color: #475569;">Filter by Semester:</label>
                    <select id="semesterFilter" onchange="applyFilters()" style="width: 100%; padding: 0.625rem; border: 1px solid #e2e8f0; border-radius: 8px;">
                        <option value="">All Semesters</option>
                        <option value="1st" <?php echo $semester === '1st' ? 'selected' : ''; ?>>1st Semester</option>
                        <option value="2nd" <?php echo $semester === '2nd' ? 'selected' : ''; ?>>2nd Semester</option>
                        <option value="Intersem" <?php echo $semester === 'Intersem' ? 'selected' : ''; ?>>Intersem</option>
                    </select>
                </div>
                <div style="flex: 1;">
                    <label style="font-weight: 600; margin-bottom: 0.5rem; display: block; color: #475569;">Filter by Section:</label>
                    <select id="sectionFilter" onchange="applyFilters()" style="width: 100%; padding: 0.625rem; border: 1px solid #e2e8f0; border-radius: 8px;">
                        <option value="">All Sections</option>
                        <?php
                        $conn2 = new mysqli($servername, $dbusername, $dbpassword, $dbname);
                        $sec_result = $conn2->query("SELECT DISTINCT section FROM student_subjects WHERE teacher_id = '$teacher_id' AND subject_code = '$subject' ORDER BY section");
                        if ($sec_result) {
                            while ($sec = $sec_result->fetch_assoc()) {
                                if ($sec['section']) {
                                    $selected = $section === $sec['section'] ? 'selected' : '';
                                    echo "<option value='{$sec['section']}' $selected>BSIT {$sec['section']}</option>";
                                }
                            }
                        }
                        $conn2->close();
                        ?>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="controls">
            <h2 id="periodLabel" style="color: #667eea; margin: 0; font-size: 1.25rem;"><i class="fas fa-book"></i> MIDTERM GRADES</h2>
            <button class="btn" onclick="openModal()"><i class="fas fa-plus"></i> Add Columns</button>
            <button class="btn" onclick="showRanking()" style="background: #f59e0b;"><i class="fas fa-trophy"></i> View Ranking</button>
            <button class="btn" onclick="generateReport()" style="background: #8b5cf6;"><i class="fas fa-file-alt"></i> Generate Report</button>
            <button class="btn" onclick="exportExcel()" style="background: #10b981;"><i class="fas fa-file-excel"></i> Export Excel</button>
            <button class="btn btn-save" onclick="saveGrades()"><i class="fas fa-save"></i> Save All</button>
        </div>
        
        <div id="columnModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Add Grade Columns</h3>
                    <span class="close" onclick="closeModal()">&times;</span>
                </div>
                <div id="columnInputs">
                    <input type="text" class="column-input" placeholder="Column name (e.g. Quiz 1)">
                </div>
                <button class="btn" onclick="addInput()"><i class="fas fa-plus"></i> Add Another</button>
                <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
                    <button class="btn" onclick="addColumns()">Add All</button>
                    <button class="btn" onclick="closeModal()">Cancel</button>
                </div>
            </div>
        </div>
        
        <div id="remarksModal" class="modal">
            <div class="modal-content" style="width: 400px;">
                <div class="modal-header">
                    <h3>Edit Remarks</h3>
                    <span class="close" onclick="closeRemarksModal()">&times;</span>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="font-weight: 600; display: block; margin-bottom: 0.5rem;">Student:</label>
                    <div id="remarksStudentName" style="padding: 0.75rem; background: #f7fafc; border-radius: 6px; font-weight: 600;"></div>
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label style="font-weight: 600; display: block; margin-bottom: 0.5rem;">Select Remarks:</label>
                    <select id="remarksSelect" style="width: 100%; padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 1rem;">
                        <option value="">-- Select --</option>
                        <option value="PASSED">PASSED</option>
                        <option value="CONDITIONAL">CONDITIONAL</option>
                        <option value="FAILED">FAILED</option>
                        <option value="INC">INC (Incomplete)</option>
                        <option value="OD">OD (Officially Dropped)</option>
                        <option value="UD">UD (Unofficially Dropped)</option>
                    </select>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button class="btn" onclick="saveRemarksFromModal()" style="flex: 1;"><i class="fas fa-save"></i> Save</button>
                    <button class="btn" onclick="closeRemarksModal()" style="flex: 1; background: #cbd5e0; color: #4a5568;">Cancel</button>
                </div>
            </div>
        </div>
        
        <div id="rankingModal" class="modal">
            <div class="modal-content" style="width: 700px;">
                <div class="modal-header">
                    <h3><i class="fas fa-trophy"></i> Student Rankings - <span id="rankingPeriod">MIDTERM</span></h3>
                    <span class="close" onclick="closeRanking()">&times;</span>
                </div>
                <div id="rankingList" style="max-height: 500px; overflow-y: auto;"></div>
            </div>
        </div>
        
        <div class="grades-table">
            <table id="gradesTable">
                <thead>
                    <tr>
                        <th rowspan="3" class="student-name">STUDENT NAME</th>
                        <th colspan="3" class="section-header">CLASS ENGAGEMENT</th>
                        <th rowspan="3" class="total-col">20%</th>
                        <th colspan="10" class="section-header">LEARNING OUTPUTS</th>
                        <th rowspan="3" class="total-col">20%</th>
                        <th colspan="8" class="section-header">QUIZZES</th>
                        <th rowspan="3" class="total-col">20%</th>
                        <th colspan="2" class="section-header">EXAM</th>
                        <th rowspan="3" class="total-col">40%</th>
                        <th rowspan="3" class="total-col">TOTAL</th>
                        <th rowspan="3" class="total-col">EQUIVALENT</th>
                    </tr>
                    <tr>
                        <th class="category-header">ATT</th>
                        <th class="category-header">BEHAVIOR</th>
                        <th class="category-header">REC/PAR</th>
                        <th class="category-header">ACT 1</th>
                        <th class="category-header">AVE</th>
                        <th class="category-header">ACT 2</th>
                        <th class="category-header">AVE</th>
                        <th class="category-header">ACT 3</th>
                        <th class="category-header">AVE</th>
                        <th class="category-header">ACT 4</th>
                        <th class="category-header">AVE</th>
                        <th class="category-header">ACT 5</th>
                        <th class="category-header">AVE</th>
                        <th class="category-header">Q1</th>
                        <th class="category-header">AVE</th>
                        <th class="category-header">Q2</th>
                        <th class="category-header">AVE</th>
                        <th class="category-header">Q3</th>
                        <th class="category-header">AVE</th>
                        <th class="category-header">Q4</th>
                        <th class="category-header">AVE</th>
                        <th class="category-header">EXAM</th>
                        <th class="category-header">AVE</th>
                    </tr>
                    <tr id="maxScores">
                        <th class="category-header">100</th>
                        <th class="category-header">100</th>
                        <th class="category-header">100</th>
                        <th class="category-header">100</th>
                        <th class="category-header"></th>
                        <th class="category-header">100</th>
                        <th class="category-header"></th>
                        <th class="category-header">100</th>
                        <th class="category-header"></th>
                        <th class="category-header">100</th>
                        <th class="category-header"></th>
                        <th class="category-header">100</th>
                        <th class="category-header"></th>
                        <th class="category-header">100</th>
                        <th class="category-header"></th>
                        <th class="category-header">100</th>
                        <th class="category-header"></th>
                        <th class="category-header">100</th>
                        <th class="category-header"></th>
                        <th class="category-header">100</th>
                        <th class="category-header"></th>
                        <th class="category-header">100</th>
                        <th class="category-header"></th>



                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                    <tr data-student="<?php echo $student['id_number']; ?>" data-section="<?php echo $student['section']; ?>" data-year="<?php echo $student['year_level']; ?>" data-semester="<?php echo $student['semester']; ?>">
                        <td class="student-name"><?php echo $student['last_name'] . ', ' . $student['first_name']; ?> (<?php echo $student['section']; ?>)</td>
                        <td><input type="number" min="1" max="100" data-col="ATT" onchange="calculate(this)"></td>
                        <td><input type="number" min="1" max="100" data-col="Behavior" onchange="calculate(this)"></td>
                        <td><input type="number" min="1" max="100" data-col="RecPar" onchange="calculate(this)"></td>
                        <td class="total-col">0.00</td>
                        <td><input type="number" min="1" max="100" data-col="Act1" onchange="calculate(this)"></td>
                        <td class="total-col">0.00</td>
                        <td><input type="number" min="1" max="100" data-col="Act2" onchange="calculate(this)"></td>
                        <td class="total-col">0.00</td>
                        <td><input type="number" min="1" max="100" data-col="Act3" onchange="calculate(this)"></td>
                        <td class="total-col">0.00</td>
                        <td><input type="number" min="1" max="100" data-col="Act4" onchange="calculate(this)"></td>
                        <td class="total-col">0.00</td>
                        <td><input type="number" min="1" max="100" data-col="Act5" onchange="calculate(this)"></td>
                        <td class="total-col">0.00</td>
                        <td class="total-col" data-type="act20">0.00</td>
                        <td><input type="number" min="1" max="100" data-col="Q1" onchange="calculate(this)"></td>
                        <td class="total-col">0.00</td>
                        <td><input type="number" min="1" max="100" data-col="Q2" onchange="calculate(this)"></td>
                        <td class="total-col">0.00</td>
                        <td><input type="number" min="1" max="100" data-col="Q3" onchange="calculate(this)"></td>
                        <td class="total-col">0.00</td>
                        <td><input type="number" min="1" max="100" data-col="Q4" onchange="calculate(this)"></td>
                        <td class="total-col">0.00</td>
                        <td class="total-col" data-type="quiz20">0.00</td>
                        <td><input type="number" min="1" max="100" data-col="Exam" onchange="calculate(this)"></td>
                        <td class="total-col">0.00</td>
                        <td class="total-col" data-type="exam40"></td>
                        <td class="total-col" data-type="total"></td>
                        <td class="total-col" data-type="equivalent"></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div id="loadingModal" class="loading-modal">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <h3>Saving Grades...</h3>
            <p>Please wait while we save all grades.</p>
        </div>
    </div>
    
    <script>
    let currentTab = 'midterm';
    
    function switchTab(tab) {
        currentTab = tab;
        
        // Update button styles
        const midtermBtn = document.getElementById('midtermBtn');
        const finalsBtn = document.getElementById('finalsBtn');
        
        const periodLabel = document.getElementById('periodLabel');
        
        if (tab === 'midterm') {
            midtermBtn.style.background = '#667eea';
            midtermBtn.style.color = 'white';
            finalsBtn.style.background = '#cbd5e0';
            finalsBtn.style.color = '#4a5568';
            periodLabel.innerHTML = '<i class="fas fa-book"></i> MIDTERM GRADES';
        } else {
            finalsBtn.style.background = '#667eea';
            finalsBtn.style.color = 'white';
            midtermBtn.style.background = '#cbd5e0';
            midtermBtn.style.color = '#4a5568';
            periodLabel.innerHTML = '<i class="fas fa-graduation-cap"></i> FINALS GRADES';
        }
        
        // Clear all inputs
        document.querySelectorAll('input[type="number"]').forEach(input => {
            input.value = '';
        });
        document.querySelectorAll('tbody tr').forEach(row => {
            const totals = row.querySelectorAll('.total-col');
            for (let i = 0; i < 13; i++) {
                totals[i].textContent = '0.00';
            }
            totals[13].textContent = '0.00'; // 40%
            totals[14].textContent = '';     // TOTAL
            totals[15].textContent = '';     // EQUIVALENT
        });
        
        // Load grades for selected tab
        loadGrades();
    }
    
    function calculate(input) {
        const row = input.closest('tr');
        const inputs = row.querySelectorAll('input[type="number"]');
        const totals = row.querySelectorAll('.total-col');
        
        // Get base inputs
        const att = parseFloat(inputs[0].value) || 0;
        const behavior = parseFloat(inputs[1].value) || 0;
        const recpar = parseFloat(inputs[2].value) || 0;
        
        // Get all activity inputs
        const actInputs = Array.from(inputs).filter(inp => 
            inp.dataset.col && (inp.dataset.col.includes('Act') || inp.dataset.category === 'activity')
        );
        
        // Get all quiz inputs
        const quizInputs = Array.from(inputs).filter(inp => 
            inp.dataset.col && (inp.dataset.col.match(/^Q\d+$/) || inp.dataset.category === 'quiz')
        );
        
        // Get exam input
        const examInput = Array.from(inputs).find(inp => inp.dataset.col === 'Exam');
        const exam = parseFloat(examInput?.value) || 0;
        
        // CE: Direct percentage (score/100)*100, then average, then get 20%
        const attPS = att > 0 ? att : 0;
        const behaviorPS = behavior > 0 ? behavior : 0;
        const recparPS = recpar > 0 ? recpar : 0;
        const ceAve = (attPS + behaviorPS + recparPS) / 3;
        const ce20 = (ceAve/100)*20;
        totals[0].textContent = (att > 0 || behavior > 0 || recpar > 0) ? ce20.toFixed(2) : '0.00';
        
        // Activities: Calculate AVE for each and get 20%
        let actTotal = 0;
        actInputs.forEach(inp => {
            const score = parseFloat(inp.value) || 0;
            const avePS = score > 0 ? ((score/100)*50)+50 : 50;
            const aveTd = inp.closest('td').nextElementSibling;
            aveTd.textContent = score > 0 ? avePS.toFixed(2) : '50.00';
            actTotal += avePS;
        });
        const actAve = actInputs.length > 0 ? actTotal / actInputs.length : 0;
        const act20 = (actAve / 100) * 20;
        
        // Find and update Activities 20% total
        const act20Cell = row.querySelector('[data-type="act20"]');
        if (act20Cell) act20Cell.textContent = act20.toFixed(2);
        
        // Quizzes: Calculate AVE for each and get 20%
        let quizTotal = 0;
        quizInputs.forEach(inp => {
            const score = parseFloat(inp.value) || 0;
            const avePS = score > 0 ? ((score/100)*50)+50 : 50;
            const aveTd = inp.closest('td').nextElementSibling;
            aveTd.textContent = score > 0 ? avePS.toFixed(2) : '50.00';
            quizTotal += avePS;
        });
        const quizAve = quizInputs.length > 0 ? quizTotal / quizInputs.length : 0;
        const quiz20 = (quizAve / 100) * 20;
        
        // Find and update Quizzes 20% total
        const quiz20Cell = row.querySelector('[data-type="quiz20"]');
        if (quiz20Cell) quiz20Cell.textContent = quiz20.toFixed(2);
        
        // Exam: Convert to PS using ((score/max)*50)+50
        const examPS = exam > 0 ? ((exam/100)*50)+50 : 50;
        const examAveTd = examInput?.closest('td').nextElementSibling;
        if (examAveTd) examAveTd.textContent = exam > 0 ? examPS.toFixed(2) : '50.00';
        const exam40 = (examPS/100)*40;
        
        // Find and update Exam 40% total
        const exam40Cell = row.querySelector('[data-type="exam40"]');
        if (exam40Cell) exam40Cell.textContent = exam40.toFixed(2);
        
        // Total (only if there's any input)
        const hasInput = att > 0 || behavior > 0 || recpar > 0 || actInputs.some(inp => parseFloat(inp.value) > 0) || quizInputs.some(inp => parseFloat(inp.value) > 0) || exam > 0;
        const total = ce20 + act20 + quiz20 + exam40;
        
        // Find TOTAL and EQUIVALENT cells by data attribute
        const totalCell = row.querySelector('[data-type="total"]');
        const equivCell = row.querySelector('[data-type="equivalent"]');
        
        if (hasInput && totalCell && equivCell) {
            totalCell.textContent = total.toFixed(2);
            totalCell.style.color = total >= 75 ? '#48bb78' : '#ef4444';
            
            // Calculate equivalent
            let equivalent = '5.00';
            if (total >= 99) equivalent = '1.00';
            else if (total >= 96) equivalent = '1.25';
            else if (total >= 93) equivalent = '1.50';
            else if (total >= 90) equivalent = '1.75';
            else if (total >= 87) equivalent = '2.00';
            else if (total >= 84) equivalent = '2.25';
            else if (total >= 81) equivalent = '2.50';
            else if (total >= 78) equivalent = '2.75';
            else if (total >= 75) equivalent = '3.00';
            else if (total >= 70) equivalent = '4.00';
            
            equivCell.textContent = equivalent;
            equivCell.style.color = total >= 75 ? '#48bb78' : '#ef4444';
        } else if (totalCell && equivCell) {
            totalCell.textContent = '';
            equivCell.textContent = '';
        }
    }
    
    function openModal() {
        document.getElementById('columnModal').style.display = 'block';
    }
    
    function closeModal() {
        document.getElementById('columnModal').style.display = 'none';
        document.getElementById('columnInputs').innerHTML = '<input type="text" class="column-input" placeholder="Column name (e.g. Quiz 1)">';
    }
    
    function addInput() {
        const container = document.getElementById('columnInputs');
        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'column-input';
        input.placeholder = 'Column name (e.g. Quiz 1)';
        container.appendChild(input);
    }
    
    function addColumns() {
        const inputs = document.querySelectorAll('.column-input');
        const names = [];
        inputs.forEach(input => {
            if (input.value.trim()) names.push(input.value.trim());
        });
        
        if (names.length === 0) {
            alert('Please enter at least one column name');
            return;
        }
        
        const table = document.getElementById('gradesTable');
        const headerRow1 = table.querySelector('thead tr:nth-child(1)');
        const headerRow2 = table.querySelector('thead tr:nth-child(2)');
        const maxScoresRow = document.getElementById('maxScores');
        
        names.forEach(name => {
            const isQuiz = name.toLowerCase().includes('quiz') || name.toLowerCase().includes('q');
            const isActivity = name.toLowerCase().includes('act');
            
            let insertBeforeTh, insertBeforeRow2, insertBeforeRow3, insertBeforeCell;
            
            let parentSection;
            
            if (isQuiz) {
                // Insert in QUIZZES section
                const headers = Array.from(headerRow1.querySelectorAll('th'));
                parentSection = headers.find(th => th.textContent === 'QUIZZES');
                
                const row2Headers = Array.from(headerRow2.querySelectorAll('th'));
                const q4Index = row2Headers.findIndex(th => th.textContent === 'Q4');
                insertBeforeRow2 = row2Headers[q4Index + 2];
                
                const row3Headers = Array.from(maxScoresRow.querySelectorAll('th'));
                insertBeforeRow3 = row3Headers[row3Headers.length - 6];
            } else if (isActivity) {
                // Insert in LEARNING OUTPUTS section
                const headers = Array.from(headerRow1.querySelectorAll('th'));
                parentSection = headers.find(th => th.textContent === 'LEARNING OUTPUTS');
                
                const row2Headers = Array.from(headerRow2.querySelectorAll('th'));
                const act5Index = row2Headers.findIndex(th => th.textContent === 'ACT 5');
                insertBeforeRow2 = row2Headers[act5Index + 2];
                
                const row3Headers = Array.from(maxScoresRow.querySelectorAll('th'));
                insertBeforeRow3 = row3Headers[row3Headers.length - 12];
            } else {
                // Default: Insert before EXAM
                const headers = Array.from(headerRow1.querySelectorAll('th'));
                parentSection = headers.find(th => th.textContent.includes('EXAM'));
                
                insertBeforeRow2 = Array.from(headerRow2.querySelectorAll('th')).find(th => th.textContent === 'EXAM');
                
                const row3Headers = Array.from(maxScoresRow.querySelectorAll('th'));
                insertBeforeRow3 = row3Headers[row3Headers.length - 6];
            }
            
            // Row 1: Update colspan of parent section
            if (parentSection && parentSection.colSpan) {
                parentSection.colSpan = parseInt(parentSection.colSpan) + 2;
            }
            
            // Row 2: Score and AVE
            const th2a = document.createElement('th');
            th2a.className = 'category-header';
            th2a.textContent = name;
            headerRow2.insertBefore(th2a, insertBeforeRow2);
            
            const th2b = document.createElement('th');
            th2b.className = 'category-header';
            th2b.textContent = 'AVE';
            headerRow2.insertBefore(th2b, insertBeforeRow2);
            
            // Row 3: Max score
            const th3a = document.createElement('th');
            th3a.className = 'category-header';
            th3a.contentEditable = true;
            th3a.textContent = '100';
            maxScoresRow.insertBefore(th3a, insertBeforeRow3);
            
            const th3b = document.createElement('th');
            th3b.className = 'category-header';
            maxScoresRow.insertBefore(th3b, insertBeforeRow3);
        });
        
        // Add data cells to each student row
        document.querySelectorAll('tbody tr').forEach(row => {
            names.forEach(name => {
                const isQuiz = name.toLowerCase().includes('quiz') || name.toLowerCase().includes('q');
                const isActivity = name.toLowerCase().includes('act');
                
                let insertBeforeCell;
                if (isQuiz) {
                    // Insert after last quiz (before quiz 20% total)
                    insertBeforeCell = row.querySelector('[data-type="quiz20"]');
                } else if (isActivity) {
                    // Insert after last activity (before activity 20% total)
                    insertBeforeCell = row.querySelector('[data-type="act20"]');
                } else {
                    // Insert before EXAM
                    const examInput = Array.from(row.querySelectorAll('input')).find(inp => inp.dataset.col === 'Exam');
                    insertBeforeCell = examInput?.closest('td');
                }
                
                if (!insertBeforeCell) {
                    console.error('insertBeforeCell is null for:', name);
                    return;
                }
                
                // Input cell
                const td1 = document.createElement('td');
                const input = document.createElement('input');
                input.type = 'number';
                input.min = '1';
                input.max = '100';
                input.dataset.col = name.replace(/\s+/g, '');
                input.dataset.category = isQuiz ? 'quiz' : (isActivity ? 'activity' : 'other');
                input.onchange = function() { calculate(this); };
                td1.appendChild(input);
                row.insertBefore(td1, insertBeforeCell);
                
                // AVE cell
                const td2 = document.createElement('td');
                td2.className = 'total-col';
                td2.textContent = '0.00';
                row.insertBefore(td2, insertBeforeCell);
            });
            
            // Re-add data attributes to last 3 cells
            const cells = row.children;
            cells[cells.length - 3].dataset.type = 'exam40';
            cells[cells.length - 2].dataset.type = 'total';
            cells[cells.length - 1].dataset.type = 'equivalent';
        });
        
        closeModal();
        
        // Save column structure
        saveColumnStructure();
        
        alert(`${names.length} column(s) added successfully!`);
    }
    
    function saveColumnStructure() {
        const subject = '<?php echo $subject; ?>';
        const columns = [];
        
        document.querySelectorAll('thead tr:nth-child(2) th').forEach(th => {
            const text = th.textContent.trim();
            if (text && !['ATT', 'BEHAVIOR', 'REC/PAR', 'ACT 1', 'ACT 2', 'ACT 3', 'ACT 4', 'ACT 5', 'Q1', 'Q2', 'Q3', 'Q4', 'EXAM', 'AVE'].includes(text)) {
                columns.push(text);
            }
        });
        
        if (columns.length > 0) {
            fetch('save_column_structure.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `subject_code=${encodeURIComponent(subject)}&period=${currentTab}&columns=${encodeURIComponent(columns.join(','))}`
            });
        }
    }
    
    function calculateNew(input) {
        const row = input.closest('tr');
        const td = input.closest('td');
        const aveTd = td.nextElementSibling;
        
        const score = parseFloat(input.value) || 0;
        const ave = score > 0 ? ((score/100)*50)+50 : 0;
        aveTd.textContent = score > 0 ? ave.toFixed(2) : '0.00';
    }
    
    function showRanking() {
        const rankings = [];
        
        document.querySelectorAll('tbody tr').forEach(row => {
            const studentName = row.querySelector('.student-name').textContent;
            const totalCell = row.querySelector('[data-type="total"]');
            const total = parseFloat(totalCell?.textContent) || 0;
            
            // Check for INC (finals exam = 0)
            if (currentTab === 'finals') {
                const examInput = row.querySelector('input[data-col="Exam"]');
                const examValue = parseFloat(examInput?.value) || 0;
                if (examValue === 0 && total > 0) {
                    return; // Skip students with INC
                }
            }
            
            if (total > 0) {
                rankings.push({ name: studentName, total: total });
            }
        });
        
        // Sort by total descending
        rankings.sort((a, b) => b.total - a.total);
        
        // Generate ranking HTML
        let html = '<div style="padding: 1rem;">';
        rankings.forEach((student, index) => {
            const rank = index + 1;
            const medal = rank === 1 ? '🥇' : rank === 2 ? '🥈' : rank === 3 ? '🥉' : '';
            const color = student.total >= 75 ? '#48bb78' : '#ef4444';
            
            html += `
                <div style="padding: 1rem; margin-bottom: 0.5rem; background: #f7fafc; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; border-left: 4px solid ${color};">
                    <div>
                        <span style="font-size: 1.5rem; font-weight: 700; color: #667eea; margin-right: 1rem;">${rank}</span>
                        <span style="font-size: 1.1rem;">${medal} ${student.name}</span>
                    </div>
                    <div style="font-size: 1.25rem; font-weight: 700; color: ${color};">${student.total.toFixed(2)}</div>
                </div>
            `;
        });
        html += '</div>';
        
        document.getElementById('rankingList').innerHTML = html;
        document.getElementById('rankingPeriod').textContent = currentTab.toUpperCase();
        document.getElementById('rankingModal').style.display = 'block';
    }
    
    function closeRanking() {
        document.getElementById('rankingModal').style.display = 'none';
    }
    
    let currentRemarksCell = null;
    
    function openRemarksModal(cell) {
        currentRemarksCell = cell;
        const studentId = cell.dataset.student;
        const row = cell.closest('tr');
        const studentName = row.querySelector('.student-name').textContent;
        
        document.getElementById('remarksStudentName').textContent = studentName;
        document.getElementById('remarksSelect').value = cell.textContent.trim();
        document.getElementById('remarksModal').style.display = 'block';
    }
    
    function closeRemarksModal() {
        document.getElementById('remarksModal').style.display = 'none';
        currentRemarksCell = null;
    }
    
    function saveRemarksFromModal() {
        const remarks = document.getElementById('remarksSelect').value;
        if (!remarks || !currentRemarksCell) return;
        
        const studentId = currentRemarksCell.dataset.student;
        
        // Update cell
        currentRemarksCell.textContent = remarks;
        currentRemarksCell.dataset.manual = 'true';
        currentRemarksCell.className = 'total-col remarks-cell';
        if (remarks === 'PASSED') currentRemarksCell.classList.add('passed');
        else if (remarks === 'FAILED') currentRemarksCell.classList.add('failed');
        
        // Save to database
        const subject = '<?php echo $subject; ?>';
        fetch('save_grade.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `student_id=${studentId}&subject_code=${subject}&column_name=${currentTab}_remarks&grade=${remarks}`
        });
        
        closeRemarksModal();
    }
    
    function generateReport() {
        const yearFilter = document.getElementById('yearFilter').value;
        const semesterFilter = document.getElementById('semesterFilter').value;
        const sectionFilter = document.getElementById('sectionFilter').value;
        
        let url = 'teacher_report_grades.php?subject=<?php echo urlencode($subject); ?>';
        if (yearFilter) url += '&year=' + yearFilter;
        if (semesterFilter) url += '&semester=' + semesterFilter;
        if (sectionFilter) url += '&section=' + sectionFilter;
        
        window.open(url, '_blank');
    }
    
    function exportExcel() {
        const subject = '<?php echo urlencode($subject); ?>';
        window.location.href = `export_grades_excel.php?subject=${subject}&period=${currentTab}`;
    }
    
    function saveGrades() {
        // Show loading modal
        document.getElementById('loadingModal').style.display = 'block';
        document.querySelector('.btn-save').disabled = true;
        
        const rows = document.querySelectorAll('tbody tr');
        const subject = '<?php echo $subject; ?>';
        let saved = 0;
        let promises = [];
        
        rows.forEach(row => {
            const studentId = row.dataset.student;
            const inputs = row.querySelectorAll('input[type="number"]');
            
            // Save ALL individual grades (including new columns)
            inputs.forEach(input => {
                if (input.value && input.dataset.col) {
                    const col = currentTab + '_' + input.dataset.col;
                    const grade = input.value;
                    
                    const promise = fetch('save_grade.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `student_id=${studentId}&subject_code=${encodeURIComponent(subject)}&column_name=${col}&grade=${grade}`
                    }).then(() => saved++);
                    promises.push(promise);
                }
            });
            
            // Save TOTAL grade
            const totalCell = row.querySelector('[data-type="total"]');
            const equivCell = row.querySelector('[data-type="equivalent"]');
            const totalGrade = totalCell?.textContent;
            const equivalent = equivCell?.textContent;
            
            if (totalGrade && totalGrade !== '0.00' && totalGrade !== '') {
                const promise = fetch('save_grade.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `student_id=${studentId}&subject_code=${encodeURIComponent(subject)}&column_name=${currentTab}_total&grade=${totalGrade}&equivalent=${equivalent}`
                }).then(() => saved++);
                promises.push(promise);
            }
        });
        
        Promise.all(promises).then(() => {
            // Check for INC grades if this is finals period
            if (currentTab === 'finals') {
                checkForIncGrades();
            }
            
            // Hide loading modal
            document.getElementById('loadingModal').style.display = 'none';
            document.querySelector('.btn-save').disabled = false;
            alert(`${saved} grades saved successfully for ${currentTab.toUpperCase()}!`);
        }).catch(() => {
            // Hide loading modal on error
            document.getElementById('loadingModal').style.display = 'none';
            document.querySelector('.btn-save').disabled = false;
            alert('Error saving grades. Please try again.');
        });
    }
    
    // Load saved grades
    function loadGrades() {
        const subject = '<?php echo $subject; ?>';
        fetch('get_grades.php?subject_code=' + encodeURIComponent(subject))
        .then(response => response.json())
        .then(grades => {
            console.log('Loaded grades:', grades);
            document.querySelectorAll('tbody tr').forEach(row => {
                const studentId = row.dataset.student;
                
                if (grades[studentId]) {
                    // Load ALL input values (including new columns)
                    row.querySelectorAll('input[type="number"]').forEach(input => {
                        if (input.dataset.col) {
                            const col = currentTab + '_' + input.dataset.col;
                            if (grades[studentId][col] !== undefined) {
                                input.value = grades[studentId][col];
                                console.log('Loaded:', studentId, col, grades[studentId][col]);
                            }
                        }
                    });
                    
                    // Load saved TOTAL
                    const totalCol = currentTab + '_total';
                    const totalCell = row.querySelector('[data-type="total"]');
                    if (grades[studentId][totalCol] && totalCell) {
                        totalCell.textContent = grades[studentId][totalCol];
                        totalCell.dataset.saved = 'true';
                    }
                    
                    // Calculate to update other columns
                    const firstInput = row.querySelector('input');
                    if (firstInput) calculate(firstInput);
                }
            });
        })
        .catch(error => console.error('Error loading grades:', error));
    }
    
    // Load midterm grades on page load
    window.addEventListener('DOMContentLoaded', function() {
        loadColumnStructure();
    });
    
    function loadColumnStructure() {
        const subject = '<?php echo $subject; ?>';
        fetch('get_column_structure.php?subject_code=' + encodeURIComponent(subject) + '&period=' + currentTab)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.columns) {
                const columns = data.columns.split(',').filter(c => c.trim());
                if (columns.length > 0) {
                    restoreColumns(columns);
                }
            }
            loadGrades();
        })
        .catch(() => loadGrades());
    }
    
    function restoreColumns(columns) {
        const table = document.getElementById('gradesTable');
        const headerRow1 = table.querySelector('thead tr:nth-child(1)');
        const headerRow2 = table.querySelector('thead tr:nth-child(2)');
        const maxScoresRow = document.getElementById('maxScores');
        
        columns.forEach(name => {
            const isQuiz = name.toLowerCase().includes('quiz') || name.toLowerCase().match(/^q\d+$/i);
            const isActivity = name.toLowerCase().includes('act');
            
            let insertBeforeRow2, insertBeforeRow3, parentSection;
            
            if (isQuiz) {
                const headers = Array.from(headerRow1.querySelectorAll('th'));
                parentSection = headers.find(th => th.textContent === 'QUIZZES');
                const row2Headers = Array.from(headerRow2.querySelectorAll('th'));
                const q4Index = row2Headers.findIndex(th => th.textContent === 'Q4');
                insertBeforeRow2 = row2Headers[q4Index + 2];
                insertBeforeRow3 = maxScoresRow.children[maxScoresRow.children.length - 6];
            } else if (isActivity) {
                const headers = Array.from(headerRow1.querySelectorAll('th'));
                parentSection = headers.find(th => th.textContent === 'LEARNING OUTPUTS');
                const row2Headers = Array.from(headerRow2.querySelectorAll('th'));
                const act5Index = row2Headers.findIndex(th => th.textContent === 'ACT 5');
                insertBeforeRow2 = row2Headers[act5Index + 2];
                insertBeforeRow3 = maxScoresRow.children[maxScoresRow.children.length - 12];
            } else {
                const headers = Array.from(headerRow1.querySelectorAll('th'));
                parentSection = headers.find(th => th.textContent.includes('EXAM'));
                insertBeforeRow2 = Array.from(headerRow2.querySelectorAll('th')).find(th => th.textContent === 'EXAM');
                insertBeforeRow3 = maxScoresRow.children[maxScoresRow.children.length - 6];
            }
            
            if (parentSection && parentSection.colSpan) {
                parentSection.colSpan = parseInt(parentSection.colSpan) + 2;
            }
            
            const th2a = document.createElement('th');
            th2a.className = 'category-header';
            th2a.textContent = name;
            headerRow2.insertBefore(th2a, insertBeforeRow2);
            
            const th2b = document.createElement('th');
            th2b.className = 'category-header';
            th2b.textContent = 'AVE';
            headerRow2.insertBefore(th2b, insertBeforeRow2);
            
            const th3a = document.createElement('th');
            th3a.className = 'category-header';
            th3a.contentEditable = true;
            th3a.textContent = '100';
            maxScoresRow.insertBefore(th3a, insertBeforeRow3);
            
            const th3b = document.createElement('th');
            th3b.className = 'category-header';
            maxScoresRow.insertBefore(th3b, insertBeforeRow3);
        });
        
        document.querySelectorAll('tbody tr').forEach(row => {
            columns.forEach(name => {
                const isQuiz = name.toLowerCase().includes('quiz') || name.toLowerCase().match(/^q\d+$/i);
                const isActivity = name.toLowerCase().includes('act');
                
                let insertBeforeCell;
                if (isQuiz) {
                    insertBeforeCell = row.querySelector('[data-type="quiz20"]');
                } else if (isActivity) {
                    insertBeforeCell = row.querySelector('[data-type="act20"]');
                } else {
                    const examInput = Array.from(row.querySelectorAll('input')).find(inp => inp.dataset.col === 'Exam');
                    insertBeforeCell = examInput?.closest('td');
                }
                
                if (insertBeforeCell) {
                    const td1 = document.createElement('td');
                    const input = document.createElement('input');
                    input.type = 'number';
                    input.min = '1';
                    input.max = '100';
                    input.dataset.col = name.replace(/\s+/g, '');
                    input.dataset.category = isQuiz ? 'quiz' : (isActivity ? 'activity' : 'other');
                    input.onchange = function() { calculate(this); };
                    td1.appendChild(input);
                    row.insertBefore(td1, insertBeforeCell);
                    
                    const td2 = document.createElement('td');
                    td2.className = 'total-col';
                    td2.textContent = '0.00';
                    row.insertBefore(td2, insertBeforeCell);
                }
            });
        });
    }
    
    function applyFilters() {
        const searchInput = document.getElementById('searchStudent').value.toLowerCase();
        const yearFilter = document.getElementById('yearFilter').value;
        const semesterFilter = document.getElementById('semesterFilter').value;
        const sectionFilter = document.getElementById('sectionFilter').value;
        
        localStorage.setItem('yearFilter', yearFilter);
        localStorage.setItem('semesterFilter', semesterFilter);
        localStorage.setItem('sectionFilter', sectionFilter);
        
        const rows = document.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const year = row.dataset.year;
            const semester = row.dataset.semester;
            const section = row.dataset.section;
            const studentName = row.querySelector('.student-name').textContent.toLowerCase();
            
            const searchMatch = !searchInput || studentName.includes(searchInput);
            const yearMatch = !yearFilter || year === yearFilter;
            const semesterMatch = !semesterFilter || semester === semesterFilter;
            const sectionMatch = !sectionFilter || section === sectionFilter || sectionFilter.includes(section);
            
            if (searchMatch && yearMatch && semesterMatch && sectionMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    function checkForIncGrades() {
        const rows = document.querySelectorAll('tbody tr');
        const subject = '<?php echo $subject; ?>';
        
        rows.forEach(row => {
            const studentId = row.dataset.student;
            const examInput = row.querySelector('input[data-col="Exam"]');
            const totalCell = row.querySelector('[data-type="total"]');
            
            const examValue = parseFloat(examInput?.value) || 0;
            const totalValue = parseFloat(totalCell?.textContent) || 0;
            
            // INC condition: has total grade but no finals exam
            if (totalValue > 0 && examValue === 0) {
                // Save INC remark
                fetch('save_grade.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `student_id=${studentId}&subject_code=${encodeURIComponent(subject)}&column_name=final_grade&grade=0&remarks=INC&units=3&equivalent=INC&notified=0`
                });
            }
        });
    }
    
    // Restore filters on page load
    window.addEventListener('DOMContentLoaded', function() {
        const savedYear = localStorage.getItem('yearFilter');
        const savedSemester = localStorage.getItem('semesterFilter');
        const savedSection = localStorage.getItem('sectionFilter');
        
        if (savedYear) document.getElementById('yearFilter').value = savedYear;
        if (savedSemester) document.getElementById('semesterFilter').value = savedSemester;
        if (savedSection) document.getElementById('sectionFilter').value = savedSection;
        
        if (savedYear || savedSemester || savedSection) {
            applyFilters();
        }
    });
    </script>
</body>
</html>