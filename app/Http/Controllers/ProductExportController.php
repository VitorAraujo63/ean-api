<?php

namespace App\Http\Controllers;

use App\Exports\ProdutosExport;
use Maatwebsite\Excel\Facades\Excel;

class ProductExportController extends Controller
{
    public function exportCsv()
    {
        return Excel::download(new ProdutosExport, 'produtos.csv');
    }
}
