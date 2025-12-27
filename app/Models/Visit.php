<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [
        'page',
        'ip_address',
        'user_id',
        'user_agent',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
