<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fonctionnalite extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'nom',
        'description',
        'module',
        'icone',
        'route',
        'actif',
        'ordre',
    ];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
            'ordre' => 'integer',
        ];
    }

    // Relationships
    public function plans()
    {
        return $this->belongsToMany(Plan::class, 'plan_fonctionnalite');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function scopeParOrdre($query)
    {
        return $query->orderBy('ordre');
    }
}
