<?php

namespace App\Services;

/**
 * Lógica de búsqueda en IA para el 8-puzzle.
 *
 * Estado = arreglo de 9 enteros (0 = hueco).
 * Expone DOS metodologías (BFS y DFS) y los 4 parámetros de IA:
 * completitud, optimalidad, complejidad temporal (CT) y espacial (CE).
 *
 * Para agregar una 3ª metodología (ej. A*): escribe un método privado con la
 * misma forma de retorno que bfs()/dfs() y regístralo en algoritmos() + resolver().
 */
class BuscadorPuzzle
{
    public const META_DEFECTO = [1, 2, 3, 4, 5, 6, 7, 8, 0];
    private const LIMITE_NODOS = 200000;

    /**
     * Catálogo único con toda la info de cada metodología:
     * nombre, idea, los 4 parámetros teóricos y cuándo conviene usarla.
     */
    private static function catalogo(): array
    {
        return [
            'bfs' => [
                'nombre'      => 'Primero en Anchura (BFS)',
                'idea'        => 'Explora el árbol nivel por nivel (cola FIFO).',
                'completitud' => 'Sí',
                'optimalidad' => 'Sí (con costos de paso uniformes)',
                'ctTeorica'   => 'O(b^d)',
                'ceTeorica'   => 'O(b^d) — guarda toda la frontera',
                'cuando'      => 'Garantiza la solución más corta, pero consume mucha memoria.',
                'recomendado' => false,
            ],
            'dfs' => [
                'nombre'      => 'Primero en Profundidad (DFS)',
                'idea'        => 'Baja por una rama hasta el fondo (pila LIFO).',
                'completitud' => 'Sí (en espacio finito con control de visitados)',
                'optimalidad' => 'No (encuentra una solución, no la más corta)',
                'ctTeorica'   => 'O(b^m)',
                'ceTeorica'   => 'O(b·m) — solo el camino actual (ventaja)',
                'cuando'      => 'Usa poca memoria, pero puede tardar y NO da la solución óptima.',
                'recomendado' => false,
            ],
            'astar' => [
                'nombre'      => 'A* (óptimo informado)',
                'idea'        => 'Expande el nodo con menor f(x) = g(x) + h(x) (costo + heurística).',
                'completitud' => 'Sí',
                'optimalidad' => 'Sí (si la heurística es admisible)',
                'ctTeorica'   => 'Exponencial en el peor caso; mucho menor con buena h',
                'ceTeorica'   => 'O(nodos generados) — guarda frontera y explorados',
                'cuando'      => 'La más recomendada: encuentra el óptimo explorando muchos menos nodos.',
                'recomendado' => true,
            ],
        ];
    }

    /** Lista de metodologías disponibles (para el selector del frontend). */
    public static function algoritmos(): array
    {
        $r = [];
        foreach (self::catalogo() as $id => $a) {
            $r[] = ['id' => $id, 'nombre' => $a['nombre']];
        }
        return $r;
    }

    /** Catálogo completo (para las tarjetas del Welcome). */
    public static function algoritmosDetallados(): array
    {
        $r = [];
        foreach (self::catalogo() as $id => $a) {
            $r[] = array_merge(['id' => $id], $a);
        }
        return $r;
    }

    /** Parámetros TEÓRICOS de cada metodología (lo del examen). */
    private static function teoria(string $metodo): array
    {
        return self::catalogo()[$metodo];
    }

    // ------------------------------------------------------------------
    //  Punto de entrada
    // ------------------------------------------------------------------
    public static function resolver(string $metodo, array $inicial, array $meta): array
    {
        $r = match ($metodo) {
            'dfs'   => self::dfs($inicial, $meta),
            'astar' => self::aStar($inicial, $meta),
            default => self::bfs($inicial, $meta),
        };

        $t = self::teoria($metodo);
        $nombre = collect(self::algoritmos())->firstWhere('id', $metodo)['nombre'] ?? $metodo;

        return array_merge($r, [
            'metodo'      => $metodo,
            'nombreMetodo' => $nombre,
            'parametros'  => [
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
    //  BFS — Primero en Anchura (nivel por nivel, cola FIFO)
    // ------------------------------------------------------------------
    private static function bfs(array $inicial, array $meta): array
    {
        $claveIni = self::clave($inicial);
        $frontera = [$inicial];               // cola (usamos puntero para no hacer array_shift)
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
    //  DFS — Primero en Profundidad (pila LIFO + control de visitados)
    //  En espacio finito con visitados es COMPLETO (termina y encuentra
    //  la meta si existe), pero la solución NO es óptima (puede ser larga).
    // ------------------------------------------------------------------
    private static function dfs(array $inicial, array $meta): array
    {
        $claveIni = self::clave($inicial);
        $pila = [$claveIni];
        $metadatos = [$claveIni => ['estado' => $inicial, 'padre' => null, 'movimiento' => null, 'profundidad' => 0]];
        $visitados = [$claveIni => true];

        $nodosGenerados = 1;
        $nodosExpandidos = 0;
        $maxEnMemoria = 1; // pico de la pila = camino actual (ventaja de DFS)
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
    //  A* — Búsqueda informada:  f(x) = g(x) + h(x)
    //  g = costo de ruta (nº de movimientos);  h = heurística (fichas
    //  mal colocadas). Siempre expande el nodo de menor f. El costo de
    //  ruta (g) garantiza que la solución sea óptima.
    // ------------------------------------------------------------------
    private static function aStar(array $inicial, array $meta): array
    {
        $claveIni = self::clave($inicial);
        // Lista ABIERTA: clave => ['g'=>costo ruta, 'f'=>g+h]
        $abierta = [$claveIni => ['g' => 0, 'f' => self::heuristica($inicial, $meta)]];
        $cerrada = [];
        $metadatos = [$claveIni => ['estado' => $inicial, 'padre' => null, 'movimiento' => null, 'profundidad' => 0]];

        $nodosGenerados = 1;
        $nodosExpandidos = 0;
        $maxEnMemoria = 1;
        $porNivel = [0 => 1];

        while (! empty($abierta)) {
            $maxEnMemoria = max($maxEnMemoria, count($abierta));

            // Seleccionar el nodo con MENOR f(x) (desempate: menor h → más cerca de la meta)
            $mejorK = null;
            $mejorF = INF;
            foreach ($abierta as $k => $info) {
                if ($info['f'] < $mejorF) {
                    $mejorF = $info['f'];
                    $mejorK = $k;
                }
            }

            $g = $abierta[$mejorK]['g'];
            unset($abierta[$mejorK]);
            $cerrada[$mejorK] = true;
            $nodo = $metadatos[$mejorK];
            $nodosExpandidos++;

            if ($nodo['estado'] === $meta) {
                $camino = self::reconstruir($metadatos, $mejorK);
                // Enriquecemos el camino con g, h y f para mostrar f(x)=g+h
                foreach ($camino as &$p) {
                    $p['g'] = $p['profundidad'];
                    $p['h'] = self::heuristica($p['estado'], $meta);
                    $p['f'] = $p['g'] + $p['h'];
                }
                unset($p);
                return self::resultado(true, $camino, $nodosGenerados, $nodosExpandidos, $maxEnMemoria, $porNivel);
            }
            if ($nodosGenerados > self::LIMITE_NODOS) break;

            foreach (self::sucesores($nodo['estado']) as $s) {
                $sk = self::clave($s['estado']);
                if (isset($cerrada[$sk])) continue;

                $ng = $g + 1;
                if (isset($abierta[$sk]) && $abierta[$sk]['g'] <= $ng) continue; // ya hay un camino igual o mejor

                if (! isset($metadatos[$sk])) {
                    $nodosGenerados++;
                    $porNivel[$ng] = ($porNivel[$ng] ?? 0) + 1;
                }
                $metadatos[$sk] = ['estado' => $s['estado'], 'padre' => $mejorK, 'movimiento' => $s['movimiento'], 'profundidad' => $ng];
                $abierta[$sk] = ['g' => $ng, 'f' => $ng + self::heuristica($s['estado'], $meta)];
            }
        }

        return self::resultado(false, [], $nodosGenerados, $nodosExpandidos, $maxEnMemoria, $porNivel);
    }

    /** Heurística h(x): nº de fichas mal colocadas (excluye el hueco). Admisible. */
    private static function heuristica(array $estado, array $meta): int
    {
        $c = 0;
        for ($i = 0; $i < 9; $i++) {
            if ($estado[$i] !== 0 && $estado[$i] !== $meta[$i]) $c++;
        }
        return $c;
    }

    // ------------------------------------------------------------------
    //  Helpers de resultado y de puzzle
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

    /** Sucesores: mueve el hueco en las 4 direcciones válidas. */
    private static function sucesores(array $estado): array
    {
        $res = [];
        $i = array_search(0, $estado, true);
        $fila = intdiv($i, 3);
        $col = $i % 3;

        $movs = [
            ['df' => -1, 'dc' => 0, 'n' => 'Arriba'],
            ['df' => 1,  'dc' => 0, 'n' => 'Abajo'],
            ['df' => 0,  'dc' => -1, 'n' => 'Izquierda'],
            ['df' => 0,  'dc' => 1,  'n' => 'Derecha'],
        ];
        foreach ($movs as $m) {
            $nf = $fila + $m['df'];
            $nc = $col + $m['dc'];
            if ($nf < 0 || $nf > 2 || $nc < 0 || $nc > 2) continue;
            $j = $nf * 3 + $nc;
            $nuevo = $estado;
            [$nuevo[$i], $nuevo[$j]] = [$nuevo[$j], $nuevo[$i]];
            $res[] = ['estado' => $nuevo, 'movimiento' => $m['n']];
        }
        return $res;
    }

    // ------------------------------------------------------------------
    //  Validaciones (públicas: las usa el controlador)
    // ------------------------------------------------------------------
    public static function esEstadoValido(array $estado): bool
    {
        if (count($estado) !== 9) return false;
        $ordenado = $estado;
        sort($ordenado);
        return $ordenado === [0, 1, 2, 3, 4, 5, 6, 7, 8];
    }

    public static function esResoluble(array $estado, array $meta): bool
    {
        return self::inversiones($estado) % 2 === self::inversiones($meta) % 2;
    }

    private static function inversiones(array $estado): int
    {
        $a = array_values(array_filter($estado, fn ($x) => $x !== 0));
        $inv = 0;
        for ($i = 0; $i < count($a); $i++) {
            for ($j = $i + 1; $j < count($a); $j++) {
                if ($a[$i] > $a[$j]) $inv++;
            }
        }
        return $inv;
    }
}
