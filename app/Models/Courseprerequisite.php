<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Courseprerequisite extends Model
{
    protected $table='course_prerequisites';
    protected $fillable = [
        'course_id',
    	'prerequisite_id'
    ];
}
