import { Head, Link } from '@inertiajs/react';
import axios from 'axios';
import { useState } from 'react';

const META_DEFECTO = [1, 2, 3, 4, 5, 6, 7, 8, 0];

// Lee ?metodo=xxx de la URL para preseleccionar la metodología elegida en el Welcome
function metodoDesdeUrl(algoritmos) {
    const q = new URLSearchParams(window.location.search).get('metodo');
    return algoritmos.some((a) => a.id === q) ? q : null;
}

// Baraja aplicando movimientos válidos desde la meta → siempre resoluble
function aleatorioResoluble(pasos = 40) {
    let e = [...META_DEFECTO];
    for (let k = 0; k < pasos; k++) {
        const i = e.indexOf(0);
        const f = Math.floor(i / 3), c = i % 3;
        const v = [];
        if (f > 0) v.push(i - 3);
        if (f < 2) v.push(i + 3);
        if (c > 0) v.push(i - 1);
        if (c < 2) v.push(i + 1);
        const j = v[Math.floor(Math.random() * v.length)];
        [e[i], e[j]] = [e[j], e[i]];
    }
    return e;
}

// Rejilla de 3x3. editable=true permite escribir en las celdas.
function Grid({ estado, editable = false, onChange, small = false }) {
    const celda = small ? 'h-8 w-8 text-sm' : 'h-14 w-14 text-xl';
    return (
        <div className="inline-grid grid-cols-3 gap-1">
            {estado.map((v, i) => {
                const hueco = v === 0;
                const base = `flex items-center justify-center rounded font-semibold ${celda} ${
                    hueco ? 'bg-blue-100 text-blue-600' : 'bg-blue-600 text-white'
                }`;
                if (editable) {
                    return (
                        <input
                            key={i}
                            value={v}
                            onChange={(e) => {
                                const n = parseInt(e.target.value.replace(/[^0-8]/g, ''), 10);
                                onChange(i, isNaN(n) ? '' : n);
                            }}
                            className={`${base} border-0 text-center outline-none focus:ring-2 focus:ring-blue-400`}
                            maxLength={1}
                        />
                    );
                }
                return (
                    <div key={i} className={base}>
                        {hueco ? '' : v}
                    </div>
                );
            })}
        </div>
    );
}

export default function Buscador({ algoritmos = [], metaDefecto = META_DEFECTO }) {
    const [inicial, setInicial] = useState([1, 2, 3, 4, 5, 0, 7, 8, 6]);
    const [meta, setMeta] = useState(metaDefecto);
    const [metodo, setMetodo] = useState(
        metodoDesdeUrl(algoritmos) || algoritmos[0]?.id || 'bfs'
    );
    const [res, setRes] = useState(null);
    const [error, setError] = useState('');
    const [cargando, setCargando] = useState(false);

    const editar = (setter) => (i, valor) =>
        setter((prev) => prev.map((x, idx) => (idx === i ? valor : x)));

    async function resolver() {
        setError('');
        setCargando(true);
        try {
            const inicialN = inicial.map((x) => (x === '' ? -1 : Number(x)));
            const metaN = meta.map((x) => (x === '' ? -1 : Number(x)));
            const { data } = await axios.post(route('buscador.resolver'), {
                inicial: inicialN,
                meta: metaN,
                metodo,
            });
            if (data.resoluble === false) {
                setError(data.mensaje);
                setRes(null);
            } else {
                setRes(data);
            }
        } catch (e) {
            setError(e.response?.data?.error || 'Error al resolver. Revisa que los tableros sean válidos.');
            setRes(null);
        } finally {
            setCargando(false);
        }
    }

    const nivelesMax = res ? Math.max(...Object.values(res.porNivel)) : 1;

    return (
        <>
            <Head title="Buscador IA · 8-puzzle" />
            <div className="min-h-screen bg-gray-100 text-gray-800">
                <header className="bg-blue-600 px-8 py-6 text-white">
                    <Link href="/" className="text-sm text-blue-100 hover:underline">
                        ← Metodologías
                    </Link>
                    <h1 className="mt-1 text-2xl font-bold">Búsqueda en IA · 8-puzzle</h1>
                    <p className="opacity-90">
                        Practica las metodologías de búsqueda y observa los 4 parámetros:
                        completitud, optimalidad, complejidad temporal y espacial.
                    </p>
                </header>

                <main className="mx-auto max-w-5xl space-y-6 p-6">
                    {/* Configuración */}
                    <section className="rounded-xl bg-white p-6 shadow">
                        <div className="flex flex-wrap gap-10">
                            <div>
                                <h3 className="mb-2 font-semibold">Estado inicial (Ei)</h3>
                                <Grid estado={inicial} editable onChange={editar(setInicial)} />
                            </div>
                            <div>
                                <h3 className="mb-2 font-semibold">Estado meta (Em)</h3>
                                <Grid estado={meta} editable onChange={editar(setMeta)} />
                            </div>
                        </div>

                        <div className="mt-6 flex flex-wrap items-center gap-3">
                            <label className="font-semibold">
                                Metodología:{' '}
                                <select
                                    value={metodo}
                                    onChange={(e) => setMetodo(e.target.value)}
                                    className="rounded-lg border-gray-300"
                                >
                                    {algoritmos.map((a) => (
                                        <option key={a.id} value={a.id}>
                                            {a.nombre}
                                        </option>
                                    ))}
                                </select>
                            </label>
                            <button
                                onClick={resolver}
                                disabled={cargando}
                                className="rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                            >
                                {cargando ? 'Resolviendo…' : 'Resolver ▶'}
                            </button>
                            <button
                                onClick={() => setInicial(aleatorioResoluble())}
                                className="rounded-lg border border-gray-300 bg-white px-4 py-2 hover:bg-blue-50"
                            >
                                🎲 Inicial aleatorio
                            </button>
                            <button
                                onClick={() => setMeta(META_DEFECTO)}
                                className="rounded-lg border border-gray-300 bg-white px-4 py-2 hover:bg-blue-50"
                            >
                                ↺ Reiniciar meta
                            </button>
                        </div>
                        <p className="mt-3 text-sm text-gray-500">
                            Escribe números del 0 al 8 (el <b>0</b> es el hueco). Cada celda debe ser única.
                        </p>
                    </section>

                    {error && (
                        <div className="rounded-lg bg-red-50 px-4 py-3 text-red-600">⚠ {error}</div>
                    )}

                    {/* Resultados */}
                    {res && (
                        <section className="space-y-5 rounded-xl bg-white p-6 shadow">
                            <h2 className="text-xl font-bold">Resultado · {res.nombreMetodo}</h2>
                            <div
                                className={`rounded-lg px-4 py-3 ${
                                    res.encontrada ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-600'
                                }`}
                            >
                                {res.encontrada
                                    ? `✔ Solución encontrada en ${res.pasos} movimientos.`
                                    : '✖ No se encontró solución dentro de los límites.'}
                            </div>

                            {/* 4 parámetros */}
                            <div className="grid gap-3 sm:grid-cols-2">
                                <Param k="C — Completitud" v={res.parametros.completitud} />
                                <Param k="O — Optimalidad" v={res.parametros.optimalidad} />
                                <Param
                                    k="CT — Complej. Temporal"
                                    v={`${res.parametros.complejidadTemporal} u/t  (${res.parametros.ctTeorica})`}
                                />
                                <Param
                                    k="CE — Complej. Espacial"
                                    v={`${res.parametros.complejidadEspacial} u/e  (${res.parametros.ceTeorica})`}
                                />
                            </div>

                            <div className="flex flex-wrap gap-6 text-sm">
                                <span>Pasos de la solución: <b>{res.pasos}</b></span>
                                <span>Nodos expandidos: <b>{res.nodosExpandidos}</b></span>
                                <span>Nodos por nivel (Σ): <b>{res.sumaPorNivel}</b></span>
                            </div>

                            {/* Niveles tipo 2,6,16,64 */}
                            <div>
                                <h3 className="mb-2 font-semibold">Árbol a lo ancho — nodos por nivel</h3>
                                <div className="flex flex-wrap items-end gap-4">
                                    {Object.entries(res.porNivel).map(([nivel, n]) => (
                                        <div key={nivel} className="text-center">
                                            <div
                                                className="flex w-12 items-start justify-center rounded-t bg-blue-600 pt-1 text-xs font-bold text-white"
                                                style={{ height: `${20 + Math.round((n / nivelesMax) * 120)}px` }}
                                            >
                                                {n}
                                            </div>
                                            <div className="mt-1 text-xs text-gray-500">Nivel {nivel}</div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Camino solución */}
                            <div>
                                <h3 className="mb-2 font-semibold">Camino solución</h3>
                                <div className="flex flex-wrap items-center gap-3">
                                    {res.camino.map((paso, idx) => (
                                        <div key={idx} className="flex items-center gap-3">
                                            <div className="text-center">
                                                <div className="mb-1 h-4 text-xs font-semibold text-blue-700">
                                                    {paso.movimiento || 'Inicio'}
                                                </div>
                                                <Grid estado={paso.estado} small />
                                                {paso.f !== undefined && (
                                                    <div className="mt-1 text-xs text-gray-500">
                                                        f={paso.h}+{paso.g}={paso.f}
                                                    </div>
                                                )}
                                            </div>
                                            {idx < res.camino.length - 1 && (
                                                <span className="text-2xl text-blue-500">→</span>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </section>
                    )}
                </main>
            </div>
        </>
    );
}

function Param({ k, v }) {
    return (
        <div className="flex items-center justify-between rounded-lg bg-blue-50 px-4 py-3">
            <span className="font-semibold text-blue-800">{k}</span>
            <span className="font-bold">{v}</span>
        </div>
    );
}
