<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // get roles
      $superAdminRole = Role::where('role_name', 'super_admin')->first();
      $adminRole = Role::where('role_name', 'admin')->first();
      $userRole = Role::where('role_name', 'user')->first();

        // get a company
        $company = Company::first();

        // Create an admin
        $admin = User::create([
            'first_name' => 'super admin',
            'last_name' => 'super admin',
            'phone' => '1234567890',
            'email' => 'super@admin.com',
            'password' => Hash::make('password'),
        ]);
        $admin->roles()->attach($superAdminRole);

        $admin = User::create([
            'first_name' => 'admin',
            'last_name' => 'admin',
            'phone' => '1234567890',
            'email' => 'admin@admin.com',
            'password' => Hash::make('password'),
            'company_id' => $company->id,
        ]);
        $admin->roles()->attach($adminRole);

        // Create a user
        // for ($i = 0; $i < 5; $i++) {
        //     $user = User::create([
        //         'first_name' => 'User',
        //         'last_name' => 'User' . $i,
        //         'phone' => '123456789 ' . $i,
        //         'email' => 'user' . $i . '@example.com',
        //         'password' => Hash::make('password'),
        //         'company_id' => Company::all()->random()->id,
        //     ]);
        //     $user->roles()->attach($userRole);
        // }

    }
}
