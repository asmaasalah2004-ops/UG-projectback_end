<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = 'tarek.nasser.1174@sci.asu.edu.eg';
$user = \App\Models\User::where('email', $email)->first();

if ($user) {
    $user->password = bcrypt('12345678');
    $user->save();
    echo "Password updated successfully for $email.\n";
    
    // Also test Auth::attempt right after
    $attempt = \Illuminate\Support\Facades\Auth::attempt(['email' => $email, 'password' => '12345678']);
    echo "Auth::attempt test: " . ($attempt ? "SUCCESS" : "FAILED") . "\n";
} else {
    echo "User not found: $email\n";
}
