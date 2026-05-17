<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = [
        'name',
        'faculty'
    ];

    public function Students():HasMany
     {
       return $this->hasMany(Student::class);

     }
     public function Advisors():HasMany
     {
        return $this->hasMany(Advisor::class);
     }
     public function  Riskevaluations():HasMany
     {
      return $this->hasMany(Riskevaluation::class);
     }
     public function courseDepartments():HasMany
      {
          return $this->hasMany(CourseDepartment::class);
      }
     public function appointments()
     {
    return $this->hasMany(Appointment::class);
     }
}
