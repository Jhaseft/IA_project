<?php

namespace App\Http\Controllers;

use App\Services\BuscadorPuzzle;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BuscadorController extends Controller
{
    /** Página del buscador (Inertia + React). */
    public function index()
    {
        return Inertia::render('Buscador', [
            'algoritmos'   => BuscadorPuzzle::algoritmos(),
            'metaDefecto'  => BuscadorPuzzle::META_DEFECTO,
        ]);
    }

    /** Resuelve un puzzle con la metodología elegida. Devuelve JSON. */
    public function resolver(Request $request)
    {
        $datos = $request->validate([
            'inicial'   => 'required|array|size:9',
            'inicial.*' => 'integer|min:0|max:8',
            'meta'      => 'required|array|size:9',
            'meta.*'    => 'integer|min:0|max:8',
            'metodo'    => 'required|string|in:bfs,dfs,astar',
        ]);

        $inicial = array_map('intval', $datos['inicial']);
        $meta    = array_map('intval', $datos['meta']);

        if (! BuscadorPuzzle::esEstadoValido($inicial) || ! BuscadorPuzzle::esEstadoValido($meta)) {
            return response()->json([
                'error' => 'Cada tablero debe ser una permutación de 0 a 8 (sin repetir).',
            ], 422);
        }

        if (! BuscadorPuzzle::esResoluble($inicial, $meta)) {
            return response()->json([
                'resoluble' => false,
                'mensaje'   => 'Este estado NO es resoluble hacia la meta (distinta paridad de inversiones).',
            ]);
        }

        $resultado = BuscadorPuzzle::resolver($datos['metodo'], $inicial, $meta);

        return response()->json(array_merge(['resoluble' => true], $resultado));
    }
}
