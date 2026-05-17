<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Riskevaluation extends Model
{
    protected $table='risk_evaluation';
    protected $fillable = [
        'student_id',
    	'semester_id',
    	'risk_level',
    	'semester_gpa',
		'cumulative_gpa',
    	'failed_courses',
		'allowed_maxCH',
		'passed_CH',
    
    ];

    protected static function booted()
    {
        static::saving(function ($model) {
            $gpa = $model->cumulative_gpa;
            if ($gpa > 0) {
                if ($gpa < 2.0) {
                    $model->risk_level = 'High level';
                    $model->allowed_maxCH = 12;
                } elseif ($gpa >= 2.0 && $gpa < 2.76) {
                    $model->risk_level = 'Medium level';
                    $model->allowed_maxCH = 17;
                } else {
                    $model->risk_level = 'Low level';
                    $model->allowed_maxCH = 17;
                }
            }
        });
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }
	 
	public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
	public function Department():BelongsTo
	{
		return $this->belongsTo(Department::class);
	}
	public function Course():BelongsTo
	{
		return $this->belongsTo(Course::class);
	}
}
