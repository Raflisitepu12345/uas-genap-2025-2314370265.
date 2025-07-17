<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        Product::create([
            'name' => 'Apel Fuji',
            'slug' => 'apel-fuji',
            'price' => 15000,
            'quantity' => 50,
            'description' => 'Apel Fuji segar dan manis.',
            'details' => 'Apel dari Jepang dengan rasa manis dan tekstur renyah.',
            'weight' => 0.3,
            'category_id' => 1
        ]);

        Product::create([
            'name' => 'Pisang Cavendish',
            'slug' => 'pisang-cavendish',
            'price' => 12000,
            'quantity' => 70,
            'description' => 'Pisang segar Cavendish, cocok untuk diet.',
            'details' => 'Pisang kaya vitamin dan serat.',
            'weight' => 1,
            'category_id' => 1
        ]);

        Product::create([
            'name' => 'Brokoli Segar',
            'slug' => 'brokoli-segar',
            'price' => 18000,
            'quantity' => 40,
            'description' => 'Brokoli segar hijau pekat.',
            'details' => 'Kaya antioksidan, baik untuk kesehatan.',
            'weight' => 0.5,
            'category_id' => 2
        ]);

        Product::create([
            'name' => 'Wortel Organik',
            'slug' => 'wortel-organik',
            'price' => 9000,
            'quantity' => 100,
            'description' => 'Wortel organik manis alami.',
            'details' => 'Cocok untuk jus atau sayur sop.',
            'weight' => 0.7,
            'category_id' => 2
        ]);

        Product::create([
            'name' => 'Tomat Merah',
            'slug' => 'tomat-merah',
            'price' => 8000,
            'quantity' => 80,
            'description' => 'Tomat merah segar dan matang.',
            'details' => 'Sumber vitamin C dan likopen.',
            'weight' => 1,
            'category_id' => 2
        ]);
    }
}
