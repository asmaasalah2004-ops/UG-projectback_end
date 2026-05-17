<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    protected $fillable = [
        'student_id',
        'slot_id',
        'semester_id',
        'booking_date',
        'status',
        'department_id',
        'notes',
    ]; 
    public function student():BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
    public function timeslot():BelongsTo
    {
        return $this->belongsTo(Timeslot::class,'slot_id');
    }
    public function semester():BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }
    public function department():BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

}
