# Uso de IA: dirección humana, ejecución asistida

Este proyecto se trabajó como se trabaja con un programador junior aventajado al lado: la IA propone y redacta, la persona dirige, revisa, corrige y decide. Ninguna entrega de la IA llegó a la rama sin pasar por revisión humana, y varias se devolvieron enteras hasta quedar como se pedía.

## Quién hizo qué

| Parte | Rol real |
|---|---|
| Persona | Dirección del trabajo: plan de desarrollo, elección de pila y de arquitectura entre las opciones, definición de las reglas que el enunciado dejaba abiertas, revisión de cada entrega, detección y corrección de los errores de la IA, recortes de alcance y verificación final (tests, MySQL 8, navegador). |
| IA | Redacción supervisada: borradores de plan, de código, de tests y de documentación, siempre sobre instrucciones concretas y siempre revisados después. |

## Herramientas

| Herramienta | Para qué se usó |
|---|---|
| Kiro (modelo deepseek-3.2) | Primer borrador del plan (`docs/PLAN_IMPLEMENTACION.md`) y del seguimiento (`docs/PROGRESO.md`), luego corregidos y completados por la persona. |
| Agente local Aclinked AI (gpt-oss:120b) | Primer intento de implementación. Se atascó editando solo las migraciones hasta agotar su límite de pasos; se descartó ese camino y se siguió con otras herramientas. |
| Claude (Cowork) | Ayudante de implementación y refactorización bajo instrucciones directas (revisión de arquitectura, paso a DDD/hexagonal, diagnóstico, documentación). |

## Cómo se dirigió a la IA (prompts reales, literales en lo esencial)

No se le pidió «haz la aplicación», sino piezas concretas con criterios de aceptación y verificación. Ejemplos:

1. **Revisión de arquitectura contra el plan.**
   > «Seguir el plan de SDD que hay en docs y revisar el diseño actual de arquitectura. Señala qué incumple el plan, qué está roto (aunque compile) y qué sobra. No escribas código todavía: primero el diagnóstico.»
   > Se exigía: lista de hallazgos con impacto y propuesta por cada uno. De ahí salieron los enums reescritos, la máquina de estados declarativa y la eliminación de Docker.

2. **Limpieza estructural con reglas explícitas.**
   > «Limpieza de estructuras de carpetas siguiendo rigurosamente DDD, hexagonal y demás patrones de diseño: bounded contexts en `src/`, dominio en PHP puro sin framework, tiempo e identificadores por puertos, y un test de arquitectura que falle si una capa importa lo que no debe.»
   > Se exigía: test `LayerDependenciesTest` en verde y suite completa sin romper nada.

3. **Revisión contra el enunciado, punto por punto.**
   > «¿Qué más añadirías o modificarías según la prueba técnica original?» (con el PDF del enunciado), seguido de «aplica todos los puntos». Después: «revisa cada ejercicio del enunciado uno por uno y dime qué falta o qué sobra, con fichero y línea».
   > De ahí salieron: MySQL como único camino verificado, log explícito de reapertura, marcas de revisión manual, índice `created_at` y la simplificación de la lectura (fuera `ListIncidents` y compañía: eran pasarelas).

4. **Prudencia obligatoria en el diagnóstico.**
   > «El comando de diagnóstico, en seco por defecto. Corregir solo lo seguro, lo dudoso se marca para revisión manual y nunca se toca `incidents.status` ni se reescribe un log. Cada corrección en su transacción, revalidando dentro de ella, e idempotente.»
   > Se exigía: demostración repetida del `--fix` sin duplicar nada y documentar cada consulta de detección.

5. **Verificación antes de dar nada por hecho.**
   > «Nada está terminado hasta que pasen los tests en SQLite y en MySQL 8, compile el frontend y lo pruebes en navegador (alta, filtros, bloqueo con motivo, comentarios, revisión). Si algo falla, lo corriges tú y repites.»
   > Se exigía: 81 tests en verde en ambos motores, `npm run build` y prueba manual documentada.

El patrón es siempre el mismo: instrucción concreta + qué se acepta como válido + cómo se comprueba. La IA redacta; la persona revisa cada entrega contra el enunciado y solo entonces se queda.

## Partes redactadas con ayuda de IA (y verificadas por la persona)

El grueso del código (`src/`, `resources/js/`, `tests/`), las migraciones y seeders, el comando de diagnóstico y la documentación. Cada entrega se verificó con tests automáticos (81; también contra MySQL 8), `npm run build` y pruebas en navegador (alta, filtros, bloqueo con motivo, comentarios, marcas de revisión, gestión de operadores).

## Decisiones que tomó la persona (no la IA)

- Inertia + React en lugar de Blade/Livewire.
- Arquitectura por capas DDD/hexagonal con bounded contexts en `src/`, elegida entre las opciones que se propusieron.
- Revisar la solución contra el enunciado y aplicar las correcciones: MySQL como camino principal, log explícito de reapertura, marcas de revisión manual, simplificar la capa de lectura y limpiar el repositorio.
- Reglas que el enunciado no definía: desbloquear y reabrir exigen motivo; SLA por prioridad para «vencida».
- Órdenes (`Command/<Caso>`) en vez de casos de uso con objetos de datos separados: dos piezas haciendo el trabajo de una (justificado en `docs/ARQUITECTURA.md` §5).
- Modelo propio de operadores con alta flexible y baja lógica, y selector de autor compartido alimentado desde la base de datos.
- Recortes de alcance: Docker fuera (el enunciado no lo pide y no se había podido verificar).

## Errores de la IA que detectó y corrigió la persona

| Error | Corrección |
|---|---|
| Enums generados con `$this` borrado por expansión de shell (heredoc sin comillas): el proyecto no compilaba. | Reescritos; se validan con `php -l` y tests. |
| `canTransitionTo()` evaluaba el estado destino en vez del actual (reglas invertidas). | Máquina de estados declarativa + test de la matriz 4×4. |
| Dominio acoplado a Laravel (`Str`, fechas implícitas) y reglas de transición duplicadas. | Dominio en PHP puro, reloj inyectado, una sola fuente de reglas. |
| Tailwind 4 configurado como v3; Dockerfile con Node 18 (Vite 7 necesita ≥ 20.19). | Configuración v4. Docker se eliminó después: fuera de alcance y sin verificar. |
| Mensajes de validación en inglés; ruta de páginas de Inertia 3 en minúscula. | Mensajes en castellano en la capa UI; `config/inertia.php`. |
| Test que fallaba por dos altas en el mismo segundo. | Búsqueda por título y desempate por UUIDv7 en el listado. |
| Sobreingeniería en la capa de lectura (piezas que solo pasaban datos). | Eliminadas: el controlador consulta directamente el puerto de lectura. |
| `findByName` sensible a mayúsculas en SQLite (en MySQL no). | Comparación con `LOWER()` explícito, portable. |
| Documentación de IA anterior con métricas no verificables («ahorro ~90 min»). | Eliminadas; este documento solo recoge hechos. |
