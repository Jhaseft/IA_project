<?php

namespace App\Services;

/**
 * Juego de Damas (8x8) con búsqueda CON ADVERSARIO (Capítulo 5).
 *
 * Implementa DOS metodologías: Minimax y Poda Alfa-Beta. Ambas eligen la MISMA
 * jugada, pero Alfa-Beta evalúa muchos menos nodos (se ven las podas).
 *
 * Tablero: array de 64 enteros, índice = fila*8 + columna (fila 0 = arriba).
 *   0  = casilla vacía
 *   1  = peón  IA     (negras, arriba, avanza hacia abajo)   → MAX (positivo)
 *   2  = dama  IA
 *  -1  = peón  humano (blancas, abajo, avanza hacia arriba)  → MIN (negativo)
 *  -2  = dama  humano
 *
 * Reglas: movimiento diagonal, captura por salto, captura OBLIGATORIA, cadenas
 * de multi-captura, coronación a dama al llegar a la última fila.
 */
class JuegoDamas
{
    private const VALOR_PEON = 100;
    private const VALOR_DAMA = 170;
    private const BONO_AVANCE = 5;
    private const GANA = 100000;
    private const LIMITE_NODOS = 800000;

    private const DIRS = [[-1, -1], [-1, 1], [1, -1], [1, 1]];

    // ------------------------------------------------------------------
    //  Catálogo de metodologías (para la portada y las tarjetas)
    // ------------------------------------------------------------------
    public static function algoritmosDetallados(): array
    {
        return [
            [
                'id' => 'minimax', 'nombre' => 'Minimax',
                'idea' => 'Construye el árbol de juego: la IA MAXIMIZA y el rival MINIMIZA. Evalúa TODAS las hojas.',
                'completitud' => 'Sí (hasta la profundidad fijada)',
                'optimalidad' => 'Sí (óptima al límite de profundidad)',
                'ctTeorica' => 'O(b^m)',
                'ceTeorica' => 'O(b·m)',
                'cuando' => 'Es la base teórica, pero explora el árbol completo.',
                'recomendado' => false,
            ],
            [
                'id' => 'alfabeta', 'nombre' => 'Poda Alfa-Beta',
                'idea' => 'Igual que Minimax pero poda ramas que ya no pueden cambiar el resultado (α ≥ β).',
                'completitud' => 'Sí (hasta la profundidad fijada)',
                'optimalidad' => 'Sí (da el MISMO valor que Minimax)',
                'ctTeorica' => 'O(b^(m/2)) en el mejor caso',
                'ceTeorica' => 'O(b·m)',
                'cuando' => 'La recomendada: mismo resultado evaluando muchos menos nodos.',
                'recomendado' => true,
            ],
        ];
    }

    // ------------------------------------------------------------------
    //  Tablero inicial (12 piezas por bando)
    // ------------------------------------------------------------------
    public static function tableroInicial(): array
    {
        $b = array_fill(0, 64, 0);
        for ($i = 0; $i < 64; $i++) {
            [$r, $c] = [intdiv($i, 8), $i % 8];
            if (($r + $c) % 2 === 1) {                 // solo casillas oscuras
                if ($r < 3) $b[$i] = 1;                // IA arriba
                elseif ($r > 4) $b[$i] = -1;           // humano abajo
            }
        }
        return $b;
    }

    // ------------------------------------------------------------------
    //  Generación de movimientos (captura obligatoria)
    //  $lado:  1 = IA,  -1 = humano
    //  Devuelve: [ ['secuencia'=>[idx..], 'capturas'=>[idx..], 'notacion'=>'..'], ... ]
    // ------------------------------------------------------------------
    public static function generarMovimientos(array $b, int $lado): array
    {
        $capturas = [];
        $simples = [];

        for ($i = 0; $i < 64; $i++) {
            if ($b[$i] === 0 || self::signo($b[$i]) !== $lado) continue;

            $cs = self::capturasDesde($b, $i, $b[$i]);
            foreach ($cs as $c) $capturas[] = $c;

            if (empty($cs)) {
                foreach (self::dirsPieza($b[$i]) as [$dr, $dc]) {
                    [$r, $cc] = [intdiv($i, 8), $i % 8];
                    $nr = $r + $dr; $nc = $cc + $dc;
                    if (!self::enTablero($nr, $nc)) continue;
                    $j = $nr * 8 + $nc;
                    if ($b[$j] === 0) {
                        $simples[] = ['secuencia' => [$i, $j], 'capturas' => [], 'notacion' => self::notacion([$i, $j], false)];
                    }
                }
            }
        }

        // Captura obligatoria: si existe al menos una captura, solo se permiten capturas.
        return !empty($capturas) ? $capturas : $simples;
    }

    /** Todas las cadenas de captura que arrancan en $idx con la pieza $v. */
    private static function capturasDesde(array $b, int $idx, int $v): array
    {
        $secuencias = [];
        [$r, $c] = [intdiv($idx, 8), $idx % 8];

        foreach (self::dirsCapturaPieza($v) as [$dr, $dc]) {
            $rm = $r + $dr; $cm = $c + $dc;             // casilla del enemigo
            $rd = $r + 2 * $dr; $cd = $c + 2 * $dc;     // casilla de aterrizaje
            if (!self::enTablero($rd, $cd)) continue;
            $m = $rm * 8 + $cm; $d = $rd * 8 + $cd;

            if ($b[$m] !== 0 && $b[$m] * $v < 0 && $b[$d] === 0) {
                // Ejecutar el salto sobre una copia
                $nb = $b;
                $nb[$idx] = 0;
                $nb[$m] = 0;
                $nv = $v;
                $corona = false;
                if (abs($v) === 1 && (($v === 1 && $rd === 7) || ($v === -1 && $rd === 0))) {
                    $nv = $v * 2;
                    $corona = true;
                }
                $nb[$d] = $nv;

                // La cadena continúa solo si no coronó en este salto
                $sub = $corona ? [] : self::capturasDesde($nb, $d, $nv);

                if (empty($sub)) {
                    $secuencias[] = ['secuencia' => [$idx, $d], 'capturas' => [$m], 'notacion' => self::notacion([$idx, $d], true)];
                } else {
                    foreach ($sub as $x) {
                        $seq = array_merge([$idx], $x['secuencia']);
                        $caps = array_merge([$m], $x['capturas']);
                        $secuencias[] = ['secuencia' => $seq, 'capturas' => $caps, 'notacion' => self::notacion($seq, true)];
                    }
                }
            }
        }
        return $secuencias;
    }

    /** Aplica un movimiento y devuelve el nuevo tablero (con coronación). */
    public static function aplicar(array $b, array $mov): array
    {
        $nb = $b;
        $seq = $mov['secuencia'];
        $from = $seq[0];
        $to = $seq[count($seq) - 1];
        $v = $nb[$from];
        $nb[$from] = 0;
        foreach ($mov['capturas'] as $cap) $nb[$cap] = 0;

        [$rto] = [intdiv($to, 8)];
        if (abs($v) === 1 && (($v === 1 && $rto === 7) || ($v === -1 && $rto === 0))) {
            $v *= 2;
        }
        $nb[$to] = $v;
        return $nb;
    }

    // ------------------------------------------------------------------
    //  Heurística f(v) desde el punto de vista de la IA (MAX)
    //  Positivo = la IA va ganando;  negativo = el humano va ganando.
    // ------------------------------------------------------------------
    public static function evaluar(array $b): int
    {
        $val = 0;
        for ($i = 0; $i < 64; $i++) {
            $v = $b[$i];
            if ($v === 0) continue;
            $r = intdiv($i, 8);
            if ($v === 1)       $val += self::VALOR_PEON + $r * self::BONO_AVANCE;
            elseif ($v === 2)   $val += self::VALOR_DAMA;
            elseif ($v === -1)  $val -= self::VALOR_PEON + (7 - $r) * self::BONO_AVANCE;
            elseif ($v === -2)  $val -= self::VALOR_DAMA;
        }
        return $val;
    }

    // ------------------------------------------------------------------
    //  Análisis de la posición: elige jugada + estadísticas + árbol MAX/MIN
    //  $metodo: 'minimax' | 'alfabeta'
    // ------------------------------------------------------------------
    public static function analizar(array $b, int $lado, int $profundidad, string $metodo): array
    {
        $usarPoda = $metodo === 'alfabeta';
        $cont = ['nodos' => 0, 'podas' => 0, 'podados' => 0];

        $raiz = self::buscar($b, $profundidad, $lado, -INF, INF, $usarPoda, 2, $cont);

        // Elegir la mejor jugada del nivel raíz
        $mejor = null; $mejorVal = $lado === 1 ? -INF : INF;
        foreach ($raiz['nodo']['hijos'] ?? [] as $h) {
            if ($h['podado']) continue;
            if (($lado === 1 && $h['valor'] > $mejorVal) || ($lado === -1 && $h['valor'] < $mejorVal)) {
                $mejorVal = $h['valor'];
                $mejor = $h['mov'];
            }
        }
        // Marcar la jugada elegida en el árbol
        if ($mejor) {
            foreach ($raiz['nodo']['hijos'] as &$h) {
                $h['elegido'] = (!$h['podado'] && $h['mov']['notacion'] === $mejor['notacion'] && $h['valor'] === $mejorVal);
            }
            unset($h);
        }

        $nombre = $metodo === 'alfabeta' ? 'Poda Alfa-Beta' : 'Minimax';

        return [
            'metodo'          => $metodo,
            'nombreMetodo'    => $nombre,
            'profundidad'     => $profundidad,
            'mejorMovimiento' => $mejor,
            'valor'           => $mejor ? $mejorVal : null,
            'nodosEvaluados'  => $cont['nodos'],
            'podas'           => $cont['podas'],
            'ramasPodadas'    => $cont['podados'],
            'arbol'           => $raiz['nodo'],
            'parametros'      => [
                'completitud' => 'Sí (hasta profundidad ' . $profundidad . ')',
                'optimalidad' => 'Sí (óptima al límite de profundidad)',
                'ctTeorica'   => $usarPoda ? 'O(b^(m/2)) mejor caso' : 'O(b^m)',
                'ceTeorica'   => 'O(b·m)',
            ],
        ];
    }

    /**
     * Búsqueda recursiva. Devuelve ['valor'=>int, 'nodo'=>árbol|null].
     * $vizDepth controla cuántos niveles de árbol se adjuntan para dibujar
     * (la búsqueda de valor/conteo continúa siempre hasta $prof).
     */
    private static function buscar(array $b, int $prof, int $lado, $alpha, $beta, bool $usarPoda, int $vizDepth, array &$cont): array
    {
        $cont['nodos']++;
        $movs = self::generarMovimientos($b, $lado);
        $terminal = $prof === 0 || empty($movs) || $cont['nodos'] > self::LIMITE_NODOS;

        if ($terminal) {
            $val = empty($movs) ? ($lado === 1 ? -self::GANA - $prof : self::GANA + $prof) : self::evaluar($b);
            $nodo = $vizDepth >= 0 ? ['tipo' => 'HOJA', 'valor' => $val, 'tablero' => $b] : null;
            return ['valor' => $val, 'nodo' => $nodo];
        }

        $tipo = $lado === 1 ? 'MAX' : 'MIN';
        $hijos = [];
        $adjuntar = $vizDepth >= 1;

        if ($lado === 1) { // MAX (IA)
            $val = -INF;
            $i = 0; $n = count($movs);
            for (; $i < $n; $i++) {
                $mv = $movs[$i];
                $hijoB = self::aplicar($b, $mv);
                $res = self::buscar($hijoB, $prof - 1, -1, $alpha, $beta, $usarPoda, $vizDepth - 1, $cont);
                if ($adjuntar) {
                    $hijos[] = ['mov' => $mv, 'valor' => $res['valor'], 'podado' => false, 'elegido' => false, 'tablero' => $hijoB, 'sub' => $res['nodo']];
                }
                $val = max($val, $res['valor']);
                if ($usarPoda) {
                    $alpha = max($alpha, $val);
                    if ($alpha >= $beta) { $cont['podas']++; break; }
                }
            }
            // Marcar como podados los movimientos restantes (para el dibujo)
            if ($adjuntar && $usarPoda && $i + 1 < $n) {
                for ($j = $i + 1; $j < $n; $j++) {
                    $cont['podados']++;
                    $hijos[] = ['mov' => $movs[$j], 'valor' => null, 'podado' => true, 'elegido' => false, 'tablero' => self::aplicar($b, $movs[$j]), 'sub' => null];
                }
            }
        } else { // MIN (humano)
            $val = INF;
            $i = 0; $n = count($movs);
            for (; $i < $n; $i++) {
                $mv = $movs[$i];
                $hijoB = self::aplicar($b, $mv);
                $res = self::buscar($hijoB, $prof - 1, 1, $alpha, $beta, $usarPoda, $vizDepth - 1, $cont);
                if ($adjuntar) {
                    $hijos[] = ['mov' => $mv, 'valor' => $res['valor'], 'podado' => false, 'elegido' => false, 'tablero' => $hijoB, 'sub' => $res['nodo']];
                }
                $val = min($val, $res['valor']);
                if ($usarPoda) {
                    $beta = min($beta, $val);
                    if ($alpha >= $beta) { $cont['podas']++; break; }
                }
            }
            if ($adjuntar && $usarPoda && $i + 1 < $n) {
                for ($j = $i + 1; $j < $n; $j++) {
                    $cont['podados']++;
                    $hijos[] = ['mov' => $movs[$j], 'valor' => null, 'podado' => true, 'elegido' => false, 'tablero' => self::aplicar($b, $movs[$j]), 'sub' => null];
                }
            }
        }

        $nodo = $vizDepth >= 0
            ? ['tipo' => $tipo, 'valor' => $val, 'tablero' => $b, 'hijos' => $hijos]
            : null;
        return ['valor' => $val, 'nodo' => $nodo];
    }

    // ------------------------------------------------------------------
    //  Estado de la partida tras una jugada
    // ------------------------------------------------------------------
    public static function estado(array $b, int $ladoSiguiente): array
    {
        $movs = self::generarMovimientos($b, $ladoSiguiente);
        $piezasIA = self::cuenta($b, 1);
        $piezasHumano = self::cuenta($b, -1);

        if ($piezasIA === 0)      return ['terminado' => true, 'ganador' => 'humano'];
        if ($piezasHumano === 0)  return ['terminado' => true, 'ganador' => 'ia'];
        if (empty($movs))         return ['terminado' => true, 'ganador' => $ladoSiguiente === 1 ? 'humano' : 'ia'];

        return ['terminado' => false, 'ganador' => null];
    }

    private static function cuenta(array $b, int $lado): int
    {
        $n = 0;
        foreach ($b as $v) if ($v !== 0 && self::signo($v) === $lado) $n++;
        return $n;
    }

    // ------------------------------------------------------------------
    //  Helpers
    // ------------------------------------------------------------------
    private static function signo(int $v): int { return $v > 0 ? 1 : -1; }

    private static function enTablero(int $r, int $c): bool { return $r >= 0 && $r < 8 && $c >= 0 && $c < 8; }

    /** Direcciones de movimiento simple según la pieza. */
    private static function dirsPieza(int $v): array
    {
        if (abs($v) === 2) return self::DIRS;              // dama: 4 diagonales
        return $v === 1 ? [[1, -1], [1, 1]] : [[-1, -1], [-1, 1]];
    }

    /** Direcciones de captura según la pieza (peones capturan hacia adelante). */
    private static function dirsCapturaPieza(int $v): array
    {
        if (abs($v) === 2) return self::DIRS;
        return $v === 1 ? [[1, -1], [1, 1]] : [[-1, -1], [-1, 1]];
    }

    private static function notacion(array $seq, bool $captura): string
    {
        $sep = $captura ? '×' : '-';
        return implode($sep, array_map(fn ($i) => self::coord($i), $seq));
    }

    private static function coord(int $i): string
    {
        $col = $i % 8;
        $fila = intdiv($i, 8);
        return chr(ord('a') + $col) . (8 - $fila);
    }

    /** Busca un movimiento legal que coincida con la secuencia dada (valida la entrada). */
    public static function buscarMovimientoLegal(array $b, int $lado, array $secuencia): ?array
    {
        foreach (self::generarMovimientos($b, $lado) as $m) {
            if ($m['secuencia'] === $secuencia) return $m;
        }
        return null;
    }
}
