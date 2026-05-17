<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Advisor extends Model
{
    protected $fillable = [
     'name',
     'email',
     'level',
     'department_id',
     'max_student',
     'user_id',
     
    ];
    public function Students():HasMany
	{
		return $this->hasMany(Student::class);
	}
    public function Department():BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
    public function Timeslots():HasMany
    {
        return $this->hasMany(Timeslot::class);
    }
    public function user()
   {
    return $this->belongsTo(User::class);
   }
}
