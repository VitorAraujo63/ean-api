<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProdutosExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Product::all(['id', 'description', 'ean', 'brand', 'ncm', 'unit', 'created_at']);
    }

    public function headings(): array
    {
        return ['ID', 'Descrição', 'EAN', 'Marca', 'NCM', 'Unidade', 'Criado em'];
    }
}
