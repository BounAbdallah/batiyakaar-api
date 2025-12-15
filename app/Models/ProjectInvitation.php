<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'projet_construction_id',
        'token',
        'role',
        'permissions',
        'expires_at',
        'used_at',
        'created_by',
    ];

    protected $casts = [
        'permissions' => 'array',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(ProjetConstruction::class, 'projet_construction_id');
    }
}
