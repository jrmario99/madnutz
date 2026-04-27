<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Pré-treino',    'slug' => 'pre-treino',    'icon' => '⚡', 'featured' => true,  'sort_order' => 1],
            ['name' => 'Proteínas',     'slug' => 'proteinas',     'icon' => '💪', 'featured' => true,  'sort_order' => 2],
            ['name' => 'Creatina',      'slug' => 'creatina',      'icon' => '🔥', 'featured' => true,  'sort_order' => 3],
            ['name' => 'Vitaminas',     'slug' => 'vitaminas',     'icon' => '🌿', 'featured' => false, 'sort_order' => 4],
            ['name' => 'Emagrecedores', 'slug' => 'emagrecedores', 'icon' => '🏃', 'featured' => true,  'sort_order' => 5],
            ['name' => 'Barras',        'slug' => 'barras',        'icon' => '🍫', 'featured' => false, 'sort_order' => 6],
            ['name' => 'Acessórios',    'slug' => 'acessorios',    'icon' => '🎒', 'featured' => false, 'sort_order' => 7],
        ];

        foreach ($categories as $cat) {
            Category::create($cat);
        }
    }
}
