<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Timeslot extends Model
{
     protected $table='time_slots';

     protected $fillable = [
     'advisor_id',
	'semester_id',
	'slot_date',
	'start_time',
	'end_time',
	'max_students',
	'status'
     ];
	 
    public function advisor(): BelongsTo
    {
        return $this->belongsTo(Advisor::class);
    }
	 public function Semester():BelongsTo
	 {
		return $this->belongsTo(Semester::class);
	 }
	 public function Appointments():HasMany
	 {
       return $this->hasMany(Appointment::class,'slot_id');
	 }
}
