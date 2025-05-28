<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            [
                'name' => 'Boné',
                'description' => 'Bonés e chapéus diversos para proteção solar e estilo',
                'status' => 'ativo',
                'image' => 'https://example.com/images/bone.jpg'
            ],
            [
                'name' => 'Tênis',
                'description' => 'Calçados esportivos e casuais para todas as idades',
                'status' => 'ativo',
                'image' => 'https://example.com/images/tenis.jpg'
            ],
            [
                'name' => 'Camisas',
                'description' => 'Camisas, camisetas e blusas masculinas e femininas',
                'status' => 'ativo',
                'image' => 'https://example.com/images/camisas.jpg'
            ],
            [
                'name' => 'Eletrônicos',
                'description' => 'Produtos eletrônicos, gadgets e acessórios tecnológicos',
                'status' => 'ativo',
                'image' => 'https://example.com/images/eletronicos.jpg'
            ],
            [
                'name' => 'Livros',
                'description' => 'Livros, revistas e materiais de leitura diversos',
                'status' => 'ativo',
                'image' => 'https://example.com/images/livros.jpg'
            ],
            [
                'name' => 'Alimentos',
                'description' => 'Produtos alimentícios, bebidas e suplementos',
                'status' => 'ativo',
                'image' => 'https://example.com/images/alimentos.jpg'
            ],
            [
                'name' => 'Casa e Jardim',
                'description' => 'Produtos para casa, decoração e jardinagem',
                'status' => 'ativo',
                'image' => 'https://example.com/images/casa.jpg'
            ],
            [
                'name' => 'Esportes',
                'description' => 'Equipamentos esportivos e acessórios fitness',
                'status' => 'ativo',
                'image' => 'https://example.com/images/esportes.jpg'
            ]
        ];

        foreach ($categories as $categoryData) {
            Category::create($categoryData);
        }

        $this->command->info('Categories seeded successfully!');
    }
}
