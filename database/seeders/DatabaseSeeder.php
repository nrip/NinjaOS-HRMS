<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles and permissions first
        $this->call(RoleAndPermissionSeeder::class);

        // Seed locations
        $this->call(LocationSeeder::class);

        // Create a test super admin user
        $location = \App\Models\Location::first();
        if ($location) {
            $user = User::factory()->create([
                'name' => 'Super Admin',
                'email' => 'admin@nexusos.local',
                'location_id' => $location->id,
                'is_active' => true,
            ]);
            $user->assignRole('super_admin');
        }
    }
}
