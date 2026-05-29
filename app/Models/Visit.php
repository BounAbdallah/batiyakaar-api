<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    protected $fillable = [
        'page',
        'ip_address',
        'user_agent',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
