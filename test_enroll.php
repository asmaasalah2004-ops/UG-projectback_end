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
if (empty($available)) {
    echo "No available courses.";
    exit;
}

$courseId = null;
foreach($available as $c_item) {
    if ($c_item['can_enroll'] == 1) {
        $courseId = $c_item['course_id'];
        break;
    }
}

if (!$courseId) {
    echo "No enrollable courses.";
    exit;
}

echo "Attempting to enroll in course_id: $courseId\n";

$request = Illuminate\Http\Request::create('/api/student/registration/enroll', 'POST', [
    'course_id' => $courseId
]);
$response = $c->enroll($request);

echo $response->getContent();
