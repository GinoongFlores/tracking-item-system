<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Role;

/*

Objectives for Permissions
1. Super Admin have Add permission of adding only an Admin and View Users, Admins, and Items
2. Admin have Add permission of adding only a User and View Users and Items
3. User have Add permission of adding only an Item and View Items only


Three Users Permission  (super_admin, admin, user)

General permission
- view_pending_items
- view_approved_items

1. super_admin
- add_admin
- add_company
- deactivate_admin
- deactivate_user
- view_users
_ view_admins
_ view_items

2. admin
- add_user
- deactivate_user
- view_users
- view_items
- approved_transaction

3. user
- add_item
- view_users

Transfer of items workflow on user to another user to admin
- user will add an item to the list of items
- user will select an item from the list of items or add a new item
- user will send the item to other user and generate a record of the transaction with status pending
- admin will approve the transaction and the status will be updated to approved


*/

class Permissions extends Model
{
    use HasFactory;

    public function roles() {
        return $this->belongsToMany(Role::class, 'role_permission');
    }
}
