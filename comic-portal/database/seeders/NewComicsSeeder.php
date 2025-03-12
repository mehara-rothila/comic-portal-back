<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Comic;
use App\Models\User;

class NewComicsSeeder extends Seeder
{
    public function run()
    {
        $admin = User::where('email', 'admin3@admin.com')->first();

        $comics = [
            [
                'title' => 'My Hero Academia, Vol. 1',
                'description' => 'Izuku Midoriya is a young boy who dreams of becoming a hero, despite being born without a Quirk (superpower). A fateful encounter with his idol, the legendary hero All Might, sets him on a path to attend U.A. High School, a prestigious academy for training the next generation of heroes.',
                'author' => 'Kohei Horikoshi',
                'genre' => 'Superhero, Action, Comedy',
                'category_id' => 3, // Superhero category
                'price' => 0.17,
                'user_id' => $admin->id,
                'featured' => true,
                'status' => 'published'
            ],
            [
                'title' => 'The Amazing Spider-Man',
                'description' => "Peter Parker's journey continues in this thrilling new adventure. Follow Spider-Man as he swings through New York City, facing new challenges and battling powerful villains while trying to balance his personal life with his superhero responsibilities.",
                'author' => 'Marvel Comics',
                'genre' => 'Superhero, Action',
                'category_id' => 3, // Superhero category
                'price' => 19.99,
                'user_id' => $admin->id,
                'featured' => false,
                'status' => 'published'
            ]
        ];

        foreach ($comics as $comic) {
            Comic::create($comic);
        }
    }
}