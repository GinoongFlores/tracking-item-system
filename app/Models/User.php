<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Role;
use App\Models\Item;
use App\Models\TransactionDetail;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public function roles() {
        return $this->belongsToMany(Role::class, 'role_users', 'user_id', 'role_id')->withTimestamps();
    }

    public function getRoleNameAttribute () {
        return $this->roles->first()->role_name ?? null;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }

    // company
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // items
    public function items()
    {
        return $this->hasMany(Item::class);
    }

    // transaction details
    public function sentTransactions()
    {
        return $this->hasMany(TransactionDetail::class, 'sender_id');
    }

    public function receivedTransactions()
    {
        return $this->hasMany(TransactionDetail::class, 'receiver_id');
    }

    public function approvedTransactions()
    {
        return $this->hasMany(TransactionDetail::class, 'approved_by');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'email',
        'password',
        'uuid',
        'company_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}
