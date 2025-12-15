<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartiesPrenantes extends Model
{
    use HasFactory;

    protected $fillable = [
        'projet_construction_id',
        'user_id',
        'role',
        'permissions',
        'date_ajout',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'date_ajout' => 'datetime',
            'actif' => 'boolean',
            'permissions' => 'array',
        ];
    }

    // Relationships
    public function projetConstruction()
    {
        return $this->belongsTo(ProjetConstruction::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
