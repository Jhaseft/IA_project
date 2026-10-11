<?php

namespace App\Services;

/**
 * Problema 3.9 (AIMA): Misioneros y Caníbales.
 *
 * Estado = [m, c, b]
 *   m = misioneros en la orilla IZQUIERDA (0..3)
 *   c = caníbales   en la orilla IZQUIERDA (0..3)
 *   b = barco: 1 = en la izquierda, 0 = en la derecha
 *
 * Estado inicial Ei = [3, 3, 1]  (todos a la izquierda, barco a la izquierda)
 * Estado meta   Em = [0, 0, 0]  (todos a la derecha, barco a la derecha)
 *
 * Restricción: en NINGUNA orilla los caníbales pueden superar en número a
 * los misioneros (salvo que no haya misioneros en esa orilla).
 *
 * Expone DOS metodologías de búsqueda no informada: BFS (anchura) y DFS
 * (profundidad), y los 4 parámetros de evaluación: completitud, optimalidad,
 * complejidad temporal (CT) y espacial (CE).
 */
class BuscadorMisioneros
{
    public const INICIAL_DEFECTO = [3, 3, 1];
    public const META_DEFECTO    = [0, 0, 0];
    private const LIMITE_NODOS    = 100000;

    /** Las 5 combinaciones (misioneros, caníbales) que caben en el barco (1 o 2 personas). */
    private const CARGAS = [
        [1, 0], [2, 0], [0, 1], [0, 2], [1, 1],
    ];

    private static function catalogo(): array
    {
        return [
            'bfs' => [
                'nombre'      => 'Primero en Anchura (BFS)',
                'idea'        => 'Explora el árbol nivel por nivel con una cola FIFO.',
                'completitud' => 'Sí',
                'optimalidad' => 'Sí (costos de paso uniformes = 1 viaje)',
                'ctTeorica'   => 'O(b^d)',
                'ceTeorica'   => 'O(b^d) — guarda toda la frontera',
                'cuando'      => 'Garantiza la solución con menos viajes, pero usa más memoria.',
                'recomendado' => true,
            ],
            'dfs' => [
                'nombre'      => 'Primero en Profundidad (DFS)',
                'idea'        => 'Baja por una rama hasta el fondo con una pila LIFO.',
                'completitud' => 'Sí (espacio finito + control de visitados)',
                'optimalidad' => 'No (encuentra una solución, no necesariamente la más corta)',
                'ctTeorica'   => 'O(b^m)',
                'ceTeorica'   => 'O(b·m) — solo el camino actual (ventaja)',
                'cuando'      => 'Gasta poca memoria, pero la ruta puede tener viajes de más.',
                'recomendado' => false,
            ],
        ];
    }

    public static function algoritmos(): array
    {
        $r = [];
        foreach (self::catalogo() as $id => $a) {
            $r[] = ['id' => $id, 'nombre' => $a['nombre']];
        }
        return $r;
    }

    public static function algoritmosDetallados(): array
    {
        $r = [];
        foreach (self::catalogo() as $id => $a) {
            $r[] = array_merge(['id' => $id], $a);
        }
        return $r;
    }

    private static function teoria(string $metodo): array
    {
        return self::catalogo()[$metodo] ?? self::catalogo()['bfs'];
    }

    // ------------------------------------------------------------------
    //  Punto de entrada
    // ------------------------------------------------------------------
    public static function resolver(string $metodo, array $inicial, array $meta): array
    {
        $r = $metodo === 'dfs'
            ? self::dfs($inicial, $meta)
            : self::bfs($inicial, $meta);

        $t = self::teoria($metodo);
        $nombre = collect(self::algoritmos())->firstWhere('id', $metodo)['nombre'] ?? $metodo;

        return array_merge($r, [
            'metodo'       => $metodo,
            'nombreMetodo' => $nombre,
            'parametros'   => [
                'completitud'         => $t['completitud'],
                'optimalidad'         => $t['optimalidad'],
                'ctTeorica'           => $t['ctTeorica'],
                'ceTeorica'           => $t['ceTeorica'],
                'complejidadTemporal' => $r['nodosGenerados'], // CT medida (u/t)
                'complejidadEspacial' => $r['maxEnMemoria'],   // CE medida (u/e)
            ],
        ]);
    }

    // ------------------------------------------------------------------
    //  BFS — Primero en Anchura (cola FIFO)
    // ------------------------------------------------------------------
    private static function bfs(array $inicial, array $meta): array
    {
        $claveIni = self::clave($inicial);
        $frontera = [$inicial];
        $ptr = 0;
        $metadatos = [$claveIni => ['estado' => $inicial, 'padre' => null, 'movimiento' => null, 'profundidad' => 0]];
        $visitados = [$claveIni => true];

        $nodosGenerados = 1;
        $nodosExpandidos = 0;
        $maxEnMemoria = 1;
        $porNivel = [0 => 1];

        while ($ptr < count($frontera)) {
            $maxEnMemoria = max($maxEnMemoria, count($frontera) - $ptr);
            $estado = $frontera[$ptr++];
            $k = self::clave($estado);
            $nodosExpandidos++;

            if ($estado === $meta) {
                return self::resultado(true, self::reconstruir($metadatos, $k), $nodosGenerados, $nodosExpandidos, $maxEnMemoria, $porNivel);
            }
            if ($nodosGenerados > self::LIMITE_NODOS) break;

            foreach (self::sucesores($estado) as $s) {
                $sk = self::clave($s['estado']);
                if (isset($visitados[$sk])) continue;
                $visitados[$sk] = true;
                $nodosGenerados++;
                $prof = $metadatos[$k]['profundidad'] + 1;
                $metadatos[$sk] = ['estado' => $s['estado'], 'padre' => $k, 'movimiento' => $s['movimiento'], 'profundidad' => $prof];
                $porNivel[$prof] = ($porNivel[$prof] ?? 0) + 1;
                $frontera[] = $s['estado'];
            }
        }

        return self::resultado(false, [], $nodosGenerados, $nodosExpandidos, $maxEnMemoria, $porNivel);
    }

    // ------------------------------------------------------------------
    //  DFS — Primero en Profundidad (pila LIFO + visitados)
    // ------------------------------------------------------------------
    private static function dfs(array $inicial, array $meta): array
    {
        $claveIni = self::clave($inicial);
        $pila = [$claveIni];
        $metadatos = [$claveIni => ['estado' => $inicial, 'padre' => null, 'movimiento' => null, 'profundidad' => 0]];
        $visitados = [$claveIni => true];

        $nodosGenerados = 1;
        $nodosExpandidos = 0;
        $maxEnMemoria = 1;
        $porNivel = [0 => 1];

        while (!empty($pila)) {
            $maxEnMemoria = max($maxEnMemoria, count($pila));
            $k = array_pop($pila);
            $nodo = $metadatos[$k];
            $nodosExpandidos++;

            if ($nodo['estado'] === $meta) {
                return self::resultado(true, self::reconstruir($metadatos, $k), $nodosGenerados, $nodosExpandidos, $maxEnMemoria, $porNivel);
            }
            if ($nodosGenerados > self::LIMITE_NODOS) break;

            foreach (self::sucesores($nodo['estado']) as $s) {
                $sk = self::clave($s['estado']);
                if (isset($visitados[$sk])) continue;
                $visitados[$sk] = true;
                $nodosGenerados++;
                $prof = $nodo['profundidad'] + 1;
                $metadatos[$sk] = ['estado' => $s['estado'], 'padre' => $k, 'movimiento' => $s['movimiento'], 'profundidad' => $prof];
                $porNivel[$prof] = ($porNivel[$prof] ?? 0) + 1;
                $pila[] = $sk;
            }
        }

        return self::resultado(false, [], $nodosGenerados, $nodosExpandidos, $maxEnMemoria, $porNivel);
    }

    // ------------------------------------------------------------------
    //  Sucesores: aplicar cada carga válida del barco
    // ------------------------------------------------------------------
    private static function sucesores(array $estado): array
    {
        [$m, $c, $b] = $estado;
        $res = [];

        foreach (self::CARGAS as [$dm, $dc]) {
            if ($b === 1) {
                // Barco en la izquierda → la carga cruza hacia la derecha
                $nuevo = [$m - $dm, $c - $dc, 0];
            } else {
                // Barco en la derecha → la carga regresa a la izquierda
                $nuevo = [$m + $dm, $c + $dc, 1];
            }

            if (self::esValido($nuevo)) {
                $res[] = ['estado' => $nuevo, 'movimiento' => self::etiqueta($dm, $dc, $b)];
            }
        }

        return $res;
    }

    /** Un estado es válido si está en rango y los caníbales no superan a los misioneros en ninguna orilla. */
    private static function esValido(array $e): bool
    {
        [$m, $c] = $e;
        if ($m < 0 || $m > 3 || $c < 0 || $c > 3) return false;

        $mr = 3 - $m; // misioneros en la derecha
        $cr = 3 - $c; // caníbales en la derecha

        if ($m > 0 && $c > $m) return false;   // izquierda en peligro
        if ($mr > 0 && $cr > $mr) return false; // derecha en peligro

        return true;
    }

    private static function etiqueta(int $dm, int $dc, int $b): string
    {
        $partes = [];
        if ($dm > 0) $partes[] = "{$dm}M";
        if ($dc > 0) $partes[] = "{$dc}C";
        $carga = implode(' + ', $partes);
        // b=1: el barco estaba en la izquierda y cruza a la derecha (→)
        return $b === 1 ? "{$carga} →" : "← {$carga}";
    }

    // ------------------------------------------------------------------
    //  Helpers de resultado
    // ------------------------------------------------------------------
    private static function resultado(bool $encontrada, array $camino, int $ct, int $exp, int $ce, array $porNivel): array
    {
        ksort($porNivel);
        return [
            'encontrada'      => $encontrada,
            'camino'          => $camino,
            'pasos'           => $encontrada ? count($camino) - 1 : null,
            'nodosGenerados'  => $ct,
            'nodosExpandidos' => $exp,
            'maxEnMemoria'    => $ce,
            'porNivel'        => $porNivel,
            'sumaPorNivel'    => array_sum($porNivel),
        ];
    }

    private static function reconstruir(array $metadatos, string $claveMeta): array
    {
        $camino = [];
        $k = $claveMeta;
        while ($k !== null) {
            $n = $metadatos[$k];
            array_unshift($camino, [
                'estado'      => $n['estado'],
                'movimiento'  => $n['movimiento'],
                'profundidad' => $n['profundidad'],
            ]);
            $k = $n['padre'];
        }
        return $camino;
    }

    private static function clave(array $estado): string
    {
        return implode(',', $estado);
    }

    public static function esEstadoValidoEntrada(array $estado): bool
    {
        return count($estado) === 3
            && $estado[0] >= 0 && $estado[0] <= 3
            && $estado[1] >= 0 && $estado[1] <= 3
            && in_array($estado[2], [0, 1], true)
            && self::esValido($estado);
    }
}
