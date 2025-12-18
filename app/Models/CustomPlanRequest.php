<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomPlanRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'telephone',
        'entreprise',
        'nombre_biens',
        'nombre_utilisateurs',
        'fonctionnalites_souhaitees',
        'besoins_specifiques',
        'budget_mensuel',
        'statut',
        'plan_id',
        'notes_admin',
    ];

    protected $casts = [
        'fonctionnalites_souhaitees' => 'array',
        'budget_mensuel' => 'decimal:2',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
