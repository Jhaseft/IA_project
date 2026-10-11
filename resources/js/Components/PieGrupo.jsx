const INTEGRANTES = [
    'Arispe León Juan David',
    'Camacho Salazar Maximiliano',
    'Humerez Soliz Joseph Harold',
    'Paredes Pava Jhoel',
    'Renjifo Murillo Nayra Jael',
    'Saat Solares Jhaseft Rene',
];

/** Pie de página institucional con los integrantes del grupo (se muestra en todas las páginas). */
export default function PieGrupo() {
    return (
        <footer className="bg-gray-900 text-gray-300">
            <div className="mx-auto flex max-w-6xl flex-col gap-4 px-6 py-6 md:flex-row md:items-start md:justify-between">
                <div className="shrink-0">
                    <p className="text-sm font-bold uppercase tracking-wide text-white">Universidad Mayor de San Simón</p>
                    <p className="text-xs text-gray-400">Inteligencia Artificial · Práctica Primer Parcial</p>
                    <p className="text-xs text-gray-400">Docente: Lic. Patricia Rodríguez Bilbao</p>
                </div>

                <div className="md:text-right">
                    <p className="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-400">Integrantes</p>
                    <ul className="grid grid-cols-1 gap-x-6 text-sm sm:grid-cols-2 lg:grid-cols-3">
                        {INTEGRANTES.map((nombre) => (
                            <li key={nombre}>{nombre}</li>
                        ))}
                    </ul>
                </div>
            </div>
            <div className="border-t border-gray-800 py-3 text-center text-xs text-gray-500">
                © {new Date().getFullYear()} · Técnicas de Búsqueda para la Resolución de Problemas
            </div>
        </footer>
    );
}
