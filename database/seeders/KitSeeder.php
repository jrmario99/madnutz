<?php

namespace Database\Seeders;

use App\Models\Kit;
use App\Models\Product;
use Illuminate\Database\Seeder;

class KitSeeder extends Seeder
{
    public function run(): void
    {
        $p = fn(string $name, string $size) => Product::where('name', $name)->where('size', $size)->first();

        $kits = [
            [
                'name'        => 'Full Madness',
                'slug'        => 'full-madness',
                'description' => 'Todos os sabores, em dobro, pra quem não aceita pouco.',
                'price'       => 289.90,
                'image'       => 'https://madnutz.com.br/wp-content/uploads/2026/02/KIT-FULL-MADNESS.webp',
                'featured'    => true,
                'badge'       => '💥',
                'sort_order'  => 1,
                'products'    => [
                    [$p('Choco Bomb', '100g'),     2],
                    [$p('Choco Bomb', '50g'),      2],
                    [$p('Super Lemon', '100g'),    2],
                    [$p('Super Lemon', '50g'),     2],
                    [$p('Crazy Pistache', '100g'), 2],
                    [$p('Crazy Pistache', '50g'),  2],
                ],
            ],
            [
                'name'        => 'Mad Started',
                'slug'        => 'mad-started',
                'description' => 'Perfeito pra começar no universo MadNutz com os 3 sabores.',
                'price'       => 67.90,
                'image'       => 'https://madnutz.com.br/wp-content/uploads/2026/02/KIT-MAD-STARTED.webp',
                'featured'    => true,
                'badge'       => '⚠️ Oferta exclusiva para primeira compra',
                'sort_order'  => 2,
                'products'    => [
                    [$p('Choco Bomb', '50g'),      1],
                    [$p('Super Lemon', '50g'),     1],
                    [$p('Crazy Pistache', '50g'),  1],
                ],
            ],
            [
                'name'        => 'Mad Boost',
                'slug'        => 'mad-boost',
                'description' => 'Mais intensidade pra quem já sabe que o básico não basta.',
                'price'       => 117.90,
                'image'       => 'https://madnutz.com.br/wp-content/uploads/2026/02/KIT-MAD-BOOST.webp',
                'featured'    => false,
                'badge'       => null,
                'sort_order'  => 3,
                'products'    => [
                    [$p('Choco Bomb', '100g'),     1],
                    [$p('Super Lemon', '100g'),    1],
                    [$p('Crazy Pistache', '100g'), 1],
                ],
            ],
            [
                'name'        => 'Mad Mode',
                'slug'        => 'mad-mode',
                'description' => 'O equilíbrio perfeito entre experimentar e repetir seus favoritos.',
                'price'       => 157.90,
                'image'       => 'https://madnutz.com.br/wp-content/uploads/2026/02/KIT-MAD-MODE.webp',
                'featured'    => true,
                'badge'       => '🔥 MAIS VENDIDO',
                'sort_order'  => 4,
                'products'    => [
                    [$p('Choco Bomb', '100g'),     1],
                    [$p('Choco Bomb', '50g'),      1],
                    [$p('Super Lemon', '100g'),    1],
                    [$p('Super Lemon', '50g'),     1],
                    [$p('Crazy Pistache', '100g'), 1],
                    [$p('Crazy Pistache', '50g'),  1],
                ],
            ],
        ];

        foreach ($kits as $data) {
            $products = $data['products'];
            unset($data['products']);
            $kit = Kit::create($data);
            foreach ($products as [$product, $qty]) {
                if ($product) {
                    $kit->products()->attach($product->id, ['quantity' => $qty]);
                }
            }
        }
    }
}
