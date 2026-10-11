import { Head, Link } from '@inertiajs/react';

export default function Welcome({ misioneros = [], damas = [] }) {
    return (
        <>
            <Head title="Práctica IA · Técnicas de Búsqueda" />
            <div className="min-h-screen bg-gray-100 text-gray-800">
                <header className="bg-gray-900 px-8 py-10 text-white">
                    <div className="mx-auto max-w-6xl">
                        <p className="text-sm uppercase tracking-wide text-gray-400">Primer Parcial · Inteligencia Artificial</p>
                        <h1 className="mt-1 text-3xl font-bold">Técnicas de Búsqueda para la Resolución de Problemas</h1>
                        <p className="mt-2 max-w-3xl opacity-90">
                            Dos problemas, cada uno con <b>dos metodologías</b> que puedes ejecutar y
                            <b> comparar</b> mediante los 4 parámetros de evaluación (completitud, optimalidad,
                            complejidad temporal y espacial).
                        </p>
                    </div>
                </header>

                <main className="mx-auto max-w-6xl space-y-8 p-6">
                    {/* Problema 1 */}
                    <Problema
                        etiqueta="Grupo 1 (c)"
                        titulo="Misioneros y Caníbales"
                        descripcion="Problema de espacio de estados (Cap. 3). Cruza a 3 misioneros y 3 caníbales sin que los caníbales superen a los misioneros en ninguna orilla."
                        metodologias={misioneros}
                        href="/misioneros"
                        color="emerald"
                    />

                    {/* Problema 2 */}
                    <Problema
                        etiqueta="Grupo 2 (a)"
                        titulo="Juego de Damas"
                        descripcion="Problema con adversario (Cap. 5). Juegas contra la IA; ella decide su jugada con búsqueda en el árbol de juego."
                        metodologias={damas}
                        href="/damas"
                        color="indigo"
                    />

                    {/* Demo extra */}
                    <div className="rounded-xl border border-dashed border-gray-300 bg-white p-5">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h3 className="font-bold">Demo extra · 8-puzzle</h3>
                                <p className="text-sm text-gray-600">Ejemplo adicional con BFS, DFS y A* (búsqueda informada).</p>
                            </div>
                            <Link href="/buscador" className="rounded-lg bg-gray-700 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                                Abrir demo →
                            </Link>
                        </div>
                    </div>
                </main>
            </div>
        </>
    );
}

function Problema({ etiqueta, titulo, descripcion, metodologias, href, color }) {
    const colores = {
        emerald: { chip: 'bg-emerald-600', btn: 'bg-emerald-600 hover:bg-emerald-700', ring: 'ring-emerald-500', text: 'text-emerald-800' },
        indigo: { chip: 'bg-indigo-600', btn: 'bg-indigo-600 hover:bg-indigo-700', ring: 'ring-indigo-500', text: 'text-indigo-800' },
    }[color];

    return (
        <section className="rounded-2xl bg-white p-6 shadow">
            <div className="mb-4 flex flex-wrap items-center gap-3">
                <span className={`rounded-full ${colores.chip} px-3 py-0.5 text-xs font-semibold text-white`}>{etiqueta}</span>
                <h2 className="text-2xl font-bold">{titulo}</h2>
            </div>
            <p className="mb-5 max-w-3xl text-gray-600">{descripcion}</p>

            <div className="grid gap-4 md:grid-cols-2">
                {metodologias.map((m) => (
                    <div key={m.id} className={`rounded-xl border bg-gray-50 p-4 ${m.recomendado ? `ring-2 ${colores.ring}` : ''}`}>
                        <div className="mb-1 flex items-center justify-between">
                            <h3 className="font-bold">{m.nombre}</h3>
                            {m.recomendado && (
                                <span className={`rounded-full ${colores.chip} px-2 py-0.5 text-xs font-semibold text-white`}>Recomendada</span>
                            )}
                        </div>
                        <p className="mb-3 text-sm text-gray-600">{m.idea}</p>
                        <dl className="space-y-1 text-xs">
                            <Fila k="C — Completitud" v={m.completitud} text={colores.text} />
                            <Fila k="O — Optimalidad" v={m.optimalidad} text={colores.text} />
                            <Fila k="CT" v={m.ctTeorica} text={colores.text} />
                            <Fila k="CE" v={m.ceTeorica} text={colores.text} />
                        </dl>
                    </div>
                ))}
            </div>

            <div className="mt-5">
                <Link href={href} className={`inline-block rounded-lg ${colores.btn} px-5 py-2.5 font-semibold text-white`}>
                    Abrir y comparar las 2 metodologías →
                </Link>
            </div>
        </section>
    );
}

function Fila({ k, v, text }) {
    return (
        <div className="flex items-start justify-between gap-2">
            <dt className={`font-semibold ${text}`}>{k}</dt>
            <dd className="text-right text-gray-600">{v}</dd>
        </div>
    );
}
