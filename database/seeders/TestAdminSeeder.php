<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class TestAdminSeeder extends Seeder
{
    /**
     * Idempotently creates/resets a stable admin account for API testing,
     * without touching any other data in the database.
     */
    public function run(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $admin = User::updateOrCreate(
            ['email' => 'qa.admin@treasuregoapp.test'],
            [
                'name' => 'QA Admin',
                'username' => 'qa_admin',
                'country' => 'Test',
                'password' => Hash::make('TestAdmin123!'),
                'email_verified_at' => now(),
            ]
        );

        $admin->syncRoles([$role]);

        $this->command->info("Test admin ready: qa.admin@treasuregoapp.test / TestAdmin123!");
    }
}
