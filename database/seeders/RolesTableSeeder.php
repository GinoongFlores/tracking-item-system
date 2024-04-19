<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = ['super_admin', 'admin', 'user'];

        foreach ($roles as $role) {
            if(!Role::where('role_name', $role)->exists()) {
                Role::create(['role_name' => $role]);
            }
        }
    }
}
