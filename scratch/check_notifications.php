<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=lumbarong", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "--- Columns of notifications ---\n";
    $cols = $pdo->query("DESCRIBE `notifications`")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo "{$col['Field']} - {$col['Type']} - Null: {$col['Null']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
