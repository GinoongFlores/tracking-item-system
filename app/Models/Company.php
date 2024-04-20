<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_name',
        'company_description',
        'address',
    ];

    // protected dates
    protected $dates = ['deleted_at'];

    // users
    public function users()
    {
        return $this->hasMany(User::class);
    }

    // transaction details
    public function transactionDetails()
    {
        return $this->hasMany(TransactionDetail::class);
    }
}
