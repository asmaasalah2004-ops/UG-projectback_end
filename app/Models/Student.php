<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    protected $fillable = [

    'name',
	'national_id',
	'email',
	'password',
	'level',
	'department_id',
	'advisor_id',
	'user_id'
    ];

	public function department():BelongsTo
	{
		return $this->belongsTo(Department::class);
	}
	public function advisor():BelongsTo
	{
		return $this->belongsTo(Advisor::class);
	}
	public function studentCourses()
    {
    return $this->hasMany(StudentCourse::class);
    }
	public function appointments():HasMany
	{
		return $this->hasMany(Appointment::class);
	}
  
   public function riskEvaluations()
   {
    return $this->hasMany(Riskevaluation::class);
   }
   public function user()
   {
    return $this->belongsTo(User::class);
   }
}

