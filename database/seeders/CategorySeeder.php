<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run()
{
    // Ganti truncate() dengan delete()
    \DB::table('categories')->delete();

    Category::insert([
        [
            'id' => 1,
            'name' => 'Buah',
            'slug' => 'buah',
        ],
        [
            'id' => 2,
            'name' => 'Sayur',
            'slug' => 'sayur',
        ],
        [
            'id' => 3,
            'name' => 'Daging',
            'slug' => 'daging',
        ],
    ]);
}

}
