<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Album extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'title', 'description', 'cover_image'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reactions()
    {
        return $this->hasMany(Reaction::class);
    }

    /**
     * Define relationship with the Song model.
     */
    public function songs(): HasMany
    {
        return $this->hasMany(Song::class);
    }
}
