<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BeneficioController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/beneficios-filtrados",
     *     summary="Obtiene beneficios filtrados por monto, agrupados por año",
     *     @OA\Response(
     *         response=200,
     *         description="Lista de beneficios agrupados"
     *     )
     * )
     */
    public function filtrados()
    {
        $responseFiltros = Http::get('https://run.mocky.io/v3/b0ddc735-cfc9-410e-9365-137e04e33fcf');
        $responseFichas = Http::get('https://run.mocky.io/v3/4654cafa-58d8-4846-9256-79841b29a687');
        $responseBeneficios = Http::get('https://run.mocky.io/v3/8f75c4b5-ad90-49bb-bc52-f1fc0b4aad02'); 

        if (!$responseFiltros->successful() || !$responseFichas->successful() || !$responseBeneficios->successful()) {
            return response()->json([
                'mensaje' => 'Error al obtener datos de los servicios externos.',
            ], 500);
        }

        $filtros = collect($responseFiltros->json('data', []));
        $fichas = collect($responseFichas->json('data', []));
        $beneficios = collect($responseBeneficios->json('data', []));

        if ($beneficios->isEmpty()) {
            return response()->json([
                'mensaje' => 'No hay beneficios disponibles en la respuesta.',
                'filtrados' => []
            ]);
        }

        $filtrados = $beneficios->map(function ($beneficio) use ($filtros, $fichas) {
            if (!is_array($beneficio) || !isset($beneficio['id_programa'], $beneficio['monto'])) {
                return null;
            }

            $filtro = $filtros->firstWhere('id_programa', $beneficio['id_programa']);
            if (!$filtro || !isset($filtro['min'], $filtro['max'], $filtro['ficha_id'])) {
                return null;
            }

            if ($beneficio['monto'] < $filtro['min'] || $beneficio['monto'] > $filtro['max']) {
                return null;
            }

            $ficha = $fichas->firstWhere('id', $filtro['ficha_id']);

            $fecha = $beneficio['fecha'] ?? null;
            $ano = $fecha ? date('Y', strtotime($fecha)) : null;

            return [
                'id_programa' => $beneficio['id_programa'],
                'monto' => $beneficio['monto'],
                'fecha' => $fecha,
                'fecha_recepcion' => $beneficio['fecha_recepcion'] ?? null,
                'ano' => $ano,
                'view' => true,
                'ficha' => $ficha,
            ];
        })->filter()->values();

        $agrupadosPorAno = $filtrados
            ->filter(fn($item) => !is_null($item['ano']))
            ->groupBy(fn($item) => (int) $item['ano'])
            ->map(fn($items, $year) => [
                'year' => $year,
                'num' => $items->count(),
                'beneficios' => $items->values()
            ])
            ->sortByDesc('year')
            ->values();

        return response()->json([
            'code' => 200,
            'success' => true,
            'data' => $agrupadosPorAno,
        ]);
    }
}
