<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [

            // super_admin
            'add_admin',
            'edit_admin',
            'delete_admin',
            'view_admin',

            // super_admin & admin
            'deactivate_admin',
            'deactivate_user',

            // super_admin & admin per company
            'add_users',
            'edit_users',
            'view_users',
            'delete_users',

            // super_admin
            'add_company',
            'delete_company',

            // super_admin & admin
            'edit_company',
            'view_company',

            // user only per company
            'add_item',
            'edit_item',
            'delete_item',

            // super_admin & admin per company
            'view_item',

            // admin & user per company
            'approve_item',
            'reject_item',

              // admin & user per company
              'view_pending_items',
              'view_approved_items',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['permission_name' => $permission]); // create a new record in the permissions table
        }
    }
}
