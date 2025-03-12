<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Action & Adventure', 'color' => 'bg-blue-500'],
            ['name' => 'Fantasy & Magic', 'color' => 'bg-purple-500'],
            ['name' => 'Superhero', 'color' => 'bg-red-500'],
            ['name' => 'Slice of Life', 'color' => 'bg-green-500'],
            ['name' => 'Mystery & Horror', 'color' => 'bg-gray-800'],
            ['name' => 'Romance', 'color' => 'bg-pink-500'],
            ['name' => 'Sci-Fi', 'color' => 'bg-indigo-500'],
            ['name' => 'Comedy', 'color' => 'bg-yellow-500'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}