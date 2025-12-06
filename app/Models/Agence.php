<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agence extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'raison_sociale',
        'ninea',
        'adresse',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function techniciens()
    {
        return $this->hasMany(Technicien::class);
    }

    public function baux()
    {
        return $this->hasMany(Bail::class);
    }

    public function biens()
    {
        return $this->hasMany(Bien::class);
    }

    public function abonnement()
    {
        return $this->hasOne(Abonnement::class);
    }
}
