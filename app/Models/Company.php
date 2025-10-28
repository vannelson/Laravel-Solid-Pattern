<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'industry',
        'is_default',
        'logo',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'latitude'   => 'float',
        'longitude'  => 'float',
    ];

    /**
     * Company belongs to a User (owner).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Company Has many cars
     */
    public function cars()
    {
        return $this->hasMany(Car::class);
    }
}
