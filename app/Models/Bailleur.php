<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bailleur extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pays',
        'adresse_diaspora',
        'numero_cni',
        'date_naissance',
        'lieu_naissance',
        'cni_recto',
        'cni_verso',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function projetsConstruction()
    {
        return $this->hasMany(ProjetConstruction::class);
    }

    public function biens()
    {
        return $this->hasMany(Bien::class);
    }

    public function baux()
    {
        return $this->hasManyThrough(Bail::class, Bien::class);
    }

    public function commandes()
    {
        return $this->hasMany(Commande::class);
    }
}
