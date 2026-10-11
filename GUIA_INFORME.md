# Guía para el Informe — Práctica Primer Parcial (IA)

> **Materia:** Inteligencia Artificial · **Docente:** Lic. Patricia Rodríguez Bilbao
> **Tema:** Técnicas de búsqueda para la resolución de problemas (Caps. 3, 4 y 5)

Este documento explica **todo lo que hace el prototipo** para que puedas llenar el
informe impreso con éxito. Está ordenado igual que los puntos que pide la práctica
(I. Introducción … VI. Bibliografía). Copia, adapta y agrega tus capturas.

---

## 0. Qué construimos (resumen)

Se eligieron **dos planteamientos**, uno de cada grupo, y en cada uno se implementaron
**2 metodologías de búsqueda**:

| Problema | Grupo | Capítulo | Metodología 1 | Metodología 2 |
|---|---|---|---|---|
| **Misioneros y Caníbales** | 1 (c) | 3 — Búsqueda no informada | **BFS** (Primero en Anchura) | **DFS** (Primero en Profundidad) |
| **Juego de Damas** | 2 (a) | 5 — Búsqueda con adversario | **Minimax** | **Poda Alfa-Beta** |

La aplicación permite, en cada problema, **ejecutar una metodología (queda guardada),
luego la otra, y compararlas** con los 4 parámetros de evaluación de búsquedas.

### Tecnologías utilizadas
- **Backend:** PHP 8.3 + **Laravel 13** (toda la lógica de búsqueda está en PHP).
- **Frontend:** **React 18** + **Inertia.js** + **Tailwind CSS** (interfaz y visualizaciones).
- **Build / dev:** Vite. **Base de datos:** no se requiere para la lógica de búsqueda.

### Cómo ejecutar el prototipo
```bash
# 1) Dependencias (solo la primera vez)
composer install
npm install

# 2) Levantar la aplicación (dos terminales)
php artisan serve        # backend  -> http://localhost:8000
npm run dev              # frontend (Vite)

# 3) Abrir en el navegador
http://localhost:8000
```
Desde la portada entras a **Misioneros y Caníbales** y a **Juego de Damas**.

### Mapa de archivos (código fuente para anexar al informe)
| Parte | Archivo |
|---|---|
| Lógica Misioneros (BFS/DFS) | `app/Services/BuscadorMisioneros.php` |
| Controlador Misioneros | `app/Http/Controllers/MisionerosController.php` |
| Interfaz Misioneros | `resources/js/Pages/Misioneros.jsx` |
| Lógica Damas (Minimax/Alfa-Beta) | `app/Services/JuegoDamas.php` |
| Controlador Damas | `app/Http/Controllers/DamasController.php` |
| Interfaz Damas | `resources/js/Pages/Damas.jsx` |
| Portada | `resources/js/Pages/Welcome.jsx` |
| Rutas | `routes/web.php` |

---

## I. Introducción (sugerencia de contenido)

La resolución de problemas mediante búsqueda es uno de los pilares de la IA: consiste
en representar un problema como un **espacio de estados** y encontrar una **secuencia de
acciones** (o la mejor jugada) que lleve de un estado inicial a una meta. En esta
práctica se implementan y comparan técnicas de los capítulos 3 (búsqueda no informada),
4 (informada) y 5 (con adversario) sobre dos problemas clásicos: **Misioneros y
Caníbales** (problema de espacio de estados) y el **Juego de Damas** (problema con
adversario). El objetivo es observar en la práctica cómo distintas metodologías llegan
(o no) a la solución y cómo se comportan según los parámetros de evaluación.

---

## II. Objetivos (sugerencia)

**General:** Implementar y comparar técnicas de búsqueda de IA mediante un prototipo de
software que resuelva dos problemas clásicos.

**Específicos:**
- Formular formalmente ambos problemas (REAS, P→A, conjunto problema, meta).
- Implementar 2 metodologías de búsqueda por problema.
- Visualizar las soluciones y el árbol de búsqueda.
- Comparar las metodologías con los 4 parámetros: completitud, optimalidad,
  complejidad temporal y complejidad espacial.

---

## III. Marco Teórico (resumen por metodología — máx. 1 plana c/u)

**BFS — Primero en Anchura.** Explora el árbol **nivel por nivel** usando una **cola
FIFO**. Expande primero todos los nodos de profundidad *d* antes de pasar a *d+1*. Es
**completa** y **óptima** cuando el costo de cada paso es uniforme, pero guarda toda la
frontera en memoria → alto costo espacial. Complejidad temporal y espacial: **O(b^d)**.

**DFS — Primero en Profundidad.** Baja por una rama hasta el fondo usando una **pila
LIFO**; retrocede (backtracking) al agotar una rama. Con control de estados repetidos en
un espacio finito es **completa**, pero **no es óptima** (devuelve la primera solución que
encuentra, no la más corta). Su ventaja es la **memoria**: solo guarda el camino actual →
**O(b·m)**; su costo temporal en el peor caso es **O(b^m)**.

**Minimax (búsqueda con adversario).** Modela juegos de dos jugadores por turnos. Un
jugador **MAX** (la IA) busca maximizar el valor y el otro **MIN** (el rival) minimizarlo.
Construye el **árbol de juego** y propaga hacia arriba el valor **f(v)** de una **función
de evaluación (heurística)** evaluada en las hojas (al límite de profundidad). Garantiza
el mejor resultado **suponiendo juego óptimo del rival**. Complejidad temporal **O(b^m)**.

**Poda Alfa-Beta.** Es Minimax **optimizado**: mantiene dos cotas **α** (lo mejor
asegurado para MAX) y **β** (lo mejor para MIN) y **poda** las ramas que ya no pueden
cambiar la decisión (cuando **α ≥ β**). Devuelve **exactamente el mismo valor y la misma
jugada** que Minimax, pero evaluando **muchos menos nodos**. En el mejor caso baja de
**O(b^m)** a **O(b^(m/2))**.

> Los 4 **parámetros de evaluación de búsquedas** son:
> **Completitud** (¿siempre encuentra solución si existe?), **Optimalidad** (¿encuentra la
> mejor?), **Complejidad Temporal** (nodos generados/evaluados) y **Complejidad Espacial**
> (máximo de nodos en memoria).

---

## IV. Ingeniería

### IV.A — Problema 1: Misioneros y Caníbales (3.9 AIMA)

**Descripción.** Tres misioneros y tres caníbales están en una orilla de un río con un
barco que lleva 1 o 2 personas. Hay que pasar a todos a la otra orilla **sin que, en
ninguna orilla, los caníbales superen en número a los misioneros** (si hay misioneros).

**Formulación de la Meta.** Que las 6 personas y el barco estén en la orilla derecha:
estado meta **Em = [0, 0, 0]**.

**Formulación del Problema (REAS / P→A / Conjunto Problema).**

*REAS (Rendimiento, Entorno, Actuadores, Sensores):*
- **Rendimiento:** pasar a todos a la otra orilla, con el **mínimo de viajes** y sin que
  ningún misionero sea superado por caníbales.
- **Entorno:** río, dos orillas, un barco (capacidad 2), 3 misioneros y 3 caníbales.
  Es **totalmente observable, determinista, discreto, estático, secuencial y de agente único**.
- **Actuadores:** cargar/descargar personas y cruzar el barco de una orilla a la otra.
- **Sensores:** cantidad de misioneros y caníbales en cada orilla y la posición del barco.

*Representación del estado:* **[m, c, b]** donde `m` = misioneros y `c` = caníbales en la
orilla **izquierda**, y `b` = barco (**1** izquierda / **0** derecha). Estado inicial
**Ei = [3, 3, 1]**.

*P → A (Problema → Acción / operadores):* mover por el río una de estas cargas
(misioneros, caníbales): **(1,0), (2,0), (0,1), (0,2), (1,1)**. Si el barco está en la
izquierda la carga cruza a la derecha; si está en la derecha, regresa. Una acción es
**válida** solo si el estado resultante cumple la restricción en ambas orillas.
*Costo de cada acción:* **1 (un viaje)**.

*Conjunto Problema:* todos los estados válidos alcanzables desde Ei (de los 32 estados
posibles, solo una parte son válidos). El **diagrama del espacio de estados** se puede
dibujar a partir de la ruta y de los nodos por nivel que muestra la aplicación.

**Tecnologías:** PHP (clase `BuscadorMisioneros`), React para la visualización del río.

**Prototipo (qué mostrar / capturas).**
1. Pantalla inicial con Ei = [3,3,1] y Em = [0,0,0] (escena del río).
2. Botón **"Ejecutar BFS"** → muestra la ruta solución paso a paso + los 4 parámetros.
3. Botón **"Ejecutar DFS"** → idem, queda guardado al lado.
4. **Tabla comparativa** BFS vs DFS.

**Evaluación comparativa (datos verificados del prototipo, Ei=[3,3,1] → Em=[0,0,0]):**

| Parámetro | **BFS (Anchura)** | **DFS (Profundidad)** |
|---|---|---|
| Completitud | Sí | Sí (espacio finito + visitados) |
| Optimalidad | **Sí** (mínimo de viajes) | No (una solución, no la más corta) |
| Viajes de la solución | **11** | 11 |
| Complejidad Temporal (nodos generados) | 15 | 15 |
| Complejidad Espacial (máx. en memoria) | **3** | 4 |

> *Nota:* en este problema el espacio de estados es muy pequeño, por eso ambos encuentran
> 11 viajes; la diferencia conceptual clave es que **BFS garantiza** la solución óptima y
> **usa menos memoria pico aquí**, mientras que **DFS no garantiza** la optimalidad en general.

**Respuestas a las preguntas del enunciado 3.9:**
- **b) ¿Es buena idea comprobar los estados repetidos?** **Sí.** El espacio de estados es
  pequeño y con ciclos (se puede ir y volver). Sin control de **estados repetidos** la
  búsqueda entraría en bucles infinitos (ida y vuelta del barco) y generaría muchísimos
  nodos. Con el conjunto de **visitados** la búsqueda termina rápido y es eficiente.
- **c) ¿Por qué la gente tarda en resolverlo si el espacio es simple?** Porque la solución
  **obliga a movimientos aparentemente "hacia atrás"** (regresar personas a la orilla de
  origen), algo **contra-intuitivo** para las personas, que tienden a avanzar siempre.
  El algoritmo, en cambio, explora todas las opciones sin ese sesgo.

---

### IV.B — Problema 2: Juego de Damas

**Descripción.** Juego por turnos de dos jugadores en un tablero 8×8. El humano juega
**abajo (blancas)** y la IA **arriba (rojas)**. Las fichas avanzan en diagonal, capturan
saltando (captura **obligatoria** y en **cadena**) y **coronan a dama** al llegar a la
última fila. Gana quien deja al rival sin piezas o sin movimientos.

**Formulación de la Meta.** Para la IA: **elegir, en su turno, la mejor jugada** que la
acerque a ganar (maximizar la función de evaluación f(v)); a largo plazo, dejar al rival
sin piezas o sin movimientos legales.

**Formulación del Problema (REAS / P→A / Conjunto Problema).**

*REAS:*
- **Rendimiento:** ganar la partida; en cada turno, maximizar **f(v)** (ventaja material y
  posicional de la IA).
- **Entorno:** tablero 8×8, 12 piezas por bando y un **oponente**. Es **observable,
  determinista, discreto, secuencial** y, a diferencia de Misioneros, **multiagente
  (con adversario)**.
- **Actuadores:** mover una ficha en diagonal, capturar piezas rivales, coronar.
- **Sensores:** lectura de la posición de todas las piezas en el tablero y de quién mueve.

*P → A (Problema → Acción):* dado el **estado del tablero** y el **turno**, el agente
**genera todos los movimientos legales** (respetando la captura obligatoria) y **elige uno**
mediante **Minimax** o **Poda Alfa-Beta** explorando el árbol de juego hasta cierta
**profundidad**, evaluando las hojas con la heurística **f(v)**.

*Función de evaluación f(v) (desde la IA):*
`f(v) = (material IA − material rival)`, con **peón = 100**, **dama = 170**, más un pequeño
**bono por avance** de los peones. Positivo → la IA va ganando; negativo → el humano.

*Conjunto Problema:* el **árbol de juego** (niveles alternados **MAX** = IA y **MIN** =
humano). La aplicación lo dibuja con el valor **f(v)** en cada nodo y, en Alfa-Beta, marca
las ramas **podadas** (igual que en las diapositivas de clase).

**Tecnologías:** PHP (clase `JuegoDamas`: reglas, Minimax y Alfa-Beta), React para el
tablero jugable y el dibujo del árbol.

**Prototipo (qué mostrar / capturas).**
1. Tablero inicial; el humano hace un movimiento (clic en ficha **amarilla** → casilla **verde**).
2. En el turno de la IA: botón **"Analizar con Minimax"** → muestra jugada elegida, nodos
   evaluados y el **árbol MAX/MIN con f(v)**.
3. Botón **"Analizar con Alfa-Beta"** → muestra el mismo árbol con **ramas podadas (✂)**.
4. **Tabla comparativa** Minimax vs Alfa-Beta y botón **"Ejecutar jugada de la IA"**.

**Evaluación comparativa (ejemplo verificado, posición inicial, profundidad 2):**

| Parámetro | **Minimax** | **Poda Alfa-Beta** |
|---|---|---|
| Jugada elegida | b6-a5 | **b6-a5 (la misma)** |
| Valor f(v) | 0 | 0 |
| Completitud (hasta la profundidad) | Sí | Sí |
| Optimalidad (al límite de profundidad) | Sí | Sí (mismo resultado) |
| **Complejidad Temporal (nodos evaluados)** | **57** | **21** |
| Ramas podadas | 0 | **6** |

> **Conclusión clave de la comparación:** Minimax y Alfa-Beta **eligen la misma jugada con
> el mismo valor**, pero **Alfa-Beta evalúa muchos menos nodos** (21 vs 57) gracias a la
> poda → es más eficiente en tiempo sin perder calidad. *Los números exactos cambian según
> la posición y la profundidad; usa los que muestre tu captura.*

---

## V. Conclusiones (ideas para redactar)

- Ambos problemas se modelaron como búsqueda y se resolvieron con **2 metodologías** cada uno.
- En **Misioneros**, **BFS** garantiza la solución óptima (mínimo de viajes); **DFS** la
  encuentra pero sin garantía de optimalidad. El **control de estados repetidos** fue
  esencial para evitar ciclos.
- En **Damas**, **Minimax** y **Alfa-Beta** dan el **mismo resultado**, pero **Alfa-Beta es
  notablemente más eficiente** (evalúa menos nodos), confirmando la teoría de la poda.
- La elección de la metodología depende del problema: optimalidad vs memoria (Cap. 3) y
  eficiencia del árbol de juego (Cap. 5).

---

## VI. Bibliografía (sugerida)

- Russell, S. & Norvig, P. *Inteligencia Artificial: Un Enfoque Moderno* (3ª ed.).
  Caps. 3 (Resolver problemas mediante búsqueda), 4 (Búsqueda informada) y 5 (Búsqueda
  entre adversarios). — *Fuente del problema 3.9.*
- Amarel, S. (1968). *On representations of problems of reasoning about actions.*
  (Formulación original de Misioneros y Caníbales.)
- Documentación oficial: Laravel (https://laravel.com), React (https://react.dev),
  Inertia.js (https://inertiajs.com), Tailwind CSS (https://tailwindcss.com).

---

## Anexo — Checklist antes de imprimir

- [ ] Portada con integrantes del grupo (5 a 8).
- [ ] Capturas: Misioneros (BFS, DFS y tabla comparativa).
- [ ] Capturas: Damas (árbol Minimax, árbol Alfa-Beta con podas, tabla comparativa).
- [ ] Diagrama del espacio de estados de Misioneros (se arma con la ruta + nodos por nivel).
- [ ] Copia digital del código (ver "Mapa de archivos").
- [ ] Puntos I a VI completos y coherentes.
