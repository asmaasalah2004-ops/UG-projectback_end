<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    protected $fillable = [
     'name',
     'email',
     'password',
     'user_id'
     
    ];



        public function logs()
    {
        return $this->hasMany(AdminLog::class);
    }

    public function user()
{
    return $this->belongsTo(User::class);
}


    

}