<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\PortefeuilleVirtuel;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminEmail = 'admin@noorimmo.com';

        // Check if admin already exists
        if (User::where('email', $adminEmail)->exists()) {
            $this->command->info('⚠️  L\'utilisateur Super Admin existe déjà.');
            return;
        }

        $user = User::create([
            'nom' => 'Admin',
            'prenom' => 'Super',
            'email' => $adminEmail,
            'telephone' => '+221770000000',
            'password' => Hash::make('password123'),
            'user_type' => 'admin',
            'actif' => true,
        ]);

        // Admin might need a wallet depending on logic, safe to add one.
        PortefeuilleVirtuel::create([
            'user_id' => $user->id,
            'solde' => 0,
            'devise' => 'XOF',
        ]);

        $this->command->info('✅ Super Admin créé: ' . $adminEmail);
        $this->command->info('🔑 Mot de passe: password123');
    }
}
