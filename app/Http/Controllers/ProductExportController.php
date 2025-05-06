<?php

namespace App\Http\Controllers;

use App\Exports\ProdutosExport;
use Maatwebsite\Excel\Facades\Excel;

/**
 * @OA\Tag(
 *     name="Exportação",
 *     description="Operações para exportação de dados."
 * )
 */
class ProductExportController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/exportar-produtos",
     *     tags={"Exportação"},
     *     summary="Exportar produtos para CSV",
     *     @OA\Response(response=200, description="Arquivo CSV gerado com sucesso."),
     *     @OA\Response(response=500, description="Erro ao gerar arquivo CSV.")
     * )
     */
    public function exportCsv()
    {
        return Excel::download(new ProdutosExport, 'produtos.csv', \Maatwebsite\Excel\Excel::CSV, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
