<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reaction extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'song_id', 'reaction_type'];

    // Reaction types
    const REACTION_APPROVED = 1;
    const REACTION_HEART = 2;
    const REACTION_CLAPPING = 3;

    /**
     * Define the relationship with User.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Define the relationship with Song.
     */
    public function song()
    {
        return $this->belongsTo(Song::class);
    }

    /**
     * Get the reaction type in a human-readable format.
     */
    public function getReactionTypeAttribute($value)
    {
        $reactions = [
            self::REACTION_APPROVED => 'approved',
            self::REACTION_HEART => 'heart',
            self::REACTION_CLAPPING => 'clapping', 
        ];

        return $reactions[$value] ?? 'unknown';
    }
}
