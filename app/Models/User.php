<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'type', // e.g., 'borrower', 'tenant', 'admin'
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * A User has many companies.
     */
    public function companies()
    {
        return $this->hasMany(Company::class);
    }

    /**
     * Bookings made by this user (as borrower).
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'borrower_id');
    }

    /**
     * Bookings managed by this user (as tenant/staff).
     */
    public function managedBookings()
    {
        return $this->hasMany(Booking::class, 'tenant_id');
    }

    /**
     * Scope: Only borrower users.
     */
    public function scopeBorrowers($query)
    {
        return $query->where('type', 'borrower');
    }

    /**
     * Scope: Only tenant users.
     */
    public function scopeTenants($query)
    {
        return $query->where('type', 'tenant');
    }
}
