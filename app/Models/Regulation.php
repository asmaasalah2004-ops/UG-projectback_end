<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Regulation extends Model
{
    protected $fillable = [
     'regulation_name',
	'description',
	'version_year',
	'is_active',
	'file_path'

    ];
	
   
}
