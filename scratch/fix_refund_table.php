<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=lumbarong", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Checking existing refund_requests data...\n";
    $count = $pdo->query("SELECT COUNT(*) FROM `refund_requests`")->fetchColumn();
    echo "Existing rows: $count\n\n";

    if ($count > 0) {
        echo "Backing up existing data...\n";
        $rows = $pdo->query("SELECT * FROM `refund_requests`")->fetchAll(PDO::FETCH_ASSOC);
        file_put_contents(__DIR__ . '/refund_backup.json', json_encode($rows, JSON_PRETTY_PRINT));
        echo "Backed up $count rows to refund_backup.json\n\n";
    }

    echo "Dropping and recreating refund_requests with camelCase columns...\n";

    // Drop existing table (disable FK checks first)
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    $pdo->exec("DROP TABLE IF EXISTS `refund_requests`");

    // Recreate with camelCase columns matching the model
    $pdo->exec("
        CREATE TABLE `refund_requests` (
            `id` char(36) NOT NULL,
            `orderId` char(36) NOT NULL,
            `orderItemId` char(36) NOT NULL,
            `customerId` char(36) NOT NULL,
            `sellerId` char(36) NOT NULL,
            `reason` enum('Damaged Item','Wrong Size','Other') NOT NULL,
            `message` text DEFAULT NULL,
            `videoProof` varchar(255) NOT NULL,
            `status` enum('Pending','Approved','Rejected','Resolved') NOT NULL DEFAULT 'Pending',
            `sellerComment` text DEFAULT NULL,
            `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
            `updatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `refund_requests_orderId_idx` (`orderId`),
            KEY `refund_requests_orderItemId_idx` (`orderItemId`),
            KEY `refund_requests_customerId_idx` (`customerId`),
            KEY `refund_requests_sellerId_idx` (`sellerId`),
            CONSTRAINT `fk_refund_orderId` FOREIGN KEY (`orderId`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_refund_orderItemId` FOREIGN KEY (`orderItemId`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_refund_customerId` FOREIGN KEY (`customerId`) REFERENCES `users` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_refund_sellerId` FOREIGN KEY (`sellerId`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

    echo "SUCCESS: refund_requests recreated with camelCase columns.\n\n";

    echo "--- New columns ---\n";
    $cols = $pdo->query("DESCRIBE `refund_requests`")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo "{$col['Field']} - {$col['Type']}\n";
    }

} catch (Exception $e) {
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    echo "Error: " . $e->getMessage() . "\n";
}
