<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Permission;

class Role extends Model
{
    use HasFactory;

    public function users() {
        return $this->belongsToMany(User::class, 'role_users', 'user_id', 'role_id')->withTimestamps();
    }

    // permissions
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_roles', 'role_id', 'permission_id')->withTimestamps();
    }
}
