<?php
$passwords = ['password', 'password123', 'admin', 'admin123', '12345678', '123456'];
foreach (App\Models\User::all() as $user) {
    echo "Checking user: " . $user->email . "\n";
    foreach ($passwords as $p) {
        if (Illuminate\Support\Facades\Hash::check($p, $user->password)) {
            echo "MATCH: " . $user->email . " => " . $p . "\n";
        }
    }
}
