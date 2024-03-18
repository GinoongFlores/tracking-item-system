<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Item extends Model
{
    use HasFactory;

    // user
    public function user ()
    {
        return $this->belongsTo(User::class);
    }

    // company
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
