import { Head, Link } from '@inertiajs/react';
import axios from 'axios';
import { useMemo, useState } from 'react';

// ---------- Tablero ----------
function Ficha({ v, size }) {
    if (v === 0) return null;
    const ia = v > 0;
    const dama = Math.abs(v) === 2;
    const d = Math.round(size * 0.74);
    return (
        <div
            className={`flex items-center justify-center rounded-full font-bold ${ia ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-700'} shadow`}
            style={{ width: d, height: d, fontSize: Math.max(8, Math.round(size * 0.4)) }}
        >
            {dama ? '♛' : ''}
        </div>
    );
}

function Tablero({ tablero, size = 56, seleccion = null, destinos = [], piezasActivas = [], onCasilla }) {
    const destSet = new Set(destinos.map((d) => d.destino));
    const activasSet = new Set(piezasActivas);
    return (
        <div className="inline-block rounded border-2 border-amber-900 bg-amber-900 p-0.5">
            <div className="grid" style={{ gridTemplateColumns: `repeat(8, ${size}px)` }}>
                {tablero.map((v, i) => {
                    const [r, c] = [Math.floor(i / 8), i % 8];
                    const oscura = (r + c) % 2 === 1;
                    const esDestino = destSet.has(i);
                    const esSel = seleccion === i;
                    const esActiva = activasSet.has(i);
                    let bg = oscura ? 'bg-amber-700' : 'bg-amber-200';
                    if (esActiva) bg = 'bg-yellow-500';
                    if (esDestino) bg = 'bg-green-400';
                    if (esSel) bg = 'bg-blue-500';
                    return (
                        <div
                            key={i}
                            onClick={onCasilla ? () => onCasilla(i) : undefined}
                            className={`relative flex items-center justify-center ${bg} ${onCasilla && (esActiva || esDestino) ? 'cursor-pointer hover:brightness-110' : ''}`}
                            style={{ width: size, height: size }}
                        >
                            <Ficha v={v} size={size} />
                            {esDestino && <span className="absolute h-3 w-3 rounded-full bg-green-700/70" />}
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

// ---------- Árbol MAX / MIN (estilo diapositiva) ----------
function Rama({ hijo, nivel }) {
    if (hijo.podado) {
        return (
            <div className="flex flex-col items-center opacity-50">
                <div className="mb-0.5 text-[10px] font-semibold text-gray-400">{hijo.mov.notacion}</div>
                <div className="grayscale">
                    <Tablero tablero={hijo.tablero} size={11} />
                </div>
                <div className="mt-0.5 text-[10px] font-bold text-red-500">✂ poda</div>
            </div>
        );
    }
    const nietos = hijo.sub?.hijos || [];
    return (
        <div className={`flex flex-col items-center rounded-lg p-1 ${hijo.elegido ? 'bg-green-100 ring-2 ring-green-500' : ''}`}>
            <div className="mb-0.5 text-[10px] font-semibold text-gray-600">{hijo.mov.notacion}</div>
            <Tablero tablero={hijo.tablero} size={11} />
            <div className="mt-0.5 text-[11px] font-bold text-blue-700">f(v)={hijo.valor}</div>
            {hijo.elegido && <div className="text-[9px] font-bold text-green-700">◀ elegida</div>}
            {nietos.length > 0 && (
                <>
                    <div className="my-1 h-3 w-px bg-gray-300" />
                    <div className="text-[9px] font-semibold uppercase text-rose-500">MIN</div>
                    <div className="flex flex-wrap items-start justify-center gap-1">
                        {nietos.map((n, idx) => (
                            <Rama key={idx} hijo={n} nivel={nivel + 1} />
                        ))}
                    </div>
                </>
            )}
        </div>
    );
}

function Arbol({ arbol }) {
    if (!arbol) return null;
    return (
        <div className="overflow-x-auto">
            <div className="flex min-w-max flex-col items-center gap-1 p-2">
                <div className="text-[10px] font-semibold uppercase text-emerald-600">MAX (la IA elige)</div>
                <Tablero tablero={arbol.tablero} size={13} />
                <div className="text-[11px] font-bold text-emerald-700">raíz (Ei)</div>
                <div className="my-1 h-3 w-px bg-gray-300" />
                <div className="flex flex-wrap items-start justify-center gap-2">
                    {(arbol.hijos || []).map((h, idx) => (
                        <Rama key={idx} hijo={h} nivel={1} />
                    ))}
                </div>
            </div>
        </div>
    );
}

// ---------- Panel de análisis de una metodología ----------
function PanelAnalisis({ a, color }) {
    if (!a) {
        return (
            <div className="flex h-full min-h-[90px] items-center justify-center rounded-xl border-2 border-dashed border-gray-300 p-4 text-center text-sm text-gray-400">
                Aún no analizas con esta metodología.
            </div>
        );
    }
    return (
        <div className={`space-y-3 rounded-xl bg-white p-4 shadow ring-1 ${color}`}>
            <h3 className="text-lg font-bold">{a.nombreMetodo}</h3>
            <div className="grid grid-cols-2 gap-2 text-sm">
                <Dato k="Jugada elegida" v={a.mejorMovimiento?.notacion ?? '—'} />
                <Dato k="Valor f(v)" v={a.valor ?? '—'} />
                <Dato k="Nodos evaluados (CT)" v={a.nodosEvaluados} />
                <Dato k="Profundidad" v={a.profundidad} />
                <Dato k="Podas realizadas" v={a.podas} />
                <Dato k="Ramas podadas" v={a.ramasPodadas} />
            </div>
            <div>
                <h4 className="mb-1 text-sm font-semibold">Árbol de búsqueda</h4>
                <Arbol arbol={a.arbol} />
            </div>
        </div>
    );
}

function Dato({ k, v }) {
    return (
        <div className="flex items-center justify-between rounded bg-gray-50 px-2 py-1">
            <span className="text-gray-500">{k}</span>
            <span className="font-bold">{v}</span>
        </div>
    );
}

export default function Damas({ tableroInicial, movimientosIniciales = [] }) {
    const [tablero, setTablero] = useState(tableroInicial);
    const [lado, setLado] = useState(-1); // -1 humano (blancas) empieza
    const [movimientos, setMovimientos] = useState(movimientosIniciales);
    const [seleccion, setSeleccion] = useState(null);
    const [profundidad, setProfundidad] = useState(2);
    const [slots, setSlots] = useState({ minimax: null, alfabeta: null });
    const [cargando, setCargando] = useState('');
    const [fin, setFin] = useState(null);
    const [error, setError] = useState('');

    const esTurnoHumano = lado === -1 && !fin;
    const esTurnoIA = lado === 1 && !fin;

    // Piezas del humano que tienen movimiento, y destinos de la pieza seleccionada
    const piezasActivas = useMemo(
        () => (esTurnoHumano ? [...new Set(movimientos.map((m) => m.secuencia[0]))] : []),
        [movimientos, esTurnoHumano],
    );
    const destinos = useMemo(() => {
        if (seleccion == null) return [];
        return movimientos
            .filter((m) => m.secuencia[0] === seleccion)
            .map((m) => ({ destino: m.secuencia[m.secuencia.length - 1], mov: m }));
    }, [seleccion, movimientos]);

    function reset() {
        setTablero(tableroInicial);
        setLado(-1);
        setMovimientos(movimientosIniciales);
        setSeleccion(null);
        setSlots({ minimax: null, alfabeta: null });
        setFin(null);
        setError('');
    }

    async function aplicar(mov, ladoMov) {
        setError('');
        try {
            const { data } = await axios.post(route('damas.aplicar'), {
                tablero,
                lado: ladoMov,
                secuencia: mov.secuencia,
            });
            setTablero(data.tablero);
            setLado(data.ladoSiguiente);
            setMovimientos(data.movimientos);
            setSeleccion(null);
            setSlots({ minimax: null, alfabeta: null });
            if (data.terminado) setFin(data.ganador);
        } catch (e) {
            setError(e.response?.data?.error || 'Error al aplicar la jugada.');
        }
    }

    function clicCasilla(i) {
        if (!esTurnoHumano) return;
        const dest = destinos.find((d) => d.destino === i);
        if (dest) { aplicar(dest.mov, -1); return; }
        if (piezasActivas.includes(i)) setSeleccion(i === seleccion ? null : i);
    }

    async function analizar(metodo) {
        setError('');
        setCargando(metodo);
        try {
            const { data } = await axios.post(route('damas.analizar'), {
                tablero, lado: 1, profundidad, metodo,
            });
            setSlots((s) => ({ ...s, [metodo]: data }));
        } catch (e) {
            setError(e.response?.data?.error || 'Error al analizar.');
        } finally {
            setCargando('');
        }
    }

    function jugarIA() {
        const a = slots.alfabeta || slots.minimax;
        if (a?.mejorMovimiento) aplicar(a.mejorMovimiento, 1);
    }

    const ambos = slots.minimax && slots.alfabeta;
    const mismoResultado = ambos && slots.minimax.mejorMovimiento?.notacion === slots.alfabeta.mejorMovimiento?.notacion;

    return (
        <>
            <Head title="Juego de Damas · IA" />
            <div className="min-h-screen bg-gray-100 text-gray-800">
                <header className="bg-indigo-600 px-8 py-6 text-white">
                    <Link href="/" className="text-sm text-indigo-100 hover:underline">← Inicio</Link>
                    <h1 className="mt-1 text-2xl font-bold">Juego de Damas · Minimax vs Alfa-Beta</h1>
                    <p className="opacity-90">
                        Tú juegas abajo (<b>blancas</b>); la IA arriba (<b>rojas</b>). En el turno de la IA,
                        analiza con cada metodología y compáralas.
                    </p>
                </header>

                <main className="mx-auto max-w-6xl space-y-6 p-6">
                    <div className="grid gap-6 lg:grid-cols-[auto_1fr]">
                        {/* Tablero */}
                        <section className="rounded-xl bg-white p-6 shadow">
                            <Tablero
                                tablero={tablero}
                                seleccion={seleccion}
                                destinos={destinos}
                                piezasActivas={piezasActivas}
                                onCasilla={clicCasilla}
                            />
                            <div className="mt-4 flex items-center gap-4 text-sm">
                                <span className="flex items-center gap-1"><span className="h-3 w-3 rounded-full bg-gray-100 ring-1 ring-gray-400" /> Tú</span>
                                <span className="flex items-center gap-1"><span className="h-3 w-3 rounded-full bg-red-600" /> IA</span>
                                <span className="text-gray-500">♛ = dama</span>
                            </div>
                            <button onClick={reset} className="mt-4 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm hover:bg-gray-50">
                                ↺ Reiniciar partida
                            </button>
                        </section>

                        {/* Panel de control */}
                        <section className="space-y-4">
                            {fin && (
                                <div className={`rounded-lg px-4 py-3 font-semibold ${fin === 'ia' ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700'}`}>
                                    {fin === 'ia' ? '🤖 Ganó la IA.' : '🎉 ¡Ganaste!'}
                                </div>
                            )}
                            {error && <div className="rounded-lg bg-red-50 px-4 py-3 text-red-600">⚠ {error}</div>}

                            {esTurnoHumano && (
                                <div className="rounded-xl bg-white p-5 shadow">
                                    <p className="font-semibold text-gray-700">Tu turno</p>
                                    <p className="text-sm text-gray-500">
                                        Haz clic en una ficha resaltada y luego en una casilla verde. (Captura obligatoria si existe.)
                                    </p>
                                </div>
                            )}

                            {esTurnoIA && (
                                <div className="rounded-xl bg-white p-5 shadow">
                                    <p className="mb-3 font-semibold text-gray-700">Turno de la IA — analiza y compara</p>
                                    <div className="mb-3 flex items-center gap-2 text-sm">
                                        <label className="font-semibold">Profundidad:</label>
                                        <select
                                            value={profundidad}
                                            onChange={(e) => { setProfundidad(Number(e.target.value)); setSlots({ minimax: null, alfabeta: null }); }}
                                            className="rounded border-gray-300"
                                        >
                                            {[1, 2, 3, 4].map((p) => <option key={p} value={p}>{p}</option>)}
                                        </select>
                                        <span className="text-xs text-gray-400">(2 recomendada para ver el árbol completo)</span>
                                    </div>
                                    <div className="flex flex-wrap gap-3">
                                        <button onClick={() => analizar('minimax')} disabled={cargando !== ''}
                                            className="rounded-lg bg-slate-600 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 disabled:opacity-50">
                                            {cargando === 'minimax' ? 'Analizando…' : '1) Analizar con Minimax'}
                                        </button>
                                        <button onClick={() => analizar('alfabeta')} disabled={cargando !== ''}
                                            className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
                                            {cargando === 'alfabeta' ? 'Analizando…' : '2) Analizar con Alfa-Beta'}
                                        </button>
                                        <button onClick={jugarIA} disabled={!slots.minimax && !slots.alfabeta}
                                            className="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-50">
                                            ▶ Ejecutar jugada de la IA
                                        </button>
                                    </div>
                                </div>
                            )}

                            {/* Comparación */}
                            {ambos && (
                                <div className="rounded-xl bg-white p-5 shadow">
                                    <h2 className="mb-3 text-lg font-bold">Evaluación comparativa</h2>
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b text-left text-gray-500">
                                                <th className="py-2">Parámetro</th><th>Minimax</th><th>Alfa-Beta</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y">
                                            <FilaCmp k="Jugada elegida" a={slots.minimax.mejorMovimiento?.notacion} b={slots.alfabeta.mejorMovimiento?.notacion} iguales={mismoResultado} />
                                            <FilaCmp k="Valor f(v)" a={slots.minimax.valor} b={slots.alfabeta.valor} iguales={slots.minimax.valor === slots.alfabeta.valor} />
                                            <FilaCmp k="Nodos evaluados (CT)" a={slots.minimax.nodosEvaluados} b={slots.alfabeta.nodosEvaluados} menorMejor />
                                            <FilaCmp k="Podas" a={slots.minimax.podas} b={slots.alfabeta.podas} />
                                        </tbody>
                                    </table>
                                    <p className="mt-3 text-xs text-gray-500">
                                        {mismoResultado
                                            ? '✔ Ambas eligen la MISMA jugada, pero Alfa-Beta evalúa menos nodos gracias a la poda.'
                                            : 'Revisa la profundidad: a igual profundidad ambas deberían coincidir en la jugada.'}
                                    </p>
                                </div>
                            )}
                        </section>
                    </div>

                    {/* Árboles lado a lado */}
                    {(slots.minimax || slots.alfabeta) && (
                        <div className="grid gap-6 xl:grid-cols-2">
                            <PanelAnalisis a={slots.minimax} color="ring-slate-300" />
                            <PanelAnalisis a={slots.alfabeta} color="ring-indigo-300" />
                        </div>
                    )}
                </main>
            </div>
        </>
    );
}

function FilaCmp({ k, a, b, iguales = false, menorMejor = false }) {
    let ca = '', cb = '';
    if (menorMejor && a != null && b != null && a !== b) {
        const aMenor = Number(a) < Number(b);
        ca = aMenor ? 'font-bold text-green-700' : '';
        cb = aMenor ? '' : 'font-bold text-green-700';
    }
    if (iguales) { ca = 'font-bold text-green-700'; cb = 'font-bold text-green-700'; }
    return (
        <tr>
            <td className="py-2 font-semibold text-gray-700">{k}</td>
            <td className={`py-2 ${ca}`}>{a}</td>
            <td className={`py-2 ${cb}`}>{b}</td>
        </tr>
    );
}
