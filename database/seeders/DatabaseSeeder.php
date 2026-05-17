<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── 1. DEPARTMENTS ──────────────────────────────────────────
        $deptIds = [];
        $departments = [
            ['name' => 'Computer Science'],
            ['name' => 'Mathematics'],
            ['name' => 'Physics'],
            ['name' => 'Chemistry'],
        ];
        foreach ($departments as $dept) {
            $deptIds[] = DB::table('departments')->insertGetId(array_merge($dept, [
                'created_at' => now(), 'updated_at' => now(),
            ]));
        }
        [$csDept, $mathDept, $physicsDept, $chemDept] = $deptIds;

        // ─── 2. SEMESTER ─────────────────────────────────────────────
        $semesterId = DB::table('semesters')->insertGetId([
            'academic_year' => '2025-2026',
            'semester_name' => 'Spring 2026',
            'start_date'    => '2026-02-01',
            'end_date'      => '2026-06-30',
            'is_active'     => true,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $prevSemesterId = DB::table('semesters')->insertGetId([
            'academic_year' => '2024-2025',
            'semester_name' => 'Fall 2025',
            'start_date'    => '2025-09-01',
            'end_date'      => '2026-01-15',
            'is_active'     => false,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // ─── 3. COURSES ──────────────────────────────────────────────
        $courses = [
            ['course_name' => 'Introduction to Programming',   'course_code' => 'CS101',  'credit_hours' => 3, 'plan_semester' => 1],
            ['course_name' => 'Data Structures',               'course_code' => 'CS201',  'credit_hours' => 3, 'plan_semester' => 3],
            ['course_name' => 'Algorithms',                    'course_code' => 'CS301',  'credit_hours' => 3, 'plan_semester' => 5],
            ['course_name' => 'Database Systems',              'course_code' => 'CS302',  'credit_hours' => 3, 'plan_semester' => 5],
            ['course_name' => 'Operating Systems',             'course_code' => 'CS401',  'credit_hours' => 3, 'plan_semester' => 7],
            ['course_name' => 'Software Engineering',          'course_code' => 'CS402',  'credit_hours' => 3, 'plan_semester' => 7],
            ['course_name' => 'Computer Networks',             'course_code' => 'CS403',  'credit_hours' => 3, 'plan_semester' => 7],
            ['course_name' => 'Calculus I',                    'course_code' => 'MATH101','credit_hours' => 3, 'plan_semester' => 1],
            ['course_name' => 'Calculus II',                   'course_code' => 'MATH201','credit_hours' => 3, 'plan_semester' => 2],
            ['course_name' => 'Linear Algebra',                'course_code' => 'MATH202','credit_hours' => 3, 'plan_semester' => 3],
            ['course_name' => 'Discrete Mathematics',          'course_code' => 'MATH203','credit_hours' => 3, 'plan_semester' => 3],
            ['course_name' => 'Probability & Statistics',      'course_code' => 'MATH301','credit_hours' => 3, 'plan_semester' => 5],
            ['course_name' => 'General Physics I',             'course_code' => 'PHYS101','credit_hours' => 3, 'plan_semester' => 1],
            ['course_name' => 'Digital Logic Design',          'course_code' => 'CS202',  'credit_hours' => 3, 'plan_semester' => 3],
            ['course_name' => 'Machine Learning',              'course_code' => 'CS501',  'credit_hours' => 3, 'plan_semester' => 9],
            ['course_name' => 'Artificial Intelligence',       'course_code' => 'CS502',  'credit_hours' => 3, 'plan_semester' => 9],
            ['course_name' => 'Web Development',               'course_code' => 'CS503',  'credit_hours' => 3, 'plan_semester' => 7],
            ['course_name' => 'Computer Graphics',             'course_code' => 'CS504',  'credit_hours' => 3, 'plan_semester' => 7],
            ['course_name' => 'Compilers',                     'course_code' => 'CS601',  'credit_hours' => 3, 'plan_semester' => 9],
            ['course_name' => 'Security & Cryptography',       'course_code' => 'CS602',  'credit_hours' => 3, 'plan_semester' => 9],
        ];
        $courseIds = [];
        foreach ($courses as $c) {
            $courseIds[] = DB::table('courses')->insertGetId(array_merge($c, [
                'created_at' => now(), 'updated_at' => now(),
            ]));
        }

        // ── Course-Department mapping ─────────────────────────────────
        $csDeptCourses = [0,1,2,3,4,5,6,13,14,15,16,17,18,19]; // indices in $courseIds
        $mathDeptCourses = [7,8,9,10,11];
        $physicsDeptCourses = [12];

        foreach ($csDeptCourses as $idx) {
            DB::table('course_departments')->insert([
                'course_id'     => $courseIds[$idx],
                'department_id' => $csDept,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
        foreach ($mathDeptCourses as $idx) {
            DB::table('course_departments')->insert([
                'course_id'     => $courseIds[$idx],
                'department_id' => $mathDept,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
        foreach ($physicsDeptCourses as $idx) {
            DB::table('course_departments')->insert([
                'course_id'     => $courseIds[$idx],
                'department_id' => $physicsDept,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }

        // ── Course Prerequisites ──────────────────────────────────────
        // CS201 requires CS101
        DB::table('course_prerequisites')->insert(['course_id' => $courseIds[1], 'prerequisite_id' => $courseIds[0], 'created_at' => now(), 'updated_at' => now()]);
        // CS301 requires CS201
        DB::table('course_prerequisites')->insert(['course_id' => $courseIds[2], 'prerequisite_id' => $courseIds[1], 'created_at' => now(), 'updated_at' => now()]);
        // CS302 requires CS201
        DB::table('course_prerequisites')->insert(['course_id' => $courseIds[3], 'prerequisite_id' => $courseIds[1], 'created_at' => now(), 'updated_at' => now()]);
        // MATH201 requires MATH101
        DB::table('course_prerequisites')->insert(['course_id' => $courseIds[8], 'prerequisite_id' => $courseIds[7], 'created_at' => now(), 'updated_at' => now()]);
        // CS401 requires CS301
        DB::table('course_prerequisites')->insert(['course_id' => $courseIds[4], 'prerequisite_id' => $courseIds[2], 'created_at' => now(), 'updated_at' => now()]);

        // ─── 4. ADMIN USER ───────────────────────────────────────────
        $adminUserId = DB::table('users')->insertGetId([
            'name'       => 'System Admin',
            'email'      => 'admin@sci.asu.edu.eg',
            'password'   => Hash::make('Admin@1234'),
            'role'       => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('admins')->insert([
            'name'       => 'System Admin',
            'email'      => 'admin@sci.asu.edu.eg',
            'password'   => Hash::make('Admin@1234'),
            'user_id'    => $adminUserId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ─── 5. ADVISORS ─────────────────────────────────────────────
        $advisorData = [
            [
                'name'        => 'Dr. Ahmed Hassan',
                'email'       => 'ahmed.hassan@sci.asu.edu.eg',
                'level'       => 'Professor',
                'department_id' => $csDept,
                'max_student' => 30,
            ],
            [
                'name'        => 'Dr. Sara Ali',
                'email'       => 'sara.ali@sci.asu.edu.eg',
                'level'       => 'Associate Professor',
                'department_id' => $csDept,
                'max_student' => 25,
            ],
            [
                'name'        => 'Dr. Mohamed Kamal',
                'email'       => 'mohamed.kamal@sci.asu.edu.eg',
                'level'       => 'Assistant Professor',
                'department_id' => $mathDept,
                'max_student' => 20,
            ],
            [
                'name'        => 'Dr. Nada Youssef',
                'email'       => 'nada.youssef@sci.asu.edu.eg',
                'level'       => 'Lecturer',
                'department_id' => $physicsDept,
                'max_student' => 20,
            ],
        ];

        $advisorIds = [];
        foreach ($advisorData as $a) {
            // Create user account for advisor
            $advisorUserId = DB::table('users')->insertGetId([
                'name'       => $a['name'],
                'email'      => $a['email'],
                'password'   => Hash::make('Advisor@1234'),
                'role'       => 'advisor',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $advisorId = DB::table('advisors')->insertGetId(array_merge($a, [
                'user_id'    => $advisorUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
            $advisorIds[] = $advisorId;

            // Add time slots for the advisor (current semester)
            $slotDates = ['2026-03-10', '2026-03-17', '2026-03-24', '2026-04-07'];
            foreach ($slotDates as $date) {
                DB::table('time_slots')->insert([
                    'advisor_id'   => $advisorId,
                    'semester_id'  => $semesterId,
                    'slot_date'    => $date,
                    'start_time'   => '10:00',
                    'end_time'     => '12:00',
                    'max_students' => 5,
                    'status'       => 'available',
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }
        [$advisor1Id, $advisor2Id, $advisor3Id, $advisor4Id] = $advisorIds;

        // ─── 6. STUDENTS ─────────────────────────────────────────────
        $studentsData = [
            // CS Students
            ['name' => 'Omar Nasser',    'national_id' => '30001010101010', 'email' => 'omar.nasser@sci.asu.edu.eg',    'level' => 3, 'department_id' => $csDept,     'advisor_id' => $advisor1Id, 'risk' => 'low',    'gpa' => 3.5, 'sem_gpa' => 3.6, 'failed' => 0, 'max_ch' => 18, 'passed_ch' => 90],
            ['name' => 'Lina Samir',     'national_id' => '30002020202020', 'email' => 'lina.samir@sci.asu.edu.eg',     'level' => 2, 'department_id' => $csDept,     'advisor_id' => $advisor1Id, 'risk' => 'medium', 'gpa' => 2.3, 'sem_gpa' => 2.1, 'failed' => 2, 'max_ch' => 15, 'passed_ch' => 45],
            ['name' => 'Youssef Tarek',  'national_id' => '30003030303030', 'email' => 'youssef.tarek@sci.asu.edu.eg',  'level' => 4, 'department_id' => $csDept,     'advisor_id' => $advisor1Id, 'risk' => 'high',   'gpa' => 1.5, 'sem_gpa' => 1.2, 'failed' => 4, 'max_ch' => 12, 'passed_ch' => 100],
            ['name' => 'Hana Magdy',     'national_id' => '30004040404040', 'email' => 'hana.magdy@sci.asu.edu.eg',     'level' => 1, 'department_id' => $csDept,     'advisor_id' => $advisor2Id, 'risk' => 'low',    'gpa' => 3.8, 'sem_gpa' => 3.9, 'failed' => 0, 'max_ch' => 18, 'passed_ch' => 15],
            ['name' => 'Kareem Emad',    'national_id' => '30005050505050', 'email' => 'kareem.emad@sci.asu.edu.eg',    'level' => 2, 'department_id' => $csDept,     'advisor_id' => $advisor2Id, 'risk' => 'high',   'gpa' => 1.8, 'sem_gpa' => 1.5, 'failed' => 3, 'max_ch' => 12, 'passed_ch' => 30],
            ['name' => 'Nour Osama',     'national_id' => '30006060606060', 'email' => 'nour.osama@sci.asu.edu.eg',     'level' => 3, 'department_id' => $csDept,     'advisor_id' => $advisor2Id, 'risk' => 'medium', 'gpa' => 2.6, 'sem_gpa' => 2.4, 'failed' => 1, 'max_ch' => 15, 'passed_ch' => 75],
            // Math Students
            ['name' => 'Dina Fawzy',     'national_id' => '30007070707070', 'email' => 'dina.fawzy@sci.asu.edu.eg',     'level' => 2, 'department_id' => $mathDept,   'advisor_id' => $advisor3Id, 'risk' => 'low',    'gpa' => 3.3, 'sem_gpa' => 3.4, 'failed' => 0, 'max_ch' => 18, 'passed_ch' => 48],
            ['name' => 'Bassem Reda',    'national_id' => '30008080808080', 'email' => 'bassem.reda@sci.asu.edu.eg',    'level' => 3, 'department_id' => $mathDept,   'advisor_id' => $advisor3Id, 'risk' => 'high',   'gpa' => 1.4, 'sem_gpa' => 1.3, 'failed' => 5, 'max_ch' => 12, 'passed_ch' => 60],
            // Physics Students
            ['name' => 'Rania Khalil',   'national_id' => '30009090909090', 'email' => 'rania.khalil@sci.asu.edu.eg',   'level' => 1, 'department_id' => $physicsDept,'advisor_id' => $advisor4Id, 'risk' => 'medium', 'gpa' => 2.7, 'sem_gpa' => 2.5, 'failed' => 1, 'max_ch' => 15, 'passed_ch' => 10],
            ['name' => 'Adel Samy',      'national_id' => '30010100100100', 'email' => 'adel.samy@sci.asu.edu.eg',      'level' => 2, 'department_id' => $physicsDept,'advisor_id' => $advisor4Id, 'risk' => 'low',    'gpa' => 3.1, 'sem_gpa' => 3.2, 'failed' => 0, 'max_ch' => 18, 'passed_ch' => 42],
        ];

        foreach ($studentsData as $s) {
            // Create user account
            $studentUserId = DB::table('users')->insertGetId([
                'name'       => $s['name'],
                'email'      => $s['email'],
                'password'   => Hash::make('Student@1234'),
                'role'       => 'student',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create student record
            $studentId = DB::table('students')->insertGetId([
                'name'          => $s['name'],
                'national_id'   => $s['national_id'],
                'email'         => $s['email'],
                'password'      => Hash::make('Student@1234'),
                'level'         => $s['level'],
                'department_id' => $s['department_id'],
                'advisor_id'    => $s['advisor_id'],
                'user_id'       => $studentUserId,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            // Risk evaluation (previous semester)
            DB::table('risk_evaluation')->insert([
                'student_id'      => $studentId,
                'semester_id'     => $prevSemesterId,
                'risk_level'      => $s['risk'],
                'semester_gpa'    => $s['sem_gpa'],
                'cumulative_gpa'  => $s['gpa'],
                'failed_courses'  => $s['failed'],
                'allowed_maxCH'   => $s['max_ch'],
                'passed_CH'       => $s['passed_ch'],
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            // Enroll student in a few courses (current semester)
            $enrollCourses = match($s['department_id']) {
                $csDept     => [$courseIds[0], $courseIds[7], $courseIds[12]],
                $mathDept   => [$courseIds[7], $courseIds[8], $courseIds[12]],
                $physicsDept=> [$courseIds[7], $courseIds[12]],
                default     => [$courseIds[0]],
            };

            foreach ($enrollCourses as $cId) {
                $course = DB::table('courses')->where('id', $cId)->first();
                DB::table('student_courses')->insert([
                    'student_id'     => $studentId,
                    'courses_id'     => $cId,
                    'semester_id'    => $semesterId,
                    'credit_hours'   => $course->credit_hours,
                    'grade'          => 'N',
                    'status'         => 'enrolled',
                    'attempt_number' => 1,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        }

        $this->command->info('✅ Database seeded successfully!');
        $this->command->info('');
        $this->command->info('=== LOGIN CREDENTIALS ===');
        $this->command->info('Admin:');
        $this->command->info('  Email:    admin@sci.asu.edu.eg');
        $this->command->info('  Password: Admin@1234');
        $this->command->info('');
        $this->command->info('Advisors (all share the same password):');
        $this->command->info('  ahmed.hassan@sci.asu.edu.eg   → Password: Advisor@1234');
        $this->command->info('  sara.ali@sci.asu.edu.eg       → Password: Advisor@1234');
        $this->command->info('  mohamed.kamal@sci.asu.edu.eg  → Password: Advisor@1234');
        $this->command->info('  nada.youssef@sci.asu.edu.eg   → Password: Advisor@1234');
        $this->command->info('');
        $this->command->info('Students (all share the same password):');
        $this->command->info('  omar.nasser@sci.asu.edu.eg    → Password: Student@1234');
        $this->command->info('  lina.samir@sci.asu.edu.eg     → Password: Student@1234');
        $this->command->info('  (and 8 more students...)');
    }
}
