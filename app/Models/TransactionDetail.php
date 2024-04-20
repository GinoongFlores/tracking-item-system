<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'company_id',
        'address_to',
        'address_from',
    ];

    protected $table = 'transaction_details';

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function items()
    {
        return $this->belongsToMany(Item::class, 'item_transfers', 'transaction_id', 'item_id')->withPivot('status', 'approved_by', 'approved_at')->withTimestamps();
    }
}
