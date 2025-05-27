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
                'description' => 'Bonés e chapéus diversos',
                'status' => 'ativo'
            ],
            [
                'name' => 'Tênis',
                'description' => 'Calçados esportivos e casuais',
                'status' => 'ativo'
            ],
            [
                'name' => 'Camisas',
                'description' => 'Camisas, camisetas e blusas',
                'status' => 'ativo'
            ],
            [
                'name' => 'Eletrônicos',
                'description' => 'Produtos eletrônicos diversos',
                'status' => 'ativo'
            ],
            [
                'name' => 'Livros',
                'description' => 'Livros e materiais de leitura',
                'status' => 'ativo'
            ],
            [
                'name' => 'Alimentos',
                'description' => 'Produtos alimentícios',
                'status' => 'ativo'
            ]
        ];

        foreach ($categories as $categoryData) {
            Category::create($categoryData);
        }
    }
}
