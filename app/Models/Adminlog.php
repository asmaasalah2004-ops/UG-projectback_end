<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Adminlog extends Model
{
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
