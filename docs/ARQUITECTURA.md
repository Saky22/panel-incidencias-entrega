# Arquitectura — revisión y diseño actual

Revisión realizada el 08/10/2026 en dos iteraciones: (1) corrección del diseño heredado e implementación; (2) limpieza estructural hacia bounded contexts en `src/` con reglas de dependencia verificadas por tests.

## 1. Hallazgos de la revisión del diseño anterior

| # | Hallazgo | Impacto | Resolución |
|---|---|---|---|
| 1 | `IncidentStatus` y `Priority` con `$this` / `$newStatus` eliminados (expansión de `$` en un heredoc de shell al generarlos). | El proyecto no compilaba. | Reescritos. Lección: generar ficheros PHP con heredoc entrecomillado (`<<'EOF'`). |
| 2 | `canTransitionTo()` hacía `match` sobre el **destino** en vez de sobre el estado actual. | Reglas invertidas. | Máquina de estados declarativa: `allowedTransitions()` + `canTransitionTo()`. |
| 3 | Reglas de transición duplicadas en el enum y en `DomainStatusTransitionValidator` con mensajes incoherentes. | Dos fuentes de verdad. | Eliminado el validador; el enum es la única fuente y la excepción de dominio genera el mensaje. |
| 4 | `Incident` dependía de `Illuminate\Support\Str`, el constructor forzaba `OPEN` y fechas nuevas. | Dominio acoplado al framework; imposible rehidratar desde BD. | `Incident::open()` (alta) y `Incident::reconstitute()` (persistencia). Ids desde el repositorio (`nextIdentity()`). PHP puro. |
| 5 | Logs/comentarios como arrays sueltos dentro del agregado. | Sin tipos, sin garantía de inmutabilidad. | `IncidentLog` (readonly) y `IncidentComment` como entidades; colas `pullPendingLogs()/pullPendingComments()`. |
| 6 | `blocked` y `resolved` sin salida («intervención manual», «reapertura» sin definir). | Incidencias atascadas para siempre. | `blocked → under_review` y `resolved → open`, ambas con **motivo obligatorio** que queda en el log. Bloquear también exige motivo. |
| 7 | `IncidentRepositoryInterface::findInconsistencies()` y métodos de lectura redundantes. | Mezcla escritura, lectura y diagnóstico en un puerto. | Puerto de escritura mínimo + `IncidentReadModel` (lecturas) + `InconsistencyDetector/Repairer` (diagnóstico). |
| 8 | Migraciones sin índices, columna `timestamp` en vez de `created_at`, sin `metadata`, método `run()` espurio, strings de estado duplicados. | No cumplía el Ejercicio 2. | Migraciones rehechas; los valores `enum` salen de los enums PHP; índices por consulta real; `created_at` con µs. |
| 9 | Tailwind 4 instalado con config y directivas de Tailwind 3; el blade cargaba `css/app.css` inexistente; `app.js` sobrante. | Estilos rotos. | `@import "tailwindcss"` + `@tailwindcss/postcss`; blade con `@vite` + `@inertiaHead`. |
| 10 | Dockerfile con Node 18 (Debian) y Vite 7 (requiere ≥ 20.19); `docker-init.sh` hacía `composer create-project` sobre el proyecto existente. | Build Docker roto / destructivo. | Corregido y, finalmente, **Docker eliminado**: fuera del alcance del enunciado y sin verificar. |
| 11 | El plan hablaba de Laravel 10 / React 18; el proyecto usa Laravel 12, React 19, Inertia 3. Las specs `.kiro/specs` citadas no existen en el repo. | Documentación desalineada. | Plan actualizado; el SDD de referencia es `docs/PLAN_IMPLEMENTACION.md` + `docs/PROGRESO.md`. |

## 2. Limpieza estructural (2ª y 3ª iteración)

La 3ª iteración (revisión contra el enunciado) **simplificó** la lectura: se eliminaron `ListIncidents*`, `GetIncidentHistory*` e `IncidentSummaryEnricher`, que solo pasaban datos; el controlador consulta directamente el puerto `IncidentReadModel`. Además se añadieron las acciones de log `reopened`/`unblocked`, las marcas de revisión manual (`incident_review_flags`) y el índice `created_at`.


| Antes | Problema | Ahora |
|---|---|---|
| `app/{Domain,Application,Infrastructure}` mezclado con el shell de Laravel | Negocio y framework compartían raíz y namespace `App\` | `src/` con namespace `OptimaRetail\`, un directorio por bounded context + `Shared` |
| Capas sin contexto | No escala a un segundo contexto | `src/IncidentManagement/{Domain,Application,Infrastructure,UI}` |
| Carpetas técnicas (`Entities/`, `ValueObjects/`, `Interfaces/`) | Agrupan por tipo, no por concepto | Módulo por agregado: `Domain/Incident/*` |
| `IncidentRepositoryInterface` | Sufijo técnico | `IncidentRepository` (puerto con nombre del lenguaje ubicuo) |
| `UseCases/` + `DTOs/` sueltos | Mezcla escritura/lectura | CQRS ligero: escrituras en `Command/<Caso>/{Caso, CasoHandler}`; lecturas por el puerto `Query/IncidentReadModel` |
| `label()` en enums de dominio, mensajes en español dentro de excepciones | Presentación dentro del dominio | Enums y excepciones con datos; textos en `UI/Labels`, traducción de errores en `UI/Http/DomainErrorRenderer` |
| `new \DateTimeImmutable()` por defecto en entidades | Tiempo implícito, tests frágiles | Puerto `Shared\Domain\Clock`; el dominio recibe siempre `$at` |
| Ids como `string` | Sin validación ni tipo | `IncidentId` (VO sobre `Shared\Domain\ValueObject\Uuid`) |
| Bindings en `AppServiceProvider`, rutas en `routes/web.php`, migraciones/seeders en `database/`, comando en `app/Console`, errores en `bootstrap/app.php` | El contexto no era autocontenido | Todo lo registra `IncidentManagementServiceProvider` (composition root del contexto) |
| `isOverdue` calculado en dos sitios | Regla duplicada | `Priority::dueFrom()` / `IncidentStatus::isFinal()` usados por la entidad y por el read model |
| Read model devolvía etiquetas | Presentación en infraestructura | Read model devuelve valores y datos derivados de reglas de dominio; `UI/Http/Presenter` pone los textos |
| `resources/js/{Components,hooks,lib}` por tipo | Sin fronteras de feature | `features/incidents/{api,components,hooks,lib}`, `shared/{ui,hooks,lib}`, `layouts/`, `Pages/` mínimas |
| Sin control automático | Las reglas se degradan con el tiempo | `tests/Architecture/LayerDependenciesTest.php` falla si una capa importa lo que no debe |

## 3. Estructura

```
src/
├── Shared/                                   Shared Kernel
│   ├── Domain/            Clock (puerto), DomainError, ValueObject/Uuid
│   ├── Application/       Transaction/TransactionManager (puerto)
│   ├── Infrastructure/    Persistence/LaravelTransactionManager, Time/SystemClock
│   └── SharedServiceProvider.php            composition root
│
└── IncidentManagement/                       Bounded context
    ├── Domain/
    │   ├── Incident/                           Módulo del agregado
    │   │   ├── Incident.php                    Raíz de agregado
    │   │   ├── IncidentId.php                  Value object (identidad)
    │   │   ├── IncidentStatus.php              Máquina de estados
    │   │   ├── Priority.php                    SLA
    │   │   ├── LogAction.php
    │   │   ├── IncidentLog.php · IncidentComment.php   Entidades internas
    │   │   ├── IncidentRepository.php          Puerto de escritura
    │   │   └── Exception/                      Errores de dominio (datos, sin textos de UI)
    │   └── Operator/                           Catálogo de trabajadores internos
    │       ├── Operator.php                    Alta flexible + baja lógica (re-alta reactiva)
    │       ├── OperatorId.php · OperatorRepository.php (puerto) · Exception/
    ├── Application/
    │   ├── Command/<Caso>/{Caso, CasoHandler}  CreateIncident · ChangeIncidentStatus · AddIncidentComment
    │   │                                        RegisterOperator · DeactivateOperator
    │   ├── Query/
    │   │   ├── IncidentReadModel.php           Puerto de lectura (search, countByStatus, history, knownAuthors)
    │   │   ├── OperatorReadModel.php           Puerto de lectura del catálogo (list)
    │   │   └── IncidentFilters.php
    │   └── Diagnostics/                        Puertos Detector/Repairer, FixPolicy, DiagnosisOutcome y DiagnoseIncidentData/
    ├── Infrastructure/                         Adaptadores secundarios (driven)
    │   └── Persistence/
    │       ├── Eloquent/                       Repository, ReadModel, Model/
    │       ├── Sql/                            Detector y reparador del diagnóstico
    │       ├── Migrations/ · Seeders/           (000005 operators, OperatorSeeder)
    ├── UI/                                     Adaptadores primarios (driving)
    │   ├── Http/                               Controller/, Request/, Presenter/, DomainErrorRenderer, routes.php
    │   │                                        (IncidentController + OperatorController: /incidents, /operators)
    │   ├── Console/                            DiagnoseIncidentsCommand
    │   └── Labels/                             Vocabulario visible (es-ES)
    └── IncidentManagementServiceProvider.php composition root

app/                     Shell de Laravel: AppServiceProvider (vacío), HandleInertiaRequests, User
resources/js/
├── Pages/Incidents/Index.jsx                 Adaptador de ruta Inertia (solo delega)
├── Pages/Operators/Index.jsx                 Gestión del catálogo de operadores
├── layouts/AppLayout.jsx                     Cabecera + nav + selector de operador
├── features/incidents/
│   ├── IncidentsDashboard.jsx                Contenedor del feature
│   ├── api/incidentsApi.js                   Único punto que conoce URLs y visitas Inertia
│   ├── components/  hooks/  lib/presentation.js
├── features/operators/                       OperatorsManager + api/operatorsApi.js
└── shared/{ui,hooks,lib}                     Piezas sin conocimiento de incidencias (AuthorSelect, useOperator…)
tests/
├── Unit/IncidentManagement/{Domain,Application}   Sin BD ni framework (adaptadores en memoria de tests/Support)
├── Feature/IncidentManagement/UI/{Http,Console}   Integración real (SQLite en memoria)
├── Architecture/LayerDependenciesTest.php
└── Support/  InMemoryIncidentRepository · InMemoryOperatorRepository · FrozenClock · ImmediateTransactionManager
```

## 4. Reglas de dependencia (verificadas por test)

```
         UI ───────► Application ───────► Domain ◄─────── Infrastructure
    (driving)          (casos de uso,        (PHP puro)      (driven: Eloquent, SQL)
                        puertos)                 ▲                    │
                                                 └──── implementa ────┘
```

| Capa | Puede usar | No puede usar |
|---|---|---|
| `Shared\Domain`, `*\Domain` | PHP, `Shared\Domain` | Framework (`Illuminate`, `Carbon`, `App`…), Application, Infrastructure, UI, helpers globales (`now()`, `app()`…) |
| `*\Application` | Domain, `Shared\{Domain,Application}` | Framework, Infrastructure, UI, helpers globales |
| `*\Infrastructure` | Framework, Domain, Application | UI |
| `*\UI` | Framework, Application, Domain (enums/excepciones) | Infrastructure (siempre vía puertos) |
| Composition root | Todo | — |

Además el test comprueba que cada namespace coincide con su ruta y que los puertos son `interface`.

## 5. Decisiones clave

**Estado y log no pueden divergir (desde la app).** `Incident::changeStatus()` valida y genera el asiento en el mismo movimiento; el repositorio guarda incidencia + asientos dentro de `TransactionManager::run()`. Las inconsistencias solo pueden entrar por escrituras fuera del dominio → para eso está el diagnóstico.

**Concurrencia.** `ChangeIncidentStatusHandler` usa `findForUpdate()` (`SELECT … FOR UPDATE`) dentro de la transacción; reintento ante *deadlock* (3).

**Logs append-only.** `IncidentLogModel` lanza excepción en `updating`/`deleting`; FK con `restrictOnDelete`.

**Orden fiable del historial.** `created_at` con microsegundos + UUIDv7 como desempate.

**Enums como única fuente de verdad** de valores (migraciones, validación `Rule::in(X::values())`, transiciones enviadas a la UI). Los textos visibles viven en `UI/Labels`.

**Errores de dominio → UX.** Las excepciones llevan datos (`from`, `to`, `property`, `rule`); `DomainErrorRenderer` los convierte en `ValidationException` por campo con texto en castellano, y Inertia los pinta en el formulario.

**Primitivos en la frontera.** Commands y queries reciben `string`/`int`; los handlers construyen `IncidentId`, `IncidentStatus`, `Priority`. La UI no instancia objetos de dominio.

**Órdenes en vez de casos de uso con sus objetos de datos.** El plan inicial pedía una carpeta de casos de uso y otra de objetos para pasar datos, pero aquí cada escritura es siempre lo mismo: entra una petición, se toca una sola incidencia y se guarda todo en una transacción. Con ese panorama, tener por un lado el objeto con los datos y por otro el caso de uso que lo recibe para pasárselo tal cual al repositorio era tener dos piezas haciendo el trabajo de una, sin que ninguna aportara nada. Así que las junté: cada operación tiene su carpeta con la orden (los datos tal cual vienen de fuera, sin adornar) y su encargado (que carga la incidencia bloqueándola, llama al dominio y guarda). Una operación, una carpeta, y queda clarísimo dónde empieza y acaba cada transacción: un encargado, una transacción. Y para leer ni siquiera hace falta ese rodeo: los listados y el historial que había al principio no hacían más que reenviar lo que les daba la base de datos, así que los quité y el controlador le pregunta directamente al puerto de lectura. Es separar lo justo: órdenes para escribir, lectura directa para mostrar. Si algún día aparece un proceso que toque varias cosas a la vez o que necesite deshacer pasos a medias, entonces sí compensará poner una capa de casos de uso por encima; a día de hoy sería complicarlo por complicarlo.

## 6. Deuda técnica / siguientes pasos

- Autenticación real: hoy el actor es un operador del catálogo (`/operators`) o texto libre (`user_name`) + IP en metadata.
- Filtro "solo vencidas" requeriría persistir `due_at` (hoy se calcula al leer, en el read model).
- Tests de frontend (Vitest + Testing Library) y ESLint con reglas de fronteras (`features/*` no importa de otro feature).
- `app/Models/User.php` y la migración de usuarios son del esqueleto de Laravel (sin uso hasta que haya autenticación).
- Las migraciones conservan el prefijo `2024_10_08_*` para no invalidar bases de datos ya migradas.
