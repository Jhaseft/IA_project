import { Head, Link } from '@inertiajs/react';
import axios from 'axios';
import { useState } from 'react';

const INICIAL_DEFECTO = [3, 3, 1];
const META_DEFECTO = [0, 0, 0];

function metodoDesdeUrl(algoritmos) {
    const q = new URLSearchParams(window.location.search).get('metodo');
    return algoritmos.some((a) => a.id === q) ? q : null;
}

// Dibuja una "fila" de personas (misioneros + caníbales) de una orilla
function Grupo({ m, c, chico = false }) {
    const tam = chico ? 'text-lg' : 'text-2xl';
    return (
        <div className={`flex flex-wrap gap-0.5 ${tam}`}>
            {Array.from({ length: m }).map((_, i) => (
                <span key={`m${i}`} title="Misionero">🧍</span>
            ))}
            {Array.from({ length: c }).map((_, i) => (
                <span key={`c${i}`} title="Caníbal">👹</span>
            ))}
            {m === 0 && c === 0 && <span className="text-xs text-gray-300">vacío</span>}
        </div>
    );
}

// Escena del río para un estado [m, c, b]
function Escena({ estado, chico = false }) {
    const [m, c, b] = estado;
    const mr = 3 - m, cr = 3 - c;
    const alto = chico ? 'h-16' : 'h-24';
    const barco = <span className={chico ? 'text-xl' : 'text-3xl'}>🛶</span>;
    return (
        <div className={`flex items-stretch gap-1 rounded-lg border border-blue-200 bg-blue-50 p-2 ${chico ? 'text-xs' : ''}`}>
            {/* Orilla izquierda */}
            <div className={`flex ${alto} min-w-[70px] flex-col justify-between rounded bg-green-100 p-1`}>
                <Grupo m={m} c={c} chico={chico} />
                <div className="text-center text-[10px] font-semibold text-green-700">Izquierda</div>
            </div>
            {/* Río */}
            <div className={`relative flex ${alto} flex-1 items-center justify-center rounded bg-blue-200`}>
                <div className={`absolute ${b === 1 ? 'left-1' : 'right-1'} top-1`}>{barco}</div>
                <span className="text-[10px] text-blue-700">río 🌊</span>
            </div>
            {/* Orilla derecha */}
            <div className={`flex ${alto} min-w-[70px] flex-col justify-between rounded bg-green-100 p-1`}>
                <Grupo m={mr} c={cr} chico={chico} />
                <div className="text-center text-[10px] font-semibold text-green-700">Derecha</div>
            </div>
        </div>
    );
}

function Param({ k, v }) {
    return (
        <div className="flex items-center justify-between rounded-lg bg-blue-50 px-3 py-2 text-sm">
            <span className="font-semibold text-blue-800">{k}</span>
            <span className="font-bold text-right">{v}</span>
        </div>
    );
}

// Panel de resultado de UNA metodología (se usa dos veces para comparar)
function PanelResultado({ res }) {
    if (!res) {
        return (
            <div className="flex h-full min-h-[120px] items-center justify-center rounded-xl border-2 border-dashed border-gray-300 p-6 text-center text-sm text-gray-400">
                Aún no ejecutas esta metodología.
            </div>
        );
    }
    const nivelesMax = Math.max(...Object.values(res.porNivel), 1);
    return (
        <div className="space-y-4 rounded-xl bg-white p-5 shadow">
            <h3 className="text-lg font-bold">{res.nombreMetodo}</h3>
            <div className={`rounded-lg px-3 py-2 text-sm ${res.encontrada ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-600'}`}>
                {res.encontrada ? `✔ Solución en ${res.pasos} viajes.` : '✖ Sin solución dentro del límite.'}
            </div>

            <div className="grid gap-2">
                <Param k="C — Completitud" v={res.parametros.completitud} />
                <Param k="O — Optimalidad" v={res.parametros.optimalidad} />
                <Param k="CT — Complej. Temporal" v={`${res.parametros.complejidadTemporal} u/t (${res.parametros.ctTeorica})`} />
                <Param k="CE — Complej. Espacial" v={`${res.parametros.complejidadEspacial} u/e (${res.parametros.ceTeorica})`} />
            </div>

            <div className="flex flex-wrap gap-4 text-xs text-gray-600">
                <span>Viajes: <b>{res.pasos}</b></span>
                <span>Nodos expandidos: <b>{res.nodosExpandidos}</b></span>
                <span>Nodos generados (Σ): <b>{res.sumaPorNivel}</b></span>
            </div>

            {/* Árbol a lo ancho */}
            <div>
                <h4 className="mb-1 text-sm font-semibold">Nodos por nivel del árbol</h4>
                <div className="flex flex-wrap items-end gap-2">
                    {Object.entries(res.porNivel).map(([nivel, n]) => (
                        <div key={nivel} className="text-center">
                            <div
                                className="flex w-8 items-start justify-center rounded-t bg-blue-600 pt-0.5 text-[10px] font-bold text-white"
                                style={{ height: `${16 + Math.round((n / nivelesMax) * 80)}px` }}
                            >
                                {n}
                            </div>
                            <div className="mt-0.5 text-[10px] text-gray-500">N{nivel}</div>
                        </div>
                    ))}
                </div>
            </div>

            {/* Camino solución */}
            {res.encontrada && (
                <div>
                    <h4 className="mb-2 text-sm font-semibold">Ruta solución</h4>
                    <div className="space-y-2">
                        {res.camino.map((paso, idx) => (
                            <div key={idx}>
                                <div className="mb-0.5 text-xs font-semibold text-blue-700">
                                    {idx === 0 ? 'Inicio (Ei)' : `Viaje ${idx}: ${paso.movimiento}`}
                                </div>
                                <Escena estado={paso.estado} chico />
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}

export default function Misioneros({ algoritmos = [], inicialDefecto = INICIAL_DEFECTO, metaDefecto = META_DEFECTO }) {
    const [inicial, setInicial] = useState(inicialDefecto);
    const [meta] = useState(metaDefecto);
    const [slots, setSlots] = useState({ bfs: null, dfs: null });
    const [error, setError] = useState('');
    const [cargando, setCargando] = useState('');

    async function ejecutar(metodo) {
        setError('');
        setCargando(metodo);
        try {
            const { data } = await axios.post(route('misioneros.resolver'), { inicial, meta, metodo });
            if (data.error) {
                setError(data.error);
            } else {
                setSlots((s) => ({ ...s, [metodo]: data }));
            }
        } catch (e) {
            setError(e.response?.data?.error || 'Error al resolver. Revisa el estado inicial.');
        } finally {
            setCargando('');
        }
    }

    const ambos = slots.bfs && slots.dfs;

    return (
        <>
            <Head title="Misioneros y Caníbales · IA" />
            <div className="min-h-screen bg-gray-100 text-gray-800">
                <header className="bg-emerald-600 px-8 py-6 text-white">
                    <Link href="/" className="text-sm text-emerald-100 hover:underline">← Inicio</Link>
                    <h1 className="mt-1 text-2xl font-bold">Misioneros y Caníbales (Problema 3.9)</h1>
                    <p className="opacity-90">
                        Cruza 3 misioneros y 3 caníbales sin que los caníbales superen a los misioneros en ninguna orilla.
                        Ejecuta <b>BFS</b> y <b>DFS</b> y compáralas con los 4 parámetros de búsqueda.
                    </p>
                </header>

                <main className="mx-auto max-w-6xl space-y-6 p-6">
                    {/* Formulación + controles */}
                    <section className="rounded-xl bg-white p-6 shadow">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <h3 className="mb-2 font-semibold">Estado inicial (Ei) = [{inicial.join(', ')}]</h3>
                                <Escena estado={inicial} />
                                <p className="mt-2 text-xs text-gray-500">
                                    Estado = [misioneros izq, caníbales izq, barco (1=izq / 0=der)]
                                </p>
                            </div>
                            <div>
                                <h3 className="mb-2 font-semibold">Estado meta (Em) = [{meta.join(', ')}]</h3>
                                <Escena estado={meta} />
                                <p className="mt-2 text-xs text-gray-500">Todos cruzados a la derecha.</p>
                            </div>
                        </div>

                        <div className="mt-6 flex flex-wrap items-center gap-3">
                            <button
                                onClick={() => ejecutar('bfs')}
                                disabled={cargando !== ''}
                                className="rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                            >
                                {cargando === 'bfs' ? 'Ejecutando…' : '1) Ejecutar BFS (Anchura) ▶'}
                            </button>
                            <button
                                onClick={() => ejecutar('dfs')}
                                disabled={cargando !== ''}
                                className="rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"
                            >
                                {cargando === 'dfs' ? 'Ejecutando…' : '2) Ejecutar DFS (Profundidad) ▶'}
                            </button>
                            <button
                                onClick={() => { setInicial(inicialDefecto); setSlots({ bfs: null, dfs: null }); setError(''); }}
                                className="rounded-lg border border-gray-300 bg-white px-4 py-2 hover:bg-gray-50"
                            >
                                ↺ Reiniciar
                            </button>
                        </div>
                        <p className="mt-3 text-sm text-gray-500">
                            Cada metodología queda <b>guardada</b> en su columna; ejecuta ambas para ver la comparación.
                        </p>
                    </section>

                    {error && <div className="rounded-lg bg-red-50 px-4 py-3 text-red-600">⚠ {error}</div>}

                    {/* Tabla comparativa */}
                    {ambos && (
                        <section className="overflow-x-auto rounded-xl bg-white p-6 shadow">
                            <h2 className="mb-3 text-xl font-bold">Evaluación comparativa</h2>
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-gray-500">
                                        <th className="py-2">Parámetro</th>
                                        <th className="py-2">BFS (Anchura)</th>
                                        <th className="py-2">DFS (Profundidad)</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    <Fila k="Completitud" a={slots.bfs.parametros.completitud} b={slots.dfs.parametros.completitud} />
                                    <Fila k="Optimalidad" a={slots.bfs.parametros.optimalidad} b={slots.dfs.parametros.optimalidad} />
                                    <Fila k="Viajes de la solución" a={slots.bfs.pasos} b={slots.dfs.pasos} resaltarMenor />
                                    <Fila k="CT — Nodos generados" a={slots.bfs.parametros.complejidadTemporal} b={slots.dfs.parametros.complejidadTemporal} resaltarMenor />
                                    <Fila k="CE — Máx. en memoria" a={slots.bfs.parametros.complejidadEspacial} b={slots.dfs.parametros.complejidadEspacial} resaltarMenor />
                                    <Fila k="Nodos expandidos" a={slots.bfs.nodosExpandidos} b={slots.dfs.nodosExpandidos} resaltarMenor />
                                </tbody>
                            </table>
                            <p className="mt-3 text-xs text-gray-500">
                                En verde el valor más favorable. BFS suele ganar en <b>viajes</b> (óptima); DFS suele ganar en <b>memoria</b>.
                            </p>
                        </section>
                    )}

                    {/* Resultados lado a lado */}
                    <div className="grid gap-6 lg:grid-cols-2">
                        <PanelResultado res={slots.bfs} />
                        <PanelResultado res={slots.dfs} />
                    </div>
                </main>
            </div>
        </>
    );
}

function Fila({ k, a, b, resaltarMenor = false }) {
    let claseA = '', claseB = '';
    if (resaltarMenor && a != null && b != null && a !== b) {
        const aMenor = Number(a) < Number(b);
        claseA = aMenor ? 'font-bold text-green-700' : '';
        claseB = aMenor ? '' : 'font-bold text-green-700';
    }
    return (
        <tr>
            <td className="py-2 font-semibold text-gray-700">{k}</td>
            <td className={`py-2 ${claseA}`}>{a}</td>
            <td className={`py-2 ${claseB}`}>{b}</td>
        </tr>
    );
}
