<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = ['employee','admin','superadmin','hr','finance','accounting'];
        foreach ($roles as $r) {
            Role::firstOrCreate(['name' => $r]);
        }

        // Assign role to user:
        // $user->assignRole('admin');
    }
}
