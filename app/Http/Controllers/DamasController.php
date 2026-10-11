<?php

namespace App\Http\Controllers;

use App\Services\JuegoDamas;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DamasController extends Controller
{
    /** Página del juego de Damas (Inertia + React). */
    public function index()
    {
        $tablero = JuegoDamas::tableroInicial();
        return Inertia::render('Damas', [
            'tableroInicial' => $tablero,
            // El humano (blancas, lado -1) mueve primero
            'movimientosIniciales' => JuegoDamas::generarMovimientos($tablero, -1),
        ]);
    }

    /** Devuelve los movimientos legales de un lado (para resaltar en el tablero). */
    public function movimientos(Request $request)
    {
        $datos = $this->validarTablero($request, ['lado' => 'required|integer|in:-1,1']);
        return response()->json([
            'movimientos' => JuegoDamas::generarMovimientos($datos['tablero'], (int) $datos['lado']),
        ]);
    }

    /** Aplica una jugada (humano o IA) y devuelve el estado de la partida. */
    public function aplicar(Request $request)
    {
        $datos = $this->validarTablero($request, [
            'lado'                => 'required|integer|in:-1,1',
            'secuencia'           => 'required|array|min:2',
            'secuencia.*'         => 'integer|min:0|max:63',
        ]);

        $lado = (int) $datos['lado'];
        $mov = JuegoDamas::buscarMovimientoLegal($datos['tablero'], $lado, array_map('intval', $datos['secuencia']));
        if (! $mov) {
            return response()->json(['error' => 'Movimiento ilegal.'], 422);
        }

        $nuevo = JuegoDamas::aplicar($datos['tablero'], $mov);
        $ladoSiguiente = -$lado;
        $estado = JuegoDamas::estado($nuevo, $ladoSiguiente);

        return response()->json([
            'tablero'       => $nuevo,
            'ladoSiguiente' => $ladoSiguiente,
            'terminado'     => $estado['terminado'],
            'ganador'       => $estado['ganador'],
            'movimientos'   => $estado['terminado'] ? [] : JuegoDamas::generarMovimientos($nuevo, $ladoSiguiente),
        ]);
    }

    /** Analiza la posición con Minimax o Alfa-Beta (sin aplicar la jugada). */
    public function analizar(Request $request)
    {
        $datos = $this->validarTablero($request, [
            'lado'        => 'required|integer|in:-1,1',
            'profundidad' => 'required|integer|min:1|max:5',
            'metodo'      => 'required|string|in:minimax,alfabeta',
        ]);

        $analisis = JuegoDamas::analizar(
            $datos['tablero'],
            (int) $datos['lado'],
            (int) $datos['profundidad'],
            $datos['metodo'],
        );

        return response()->json($analisis);
    }

    private function validarTablero(Request $request, array $reglas): array
    {
        return $request->validate(array_merge([
            'tablero'   => 'required|array|size:64',
            'tablero.*' => 'integer|min:-2|max:2',
        ], $reglas));
    }
}
