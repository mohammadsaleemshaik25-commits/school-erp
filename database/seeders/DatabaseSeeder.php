<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create roles if they don't exist
        $adminRole = Role::firstOrCreate(
            ['role_name' => 'Admin'],
            ['description' => 'Administrator with full access']
        );

        $accountantRole = Role::firstOrCreate(
            ['role_name' => 'Accountant'],
            ['description' => 'Accountant with fee management access']
        );

        $teacherRole = Role::firstOrCreate(
            ['role_name' => 'Teacher'],
            ['description' => 'Teacher with student management access']
        );

        // Create admin user
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'role_id' => $adminRole->role_id,
                'full_name' => 'System Administrator',
                'email' => 'admin@vikaschool.com',
                'password_hash' => bcrypt('admin123'),
                'is_active' => true,
            ]
        );
    }
}
