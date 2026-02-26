<?php
// Test script for dean notification system
require_once 'dean_notification_system.php';

echo "<h2>Dean Notification System Test</h2>";

// Create table if not exists
if (createDeanNotificationsTable()) {
    echo "✅ Dean notifications table ready<br><br>";
} else {
    echo "❌ Failed to create table<br><br>";
}

// Test notifications
echo "<h3>Testing Notifications:</h3>";

// Test crediting notification
if (notifyDeanCreditingRequest('0122-1234', 'Juan Dela Cruz', 123)) {
    echo "✅ Crediting notification sent<br>";
} else {
    echo "❌ Crediting notification failed<br>";
}

// Test INC notification
if (notifyDeanIncRequest('0122-5678', 'Maria Santos', 456, 'Math 101')) {
    echo "✅ INC notification sent<br>";
} else {
    echo "❌ INC notification failed<br>";
}

// Test unscheduled notification
if (notifyDeanUnscheduledRequest('0122-9999', 'Pedro Garcia', 789, 'CS101', 'Programming Fundamentals')) {
    echo "✅ Unscheduled notification sent<br>";
} else {
    echo "❌ Unscheduled notification failed<br>";
}

echo "<br><h3>Current Notifications:</h3>";
$notifications = getDeanNotifications(10);
if (empty($notifications)) {
    echo "No notifications found.<br>";
} else {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Message</th><th>Type</th><th>Student</th><th>Date</th><th>Read</th></tr>";
    foreach ($notifications as $notif) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($notif['message']) . "</td>";
        echo "<td>" . htmlspecialchars($notif['type']) . "</td>";
        echo "<td>" . htmlspecialchars($notif['student_name'] ?? 'N/A') . "</td>";
        echo "<td>" . date('M d, Y h:i A', strtotime($notif['created_at'])) . "</td>";
        echo "<td>" . ($notif['is_read'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$unread_count = getUnreadDeanNotificationCount();
echo "<br><strong>Unread notifications: $unread_count</strong><br><br>";

echo "<a href='dean_notifications.php'>View Dean Notifications Page</a> | ";
echo "<a href='dean_dashboard.php'>Dean Dashboard</a>";
?>