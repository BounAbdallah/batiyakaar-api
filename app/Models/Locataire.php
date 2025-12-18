<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Locataire extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'agence_id',
        'profession',
        'employeur',
        'revenu_mensuel',
        'numero_cni',
        'date_naissance',
        'lieu_naissance',
        'cni_recto',
        'cni_verso',
    ];

    protected function casts(): array
    {
        return [
            'revenu_mensuel' => 'decimal:2',
            'date_naissance' => 'date',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function baux()
    {
        return $this->hasMany(Bail::class);
    }

    public function agence()
    {
        return $this->belongsTo(Agence::class);
    }

    public function incidents()
    {
        return $this->hasMany(Incident::class);
    }
}
