<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortefeuilleVirtuel extends Model
{
    use HasFactory;

    protected $table = 'portefeuilles_virtuels';

    protected $fillable = [
        'user_id',
        'solde',
        'devise',
    ];

    protected function casts(): array
    {
        return [
            'solde' => 'decimal:2',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
