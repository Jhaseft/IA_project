<?php

namespace App\Http\Controllers;

use App\Services\BuscadorMisioneros;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MisionerosController extends Controller
{
    /** Página del problema Misioneros y Caníbales (Inertia + React). */
    public function index()
    {
        return Inertia::render('Misioneros', [
            'algoritmos'     => BuscadorMisioneros::algoritmos(),
            'inicialDefecto' => BuscadorMisioneros::INICIAL_DEFECTO,
            'metaDefecto'    => BuscadorMisioneros::META_DEFECTO,
        ]);
    }

    /** Resuelve con la metodología elegida (BFS o DFS). Devuelve JSON. */
    public function resolver(Request $request)
    {
        $datos = $request->validate([
            'inicial'   => 'required|array|size:3',
            'inicial.*' => 'integer|min:0|max:3',
            'meta'      => 'required|array|size:3',
            'meta.*'    => 'integer|min:0|max:3',
            'metodo'    => 'required|string|in:bfs,dfs',
        ]);

        $inicial = array_map('intval', $datos['inicial']);
        $meta    = array_map('intval', $datos['meta']);

        if (! BuscadorMisioneros::esEstadoValidoEntrada($inicial)) {
            return response()->json([
                'error' => 'El estado inicial no es válido (fuera de rango o los caníbales superan a los misioneros).',
            ], 422);
        }

        $resultado = BuscadorMisioneros::resolver($datos['metodo'], $inicial, $meta);

        return response()->json($resultado);
    }
}
