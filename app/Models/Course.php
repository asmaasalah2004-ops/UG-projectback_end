<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = [
        'course_name',
    	'course_code',
    	'credit_hours',
    	'plan_semester'
    ];

   public function studentCourses()
   {
    return $this->hasMany(StudentCourse::class);
   }

  public function Riskevaluation():HasMany
  {
	return $this->hasMany(Riskevaluation::class);
  }

  public function prerequisites():BelongsToMany
  {
    return $this->belongsToMany(Course::class,'course_prerequisites','course_id','prerequisite_id');
  }

  public function isprerequisitefor():BelongsToMany
  {
    return $this->belongsToMany(Course::class,'course_prerequisites','prerequisite_id','course_id');
  }

  public function courseDepartments()
  {
    return $this->hasMany(CourseDepartment::class);
  }
 

}
