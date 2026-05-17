<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semester extends Model
{
    protected $fillable = [
        'academic_year',
    	'semester_name',
    	'start_date',
    	'end_date',
    	'is_active'
    ];

	  public function getDurationAttribute()
    {
        return Carbon::parse($this->start_date)
            ->diffInWeeks($this->end_date) . ' weeks';
    }
     protected $appends = ['duration'];


	public function Timeslots():HasMany
	{
		return $this->hasMany(Timeslot::class);
	}
	public function studentCourses()
    {
    return $this->hasMany(StudentCourse::class);
    }
    public function appointments()
	{
		return $this->hasMany(Appointment::class);
	}
}
