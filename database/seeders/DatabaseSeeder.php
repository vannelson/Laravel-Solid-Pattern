<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Album;
use App\Models\Song;
use App\Models\Reaction;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the admin user
        $admin = User::create([
            'name' => 'admin',
            'email' => 'admin@example.com', // Change the email as needed
            'password' => Hash::make('12345678'),
        ]);

        // Album 1: Electric Dreams
        $album1 = Album::create([
            'user_id' => $admin->id,
            'title' => 'Electric Dreams',
            'description' => 'A collection of 80\'s classic hits that defined a generation.',
            'cover_image' => 'electric_dreams.jpg',
        ]);

        Song::create([
            'album_id' => $album1->id,
            'user_id' => $admin->id,
            'title' => 'Sweet Dreams (Are Made of This)',
            'artist' => 'Eurythmics',
            'duration' => 240,
        ]);
        Song::create([
            'album_id' => $album1->id,
            'user_id' => $admin->id,
            'title' => 'Tainted Love',
            'artist' => 'Soft Cell',
            'duration' => 240,
        ]);
        Song::create([
            'album_id' => $album1->id,
            'user_id' => $admin->id,
            'title' => 'Don\'t You (Forget About Me)',
            'artist' => 'Simple Minds',
            'duration' => 250,
        ]);
        Song::create([
            'album_id' => $album1->id,
            'user_id' => $admin->id,
            'title' => 'Hungry Like the Wolf',
            'artist' => 'Duran Duran',
            'duration' => 230,
        ]);
        Song::create([
            'album_id' => $album1->id,
            'user_id' => $admin->id,
            'title' => 'Every Breath You Take',
            'artist' => 'The Police',
            'duration' => 260,
        ]);

        // Album 2: Retro Rhythms
        $album2 = Album::create([
            'user_id' => $admin->id,
            'title' => 'Retro Rhythms',
            'description' => 'An assortment of timeless 80\'s hits from across the UK.',
            'cover_image' => 'retro_rhythms.jpg',
        ]);

        Song::create([
            'album_id' => $album2->id,
            'user_id' => $admin->id,
            'title' => 'Come On Eileen',
            'artist' => 'Dexys Midnight Runners',
            'duration' => 240,
        ]);
        Song::create([
            'album_id' => $album2->id,
            'user_id' => $admin->id,
            'title' => 'Karma Chameleon',
            'artist' => 'Culture Club',
            'duration' => 230,
        ]);
        Song::create([
            'album_id' => $album2->id,
            'user_id' => $admin->id,
            'title' => 'Faith',
            'artist' => 'George Michael',
            'duration' => 250,
        ]);
        Song::create([
            'album_id' => $album2->id,
            'user_id' => $admin->id,
            'title' => 'Under Pressure',
            'artist' => 'Queen & David Bowie',
            'duration' => 245,
        ]);
        Song::create([
            'album_id' => $album2->id,
            'user_id' => $admin->id,
            'title' => 'Blue Monday',
            'artist' => 'New Order',
            'duration' => 260,
        ]);

        // Album 3: Synth Pop Sensations
        $album3 = Album::create([
            'user_id' => $admin->id,
            'title' => 'Synth Pop Sensations',
            'description' => 'Synth-pop classics that electrified the 80\'s.',
            'cover_image' => 'synth_pop_sensations.jpg',
        ]);

        Song::create([
            'album_id' => $album3->id,
            'user_id' => $admin->id,
            'title' => 'Relax',
            'artist' => 'Frankie Goes to Hollywood',
            'duration' => 230,
        ]);
        Song::create([
            'album_id' => $album3->id,
            'user_id' => $admin->id,
            'title' => 'Bizarre Love Triangle',
            'artist' => 'New Order',
            'duration' => 245,
        ]);
        Song::create([
            'album_id' => $album3->id,
            'user_id' => $admin->id,
            'title' => 'West End Girls',
            'artist' => 'Pet Shop Boys',
            'duration' => 250,
        ]);
        Song::create([
            'album_id' => $album3->id,
            'user_id' => $admin->id,
            'title' => 'Just Can\'t Get Enough',
            'artist' => 'Depeche Mode',
            'duration' => 240,
        ]);
        Song::create([
            'album_id' => $album3->id,
            'user_id' => $admin->id,
            'title' => 'Don\'t You Want Me',
            'artist' => 'The Human League',
            'duration' => 250,
        ]);

        // Seed reactions
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
                'reaction_type' => Reaction::REACTION_APPROVED, // Assumes constant is defined in Reaction model
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        Reaction::insert($reactions);
    }
}
