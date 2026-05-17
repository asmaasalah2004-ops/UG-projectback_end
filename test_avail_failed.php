<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$student = App\Models\Student::first();
Auth::loginUsingId($student->user_id);

$c = new App\Http\Controllers\Student\RegistrationController();
$data = json_decode($c->index(request())->getContent(), true);

$available = $data['available_courses'] ?? [];
$found = false;
foreach($available as $c_item) {
    if ($c_item['course_id'] == 8) { // Course 8 is the failed course
        print_r($c_item);
        $found = true;
    }
}

if (!$found) {
    echo "Failed course 8 is NOT in available_courses list.\n";
}
