<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['name' => 'Choco Bomb',     'size' => '50g',  'brand' => 'MadNutz', 'price' => 19.90],
            ['name' => 'Choco Bomb',     'size' => '100g', 'brand' => 'MadNutz', 'price' => 34.90],
            ['name' => 'Super Lemon',    'size' => '50g',  'brand' => 'MadNutz', 'price' => 19.90],
            ['name' => 'Super Lemon',    'size' => '100g', 'brand' => 'MadNutz', 'price' => 34.90],
            ['name' => 'Crazy Pistache', 'size' => '50g',  'brand' => 'MadNutz', 'price' => 19.90],
            ['name' => 'Crazy Pistache', 'size' => '100g', 'brand' => 'MadNutz', 'price' => 34.90],
        ];

        foreach ($products as $data) {
            Product::create(array_merge($data, [
                'slug'  => Str::slug($data['name'] . '-' . $data['size']),
                'stock' => 999,
                'active' => true,
                'description' => "MADNUTZ {$data['name']} {$data['size']} — sabor com atitude.",
            ]));
        }
    }
}
