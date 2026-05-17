<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\Admin\AdvisorController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\Semestercontroller;
use App\Http\Controllers\Admin\Studentcontroller;
use App\Models\Admin;
use App\Models\Student;
use App\Http\Controllers\Admin\Dashboardcontroller as AdminDashboardController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Student\Dashboardcontroller as StudentDashboardController;
use App\Http\Controllers\Student\BookingController;
use App\Http\Controllers\Student\AppointmentController as AppointmentController;
use App\Http\Controllers\Student\CoursesController as StudentCoursesController;
use App\Http\Controllers\Student\RegistrationController;
use App\Http\Controllers\Advisor\DashboardController as AdvisorDashboardController;
use App\Http\Controllers\Advisor\MystudentsController;
use App\Http\Controllers\Advisor\StudentprofileController;
use App\Http\Controllers\Advisor\AppointmentsController as AdvisorAppointmentsController;
use App\Http\Controllers\Advisor\MytimeslotsController;





  Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register',[AuthController::class, 'register']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });
 });



   Route::prefix('admin')
   ->middleware(['auth:sanctum', 'role:admin'])
   ->group(function(){
     Route::get('/dashboard', [AdminDashboardController::class, 'index']);



  Route::prefix('departments')->group(function () {
        Route::get('', [DepartmentController::class, 'index']);
        Route::post('', [DepartmentController::class, 'store']);
        Route::delete('/{id}', [DepartmentController::class, 'destroy']);
    });

  Route::prefix('courses')->group(function () {
    Route::get('', [CourseController::class, 'index']);
    Route::post('', [CourseController::class, 'store']);
    Route::get('/{id}', [CourseController::class, 'show']);
    Route::put('/{id}', [CourseController::class, 'update']);
    Route::delete('/{id}', [CourseController::class, 'destroy']);
 });

 Route::prefix('advisors')->group(function () {
    Route::get('', [AdvisorController::class, 'index']);
    Route::post('', [AdvisorController::class, 'store']);
    Route::get('/{id}', [AdvisorController::class, 'show']);
    Route::put('/{id}', [AdvisorController::class, 'update']);
    Route::delete('/{id}', [AdvisorController::class, 'destroy']);
 });

  Route::prefix('semesters')->group(function () {
    Route::get('', [SemesterController::class, 'index']);
    Route::post('', [SemesterController::class, 'store']);
    Route::get('/{id}', [SemesterController::class, 'show']);
    Route::put('/{id}', [SemesterController::class, 'update']);
    Route::patch('/{id}/activate', [SemesterController::class, 'activate']);
 });

 Route::prefix('students')->group(function(){
    Route::get('', [StudentController::class, 'index']);
    Route::post('', [StudentController::class, 'store']);
    Route::get('/{id}', [StudentController::class, 'show']);
    Route::put('/{id}', [StudentController::class, 'update']);
    Route::delete('/{id}', [StudentController::class, 'destroy']);
 });
   });




   Route::prefix('student')
    ->middleware(['auth:sanctum', 'role:student'])
   
   ->group(function(){
      Route::get('/dashboard',[StudentDashboardController::class,'index']);

      Route::prefix('bookings')
          ->group(function(){
              Route::get('/days/{month}/{year}', [BookingController::class, 'getAvailableDays']);
              Route::get('/slots/{date}', [BookingController::class, 'getSlotsByDate']);
              Route::post('/book', [BookingController::class, 'book']);
          });

      Route::prefix('appointments')
          ->group(function(){
              Route::get('/', [AppointmentController::class, 'index']);
              Route::post('/{id}/cancel', [AppointmentController::class, 'cancel']);
          });

          Route::get('/courses',[StudentCoursesController::class,'index']);
          
    Route::prefix('registration')->group(function () {
 
    Route::get ('',[RegistrationController::class, 'index']);
    Route::post('enroll',[RegistrationController::class, 'enroll']);
    Route::post('unenroll/{enrollmentId}',[RegistrationController::class, 'unenroll']);
    Route::post('confirm',[RegistrationController::class, 'confirm']);
    Route::post('unconfirm',[RegistrationController::class, 'unconfirm']);


   });
});

    Route::prefix('advisor')
    ->middleware(['auth:sanctum', 'role:advisor'])
   
   ->group(function(){
    Route::get('/dashboard',[AdvisorDashboardController::class,'index']);
    
    Route::get('/mystudents',[MystudentsController::class,'index']);

    Route::prefix('students/{studentId}')->group(function () {
            Route::get('', [StudentprofileController::class, 'index']);
            Route::get('availablecourses', [StudentprofileController::class, 'availableCourses']);
            Route::post('addcourse', [StudentprofileController::class, 'addCourse']);
            Route::delete('removecourse/{courseId}', [StudentprofileController::class, 'removeCourse']);
            Route::post('confirmcourses', [StudentprofileController::class, 'confirmCourses']);
        });
        
        Route::prefix('appointments')->group(function () {
            Route::get('', [AdvisorAppointmentsController::class, 'advisor_appointments']);
            Route::get('stats', [AdvisorAppointmentsController::class, 'advisor_stats']);
            Route::patch('update_status/{id}', [AdvisorAppointmentsController::class, 'update_status']);
        });
        

        Route::prefix('timeslots')->group(function () {
            Route::get('', [MytimeslotsController::class, 'index']);
            Route::post('addslot', [MytimeslotsController::class, 'store']);
            Route::put('update/{slot}', [MytimeslotsController::class, 'update']);
        });



 });


    
   










