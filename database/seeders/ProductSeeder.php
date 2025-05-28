<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use Faker\Factory as Faker;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('pt_BR');
        $categories = Category::all();

        $productsByCategory = [
            'Boné' => [
                ['name' => 'Boné Nike Dri-FIT', 'brand' => 'Nike', 'price' => 89.90, 'cost' => 45.00],
                ['name' => 'Boné Adidas Trefoil', 'brand' => 'Adidas', 'price' => 79.90, 'cost' => 40.00],
                ['name' => 'Boné New Era 9FIFTY', 'brand' => 'New Era', 'price' => 159.90, 'cost' => 80.00],
                ['name' => 'Boné Puma Essential', 'brand' => 'Puma', 'price' => 69.90, 'cost' => 35.00],
                ['name' => 'Boné Vans Classic', 'brand' => 'Vans', 'price' => 99.90, 'cost' => 50.00],
                ['name' => 'Boné Oakley Tincan', 'brand' => 'Oakley', 'price' => 129.90, 'cost' => 65.00],
                ['name' => 'Boné Under Armour Blitzing', 'brand' => 'Under Armour', 'price' => 119.90, 'cost' => 60.00],
                ['name' => 'Boné Hurley One and Only', 'brand' => 'Hurley', 'price' => 89.90, 'cost' => 45.00],
            ],
            'Tênis' => [
                ['name' => 'Tênis Nike Air Max 270', 'brand' => 'Nike', 'price' => 599.90, 'cost' => 300.00],
                ['name' => 'Tênis Adidas Ultraboost 22', 'brand' => 'Adidas', 'price' => 899.90, 'cost' => 450.00],
                ['name' => 'Tênis Vans Old Skool', 'brand' => 'Vans', 'price' => 329.90, 'cost' => 165.00],
                ['name' => 'Tênis Converse All Star', 'brand' => 'Converse', 'price' => 199.90, 'cost' => 100.00],
                ['name' => 'Tênis Puma RS-X', 'brand' => 'Puma', 'price' => 499.90, 'cost' => 250.00],
                ['name' => 'Tênis New Balance 574', 'brand' => 'New Balance', 'price' => 449.90, 'cost' => 225.00],
                ['name' => 'Tênis Asics Gel-Kayano', 'brand' => 'Asics', 'price' => 699.90, 'cost' => 350.00],
                ['name' => 'Tênis Mizuno Wave Prophecy', 'brand' => 'Mizuno', 'price' => 799.90, 'cost' => 400.00],
                ['name' => 'Tênis Fila Disruptor', 'brand' => 'Fila', 'price' => 299.90, 'cost' => 150.00],
                ['name' => 'Tênis Reebok Classic', 'brand' => 'Reebok', 'price' => 259.90, 'cost' => 130.00],
            ],
            'Camisas' => [
                ['name' => 'Camisa Polo Lacoste', 'brand' => 'Lacoste', 'price' => 299.90, 'cost' => 150.00],
                ['name' => 'Camiseta Nike Dri-FIT', 'brand' => 'Nike', 'price' => 89.90, 'cost' => 45.00],
                ['name' => 'Camisa Social Hugo Boss', 'brand' => 'Hugo Boss', 'price' => 599.90, 'cost' => 300.00],
                ['name' => 'Camiseta Adidas Originals', 'brand' => 'Adidas', 'price' => 99.90, 'cost' => 50.00],
                ['name' => 'Camisa Jeans Levi\'s', 'brand' => 'Levi\'s', 'price' => 249.90, 'cost' => 125.00],
                ['name' => 'Camiseta Tommy Hilfiger', 'brand' => 'Tommy Hilfiger', 'price' => 179.90, 'cost' => 90.00],
            ],
            'Eletrônicos' => [
                ['name' => 'Smartphone Samsung Galaxy S23', 'brand' => 'Samsung', 'price' => 3999.90, 'cost' => 2000.00],
                ['name' => 'iPhone 14 Pro', 'brand' => 'Apple', 'price' => 7999.90, 'cost' => 4000.00],
                ['name' => 'Notebook Dell Inspiron', 'brand' => 'Dell', 'price' => 2999.90, 'cost' => 1500.00],
                ['name' => 'Tablet iPad Air', 'brand' => 'Apple', 'price' => 4999.90, 'cost' => 2500.00],
                ['name' => 'Smartwatch Apple Watch', 'brand' => 'Apple', 'price' => 2499.90, 'cost' => 1250.00],
                ['name' => 'Fone JBL Tune 510BT', 'brand' => 'JBL', 'price' => 199.90, 'cost' => 100.00],
            ],
            'Livros' => [
                ['name' => 'O Alquimista', 'brand' => 'Paulo Coelho', 'price' => 29.90, 'cost' => 15.00],
                ['name' => 'Dom Casmurro', 'brand' => 'Machado de Assis', 'price' => 24.90, 'cost' => 12.50],
                ['name' => 'Harry Potter - Pedra Filosofal', 'brand' => 'J.K. Rowling', 'price' => 39.90, 'cost' => 20.00],
                ['name' => 'O Pequeno Príncipe', 'brand' => 'Antoine de Saint-Exupéry', 'price' => 19.90, 'cost' => 10.00],
                ['name' => 'Código Limpo', 'brand' => 'Robert C. Martin', 'price' => 89.90, 'cost' => 45.00],
            ],
            'Alimentos' => [
                ['name' => 'Whey Protein Optimum', 'brand' => 'Optimum Nutrition', 'price' => 189.90, 'cost' => 95.00],
                ['name' => 'Café Pilão Tradicional', 'brand' => 'Pilão', 'price' => 12.90, 'cost' => 6.50],
                ['name' => 'Açúcar Cristal União', 'brand' => 'União', 'price' => 4.99, 'cost' => 2.50],
                ['name' => 'Arroz Tio João', 'brand' => 'Tio João', 'price' => 8.99, 'cost' => 4.50],
                ['name' => 'Feijão Carioca Camil', 'brand' => 'Camil', 'price' => 7.99, 'cost' => 4.00],
            ]
        ];

        foreach ($categories as $category) {
            $categoryProducts = $productsByCategory[$category->name] ?? [];

            foreach ($categoryProducts as $productData) {
                Product::create([
                    'ean' => $faker->ean13,
                    'description' => $productData['name'],
                    'brand' => $productData['brand'],
                    'price' => $productData['price'],
                    'cost' => $productData['cost'],
                    'category_id' => $category->id,
                    'ncm' => $faker->numerify('########'),
                    'unit' => 'UN',
                    'gross_weight' => $faker->randomFloat(3, 0.1, 5.0),
                    'net_weight' => $faker->randomFloat(3, 0.1, 4.5),
                    'image' => 'https://via.placeholder.com/300x300?text=' . urlencode($productData['name']),
                    'source' => 'manual',
                    'complete' => true,
                    'created_at' => $faker->dateTimeBetween('-6 months', 'now'),
                    'updated_at' => now()
                ]);
            }

            // Add some random products for each category
            for ($i = 0; $i < 5; $i++) {
                Product::create([
                    'ean' => $faker->ean13,
                    'description' => $faker->words(3, true) . ' ' . $category->name,
                    'brand' => $faker->company,
                    'price' => $faker->randomFloat(2, 10, 1000),
                    'cost' => $faker->randomFloat(2, 5, 500),
                    'category_id' => $category->id,
                    'ncm' => $faker->numerify('########'),
                    'unit' => 'UN',
                    'gross_weight' => $faker->randomFloat(3, 0.1, 5.0),
                    'net_weight' => $faker->randomFloat(3, 0.1, 4.5),
                    'image' => 'https://via.placeholder.com/300x300?text=Product',
                    'source' => 'manual',
                    'complete' => $faker->boolean(80),
                    'created_at' => $faker->dateTimeBetween('-6 months', 'now'),
                    'updated_at' => now()
                ]);
            }
        }

        $this->command->info('Products seeded successfully!');
    }
}
