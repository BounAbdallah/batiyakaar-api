<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'telephone',
        'cin',
        'date_naissance',
        'lieu_naissance',
        'password',
        'user_type',
        'actif',
        'agence_id',
        'permissions',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'actif' => 'boolean',
            'date_naissance' => 'date',
            'permissions' => 'array',
        ];
    }

    // Polymorphic Relationships
    public function bailleur()
    {
        return $this->hasOne(Bailleur::class);
    }

    public function agence()
    {
        // If user_type is 'agence', they own it (hasOne).
        // If they are a team member, they belong to it (belongsTo).
        // To avoid conflict, let's keep 'agence' as ownership (hasOne) for backward compat,
        // and add 'equipeAgence' for membership?
        // Or better: Unify. If owner, agence_id is null but they have Agence record with user_id.
        // If member, `users.agence_id` is set.

        // This existing relationship is hasOne (Owner).
        return $this->hasOne(Agence::class);
    }

    public function employeurAgence()
    {
        return $this->belongsTo(Agence::class, 'agence_id');
    }

    public function entrepreneur()
    {
        return $this->hasOne(Entrepreneur::class);
    }

    public function fournisseur()
    {
        return $this->hasOne(Fournisseur::class);
    }

    public function locataire()
    {
        return $this->hasOne(Locataire::class);
    }

    // Other Relationships
    public function portefeuilleVirtuel()
    {
        return $this->hasOne(PortefeuilleVirtuel::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function transactionsEmises()
    {
        return $this->hasMany(Transaction::class, 'emetteur_id');
    }

    public function transactionsRecues()
    {
        return $this->hasMany(Transaction::class, 'beneficiaire_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('actif', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('user_type', $type);
    }

    // Accessors
    public function getNomCompletAttribute()
    {
        return "{$this->prenom} {$this->nom}";
    }
}
