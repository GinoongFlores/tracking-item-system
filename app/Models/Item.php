<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory, SoftDeletes;

    // fillable
    protected $fillable = [
        'name',
        'description',
        'quantity',
        'image',
        'user_id',
        'company_id',
    ];

    protected $dates = ['deleted_at'];

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

    // get company name of an item from a user
    public function getCompanyName()
    {
        return $this->user->company->company_name;
    }

    // item
    public function transactions()
    {
        return $this->belongsToMany(TransactionDetail::class, 'items_transfers', 'item_id', 'transaction_id')->withTimestamps();
    }
}
