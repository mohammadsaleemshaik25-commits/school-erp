<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserPasswordSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Update principal user password
        $principal = User::where('username', 'principal')->first();
        if ($principal) {
            $principal->password_hash = Hash::make('principal123');
            $principal->save();
        }

        // Update correspondent user password
        $correspondent = User::where('username', 'correspondent')->first();
        if ($correspondent) {
            $correspondent->password_hash = Hash::make('correspondent123');
            $correspondent->save();
        }

        // Update clerk user password
        $clerk = User::where('username', 'clerk')->first();
        if ($clerk) {
            $clerk->password_hash = Hash::make('clerk123');
            $clerk->save();
        }

        $this->command->info('User passwords updated successfully.');
    }
}
