<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class ComicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the first admin user or create one if none exists
        $adminId = $this->getOrCreateAdminUser();

        // Define the three default comic books with all required fields
        // Using direct paths to the images in public directory
        $comics = [
            [
                'title' => 'The Amazing Spider-Man',
                'description' => "Follow Peter Parker as he balances his life as an ordinary high school student in Queens with his superhero alter-ego Spider-Man. When he's not fighting crime as Spider-Man, Peter must navigate the emotional rollercoaster of being a teenager.",
                'author' => 'Stan Lee & Steve Ditko',
                'genre' => 'Superhero',
                'price' => 4.99,
                'image_url' => '/images/fallbacks/spiderman.jpg',  // Direct path to public images
                'category_id' => 1,
                'user_id' => $adminId,
                'featured' => true,
                'status' => 'published',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'title' => 'Berserk',
                'description' => "Guts, a former mercenary now known as the \"Black Swordsman,\" is out for revenge. After a tumultuous childhood, he finally finds someone he respects and believes he can trust, only to have everything fall apart when this person takes away everything important to Guts for the purpose of fulfilling his own desires.",
                'author' => 'Kentaro Miura',
                'genre' => 'Dark Fantasy',
                'price' => 14.99,
                'image_url' => '/images/fallbacks/berserk.jpg',  // Direct path to public images
                'category_id' => 2,
                'user_id' => $adminId,
                'featured' => true,
                'status' => 'published',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'title' => 'Vinland Saga',
                'description' => "Thorfinn pursues a journey with his father's killer in order to take revenge and end his life in a duel as an honorable warrior and pay his father a homage. As a boy, Thorfinn worked desperately to become a great warrior capable of defeating Askeladd and avenging his father.",
                'author' => 'Makoto Yukimura',
                'genre' => 'Historical',
                'price' => 12.99,
                'image_url' => '/images/fallbacks/vinland.jpg',  // Direct path to public images
                'category_id' => 2,
                'user_id' => $adminId,
                'featured' => false,
                'status' => 'published',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        // Insert comics into database
        DB::table('comics')->insert($comics);
    }

    /**
     * Get the first admin user ID or create a default admin user
     *
     * @return int
     */
    private function getOrCreateAdminUser()
    {
        // Try to get the first admin user
        $admin = DB::table('users')->where('is_admin', 1)->first();

        // If admin exists, return their ID
        if ($admin) {
            return $admin->id;
        }

        // If no admin user exists, try to get any user
        $user = DB::table('users')->first();
        
        if ($user) {
            return $user->id;
        }

        // If no users exist at all, create a default admin
        $userId = DB::table('users')->insertGetId([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'is_admin' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return $userId;
    }
}