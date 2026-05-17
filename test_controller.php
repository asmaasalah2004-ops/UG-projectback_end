<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$student = App\Models\Student::first();
Auth::loginUsingId($student->user_id);

$request = Illuminate\Http\Request::create('/api/student/courses', 'GET');
$controller = new App\Http\Controllers\Student\CoursesController();
$response = $controller->index($request);

echo $response->getContent();
