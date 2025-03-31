<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SongsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $songs = [
            ['album_id' => 1, 'user_id' => 1, 'title' => 'Hey Jude', 'artist' => 'The Beatles', 'duration' => 420],
            ['album_id' => 2, 'user_id' => 1, 'title' => 'Imagine', 'artist' => 'John Lennon', 'duration' => 183],
            ['album_id' => 3, 'user_id' => 1, 'title' => 'Billie Jean', 'artist' => 'Michael Jackson', 'duration' => 293],
            ['album_id' => 4, 'user_id' => 1, 'title' => 'Like a Rolling Stone', 'artist' => 'Bob Dylan', 'duration' => 370],
            ['album_id' => 5, 'user_id' => 1, 'title' => 'Smells Like Teen Spirit', 'artist' => 'Nirvana', 'duration' => 301],
            ['album_id' => 6, 'user_id' => 1, 'title' => 'Bohemian Rhapsody', 'artist' => 'Queen', 'duration' => 355],
            ['album_id' => 7, 'user_id' => 1, 'title' => 'Hotel California', 'artist' => 'Eagles', 'duration' => 391],
            ['album_id' => 1, 'user_id' => 1, 'title' => 'Let It Be', 'artist' => 'The Beatles', 'duration' => 243],
            ['album_id' => 2, 'user_id' => 1, 'title' => 'Jealous Guy', 'artist' => 'John Lennon', 'duration' => 256],
            ['album_id' => 3, 'user_id' => 1, 'title' => 'Smooth Criminal', 'artist' => 'Michael Jackson', 'duration' => 256],
        ];

        foreach ($songs as $song) {
            DB::table('songs')->insert([
                'album_id' => $song['album_id'],
                'user_id' => $song['user_id'],
                'title' => $song['title'],
                'artist' => $song['artist'],
                'duration' => $song['duration'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
