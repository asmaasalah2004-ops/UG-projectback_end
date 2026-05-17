<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DB CONNECTION: " . DB::connection()->getDatabaseName() . " ===\n\n";

echo "=== USERS TABLE ===\n";
$users = DB::table('users')->get();
foreach ($users as $u) {
    echo "ID:{$u->id} | Email:{$u->email} | Role:{$u->role} | Name:{$u->name}\n";
}

echo "\n=== STUDENTS TABLE ===\n";
$students = DB::table('students')->get();
foreach ($students as $s) {
    echo "ID:{$s->id} | Name:{$s->name} | user_id:{$s->user_id} | dept:{$s->department_id}\n";
}

echo "\n=== ADMINS TABLE ===\n";
$admins = DB::table('admins')->get();
foreach ($admins as $a) {
    echo "ID:{$a->id} | Name:{$a->name} | user_id:{$a->user_id}\n";
}

echo "\n=== ADVISORS TABLE ===\n";
$advisors = DB::table('advisors')->get();
foreach ($advisors as $a) {
    echo "ID:{$a->id} | Name:{$a->name} | user_id:{$a->user_id}\n";
}

echo "\n=== DEPARTMENTS TABLE ===\n";
$depts = DB::table('departments')->get();
foreach ($depts as $d) {
    echo "ID:{$d->id} | Name:{$d->name}\n";
}

echo "\n=== COURSES TABLE ===\n";
$courses = DB::table('courses')->count();
echo "Total courses: $courses\n";

echo "\n=== SEMESTERS TABLE ===\n";
$sems = DB::table('semesters')->get();
foreach ($sems as $s) {
    echo "ID:{$s->id} | Name:{$s->name} | Active:{$s->is_active}\n";
}
