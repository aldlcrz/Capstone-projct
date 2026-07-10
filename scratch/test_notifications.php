<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=lumbarong", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Test the exact UPDATE query that markAllRead runs
    $userId = 'd44924cb-3acf-4526-9670-d32ada72e953'; // Artisan seller ID
    echo "Testing markAllRead query for userId: $userId\n\n";

    try {
        $stmt = $pdo->prepare("UPDATE `notifications` SET `isRead` = 1 WHERE `userId` = ? AND `isRead` = 0 AND `targetRole` = ?");
        $stmt->execute([$userId, 'seller']);
        echo "SUCCESS: markAllRead UPDATE ran fine. Rows affected: " . $stmt->rowCount() . "\n";
    } catch (Exception $e) {
        echo "ERROR in UPDATE: " . $e->getMessage() . "\n";
    }

    // Check the COUNT query too
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `notifications` WHERE `userId` = ? AND `isRead` = 0");
        $stmt->execute([$userId]);
        echo "SUCCESS: COUNT query ran fine. Unread count: " . $stmt->fetchColumn() . "\n";
    } catch (Exception $e) {
        echo "ERROR in COUNT: " . $e->getMessage() . "\n";
    }

    // Check current notifications data
    echo "\n--- Current Notifications ---\n";
    $stmt = $pdo->query("DESCRIBE `notifications`");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo "{$col['Field']} - {$col['Type']} - Default: {$col['Default']}\n";
    }

    $count = $pdo->query("SELECT COUNT(*) FROM notifications")->fetchColumn();
    echo "\nTotal notifications: $count\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
