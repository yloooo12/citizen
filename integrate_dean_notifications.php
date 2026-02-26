<?php
// Integration script to add dean notifications to existing request handlers

// First, let's check what files need to be updated
$files_to_check = [
    'secretary_crediting.php',
    'program_head_requests.php', 
    'unscheduled_subjects.php',
    'inc_removal.php'
];

echo "<h2>Dean Notification Integration</h2>";
echo "<p>This script will help integrate dean notifications into existing request handlers.</p>";

// Check if files exist
foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "✅ Found: $file<br>";
    } else {
        echo "❌ Not found: $file<br>";
    }
}

echo "<br><h3>Manual Integration Steps:</h3>";
echo "<ol>";
echo "<li>Add this line at the top of request handling files:<br><code>require_once 'dean_notification_system.php';</code></li>";
echo "<li>When a request is sent to dean, add appropriate notification call:</li>";
echo "<ul>";
echo "<li>For crediting: <code>notifyDeanCreditingRequest(\$student_id, \$student_name, \$request_id);</code></li>";
echo "<li>For INC: <code>notifyDeanIncRequest(\$student_id, \$student_name, \$request_id, \$subject);</code></li>";
echo "<li>For unscheduled: <code>notifyDeanUnscheduledRequest(\$student_id, \$student_name, \$request_id, \$subject_code, \$subject_name);</code></li>";
echo "</ul>";
echo "</ol>";

// Create a sample integration for unscheduled subjects
echo "<br><h3>Sample Integration Code:</h3>";
echo "<pre>";
echo htmlspecialchars('
// Example for unscheduled_subjects.php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit_request"])) {
    // ... existing code to save request ...
    
    // Add dean notification
    require_once "dean_notification_system.php";
    notifyDeanUnscheduledRequest($student_id, $student_name, $request_id, $subject_code, $subject_name);
    
    // ... rest of existing code ...
}
');
echo "</pre>";

echo "<br><a href='dean_notifications.php'>View Dean Notifications</a> | ";
echo "<a href='add_dean_notifications.php'>Setup Tables</a>";
?>