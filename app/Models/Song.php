<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Song extends Model
{
    use HasFactory;

    protected $fillable = ['album_id', 'user_id', 'title', 'artist', 'duration'];


    /**
     * Define the relationship with Album.
     */
    public function album()
    {
        return $this->belongsTo(Album::class);
    }

    /**
     * Define the relationship with Reactions.
     */
    public function reactions()
    {
        return $this->hasMany(Reaction::class);
    }
}
