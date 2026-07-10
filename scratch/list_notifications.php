<?php
// Simulate what SocketUtility::emitNotificationCountUpdated does via broadcasting
// Check if Pusher/broadcasting config is correct

try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=lumbarong", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "--- Notifications in DB ---\n";
    $rows = $pdo->query("SELECT id, userId, title, type, isRead, targetRole FROM notifications")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        print_r($row);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
