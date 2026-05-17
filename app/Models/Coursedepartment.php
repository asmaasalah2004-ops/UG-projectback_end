<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coursedepartment extends Model
{
    protected $table='course_departments';
    protected $fillable = [
        'department_id',
    	'course_id',
    	'type_course'
    ];

    public function course()
    {
    return $this->belongsTo(Course::class);
    }

   public function department()
   {
    return $this->belongsTo(Department::class);
   }
}
