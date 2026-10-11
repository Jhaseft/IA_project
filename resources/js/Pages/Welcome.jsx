import { Head, Link } from '@inertiajs/react';

export default function Welcome({ metodologias = [] }) {
    return (
        <>
            <Head title="Metodologías de Búsqueda · IA" />
            <div className="min-h-screen bg-gray-100 text-gray-800">
                <header className="bg-blue-600 px-8 py-10 text-white">
                    <div className="mx-auto max-w-5xl">
                        <h1 className="text-3xl font-bold">Metodologías de Búsqueda en IA</h1>
                        <p className="mt-2 max-w-2xl opacity-90">
                            Elige una metodología para resolver el <b>8-puzzle</b>. Cada una muestra
                            sus 4 parámetros (completitud, optimalidad, complejidad temporal y
                            espacial). Luego ingresas el estado inicial y la meta, y listo.
                        </p>
                    </div>
                </header>

                <main className="mx-auto max-w-5xl p-6">
                    {/* Recomendación */}
                    <div className="mb-6 rounded-lg border-l-4 border-blue-500 bg-blue-50 px-4 py-3 text-blue-900">
                        <b>¿Cuál usar?</b> Si necesitas la solución <b>óptima</b> explorando lo menos
                        posible, usa <b>A*</b> (búsqueda informada). BFS también es óptima pero gasta
                        más memoria; DFS es rápida en memoria pero <b>no</b> garantiza el óptimo.
                    </div>

                    {/* Tarjetas de metodologías */}
                    <div className="grid gap-5 md:grid-cols-3">
                        {metodologias.map((m) => (
                            <div
                                key={m.id}
                                className={`flex flex-col rounded-xl bg-white p-5 shadow ${
                                    m.recomendado ? 'ring-2 ring-blue-500' : ''
                                }`}
                            >
                                <div className="mb-1 flex items-center justify-between">
                                    <h2 className="text-lg font-bold">{m.nombre}</h2>
                                    {m.recomendado && (
                                        <span className="rounded-full bg-blue-600 px-2 py-0.5 text-xs font-semibold text-white">
                                            Recomendada
                                        </span>
                                    )}
                                </div>
                                <p className="mb-3 text-sm text-gray-600">{m.idea}</p>

                                <dl className="mb-3 space-y-1 text-sm">
                                    <Fila k="C — Completitud" v={m.completitud} />
                                    <Fila k="O — Optimalidad" v={m.optimalidad} />
                                    <Fila k="CT" v={m.ctTeorica} />
                                    <Fila k="CE" v={m.ceTeorica} />
                                </dl>

                                <p className="mb-4 flex-1 text-xs text-gray-500">{m.cuando}</p>

                                <Link
                                    href={`/buscador?metodo=${m.id}`}
                                    className="rounded-lg bg-blue-600 px-4 py-2 text-center font-semibold text-white hover:bg-blue-700"
                                >
                                    Usar esta metodología →
                                </Link>
                            </div>
                        ))}
                    </div>

                    <div className="mt-6 text-center">
                        <Link href="/buscador" className="text-blue-600 hover:underline">
                            O ir directo al buscador y elegir ahí →
                        </Link>
                    </div>
                </main>
            </div>
        </>
    );
}

function Fila({ k, v }) {
    return (
        <div className="flex items-start justify-between gap-2">
            <dt className="font-semibold text-blue-800">{k}</dt>
            <dd className="text-right">{v}</dd>
        </div>
    );
}
