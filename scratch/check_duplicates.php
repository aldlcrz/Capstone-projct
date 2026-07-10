<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=lumbarong", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $users = $pdo->query("SELECT id, name, email, role, password, status FROM users WHERE email = 'admin@lumbarong.com'")->fetchAll(PDO::FETCH_ASSOC);
    print_r($users);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
