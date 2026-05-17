<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Studentcourse extends Model
{
    protected $table='student_courses';
    protected $fillable = [
    'student_id',
	'courses_id',
	'semester_id',
    'credit_hours',
	'grade',
	'status',
	'attempt_number'
    ];

	public function student()
    {
     return $this->belongsTo(Student::class);
    }

   public function course()
   {
    return $this->belongsTo(Course::class,'courses_id');
   }

  public function semester()
  {
    return $this->belongsTo(Semester::class);
  }
}
