<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=lumbarong", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "--- Columns of refund_requests ---\n";
    $cols = $pdo->query("DESCRIBE `refund_requests`")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo "{$col['Field']} - {$col['Type']} - Null: {$col['Null']}\n";
    }

    echo "\n--- Sample join test (simulating getSellerRefundRequests) ---\n";
    try {
        $stmt = $pdo->query("
            SELECT rr.id, rr.sellerId, rr.customerId, rr.status,
                   o.id as orderId, o.status as orderStatus, o.totalAmount,
                   oi.id as orderItemId, oi.productId, oi.price, oi.quantity, oi.size, oi.variation,
                   p.name as productName,
                   u.name as customerName
            FROM refund_requests rr
            LEFT JOIN orders o ON o.id = rr.orderId
            LEFT JOIN order_items oi ON oi.id = rr.orderItemId
            LEFT JOIN products p ON p.id = oi.productId
            LEFT JOIN users u ON u.id = rr.customerId
            LIMIT 5
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Success! Rows: " . count($rows) . "\n";
        if ($rows) print_r($rows[0]);
    } catch (Exception $e) {
        echo "ERROR in join: " . $e->getMessage() . "\n";
    }

    // Check order_items columns
    echo "\n--- Columns of order_items ---\n";
    $cols = $pdo->query("DESCRIBE `order_items`")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo "{$col['Field']} - {$col['Type']}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
