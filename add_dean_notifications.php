<?php
// Script to add dean notifications to existing request handlers
require_once 'dean_notification_system.php';

// Create the dean_notifications table
createDeanNotificationsTable();

echo "Dean notification system setup complete!<br>";
echo "✅ dean_notifications table created<br>";
echo "✅ Notification functions ready<br><br>";

echo "To integrate with existing request handlers, add these lines:<br><br>";

echo "<strong>For Crediting Requests (in secretary_crediting.php or similar):</strong><br>";
echo "<code>require_once 'dean_notification_system.php';<br>";
echo "notifyDeanCreditingRequest(\$student_id, \$student_name, \$request_id);</code><br><br>";

echo "<strong>For INC Requests (when forwarded to dean):</strong><br>";
echo "<code>require_once 'dean_notification_system.php';<br>";
echo "notifyDeanIncRequest(\$student_id, \$student_name, \$request_id, \$subject);</code><br><br>";

echo "<strong>For Unscheduled Subject Requests:</strong><br>";
echo "<code>require_once 'dean_notification_system.php';<br>";
echo "notifyDeanUnscheduledRequest(\$student_id, \$student_name, \$request_id, \$subject_code, \$subject_name);</code><br><br>";

echo "<a href='dean_notifications.php'>View Dean Notifications</a>";
?>