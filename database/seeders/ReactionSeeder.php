<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Reaction;
use App\Models\User;
use App\Models\Song;

class ReactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::pluck('id')->toArray();
        $songs = Song::pluck('id')->toArray();

        if (empty($users) || empty($songs)) {
            $this->command->info('No users or songs found. Seed users and songs first.');
            return;
        }

        $reactions = [];
        for ($i = 0; $i < 700; $i++) {
            $reactions[] = [
                'user_id' => $users[array_rand($users)],
                'song_id' => $songs[array_rand($songs)],
                'reaction_type' => Reaction::REACTION_APPROVED, // Always 1
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Reaction::insert($reactions);
    }
}
