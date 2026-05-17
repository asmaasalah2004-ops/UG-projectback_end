<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = \App\Models\User::all(['id', 'name', 'email', 'role', 'password']);

echo "\n=== All Users in Database ===\n";
foreach ($users as $user) {
    $isHashed = str_starts_with($user->password, '$2y$') || str_starts_with($user->password, '$2a$');
    echo "ID: {$user->id} | Email: {$user->email} | Role: {$user->role} | Password bcrypted: " . ($isHashed ? 'YES' : 'NO - plain: '.$user->password) . "\n";
}
echo "\nTotal users: " . $users->count() . "\n";
