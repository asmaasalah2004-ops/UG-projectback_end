<?php
use App\Models\Student;
use App\Models\Course;
use App\Http\Controllers\Student\RegistrationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle($request = Request::capture());

$student = Student::where('name', 'Tarek Nasser')->first();
if (!$student) { echo "Student not found\n"; exit; }

Auth::loginUsingId($student->user_id);

$request = new Request();
$course = Course::where('course_code', 'CHEM 101')->first();
$request->replace(['course_id' => $course->id]);

$controller = new RegistrationController();
$res = $controller->enroll($request);

echo "Status: " . $res->getStatusCode() . "\n";
echo "Body: " . $res->getContent() . "\n";
