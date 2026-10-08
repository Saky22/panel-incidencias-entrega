# Panel operativo de incidencias

Alta, seguimiento y trazabilidad de incidencias operativas (tiendas, TPV, logística). Cada cambio de estado queda registrado en un log inmutable, y un comando de diagnóstico detecta inconsistencias entre el estado de una incidencia y su historial y las corrige con prudencia.

**Stack:** PHP 8.4 · Laravel 12 · MySQL 8 · Inertia.js 3 + React 19 · Tailwind CSS 4

---

## 1. Requisitos

| Herramienta | Versión |
|---|---|
| PHP | ≥ 8.4.1 (lo exigen las dependencias de `composer.lock`, p. ej. Symfony 8) con `pdo_mysql`, `mbstring`, `xml`, `ctype`, `json` |
| Composer | 2.x |
| Node.js / npm | Node ≥ 20.19 (lo exige Vite 7) |
| MySQL | 8.0 o superior (el diagnóstico usa funciones de ventana) |

No hay autenticación (fuera de alcance), así que **no hay usuario de prueba**: en la cabecera se elige el operador en un selector con los nombres ya usados en la base de datos (admite escribir uno nuevo), que queda registrado en cada log junto con la IP. El mismo selector se usa para autor del comentario, solicitante y asignado. Los operadores internos se gestionan en `/operators` (alta y baja lógica; dar de alta un nombre dado de baja lo reactiva).

## 2. Instalación y arranque

```bash
bin/setup                   # camino fácil: comprueba requisitos, crea la BD, migra + datos de ejemplo y compila el frontend
php artisan serve           # http://127.0.0.1:8000
```

`bin/setup` no borra nada si ya hay tablas (usa `migrate`); con `bin/setup --fresh` recrea la demo desde cero. Si tu root de MySQL tiene contraseña: `DB_ROOT_PASSWORD=xxx bin/setup`. Incluye las inconsistencias simuladas para probar `php artisan incidents:diagnose --fix`. El README y la arquitectura también se leen dentro de la app, en **Ayuda**.

<details>
<summary>Instalación manual (alternativa)</summary>

```bash
# 1. Base de datos (ajusta usuario/contraseña a tu MySQL)
mysql -uroot -e "CREATE DATABASE incident_panel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER IF NOT EXISTS 'incident_user'@'127.0.0.1' IDENTIFIED BY 'incident_password';
  GRANT ALL ON incident_panel.* TO 'incident_user'@'127.0.0.1';"

# 2. Dependencias y configuración
composer install
cp .env.example .env            # revisa DB_* si usas otras credenciales
php artisan key:generate

# 3. Migraciones + datos de ejemplo (incluye las inconsistencias simuladas)
php artisan migrate:fresh --seed

# 4. Frontend y servidor
npm install
npm run build                   # o `npm run dev` en otra terminal para desarrollo
php artisan serve               # http://127.0.0.1:8000
```
</details>

## 3. Funcionalidades

| Ejercicio | Implementación |
|---|---|
| 1. Pantalla | Cabecera, contadores por estado, buscador, tabla (escritorio) / tarjetas (móvil), alta en modal, botones de cambio de estado por fila, panel lateral con historial y comentarios. |
| 2. Modelo | `incidents`, `incident_logs`, `incident_comments` (+ `incident_review_flags` para el diagnóstico). UUID, FKs, índices por estado/prioridad/fecha. Estados centralizados en el enum `IncidentStatus`. Transacción en cada escritura que toca incidencia + log. |
| 3. Acciones | Crear con validación, listar con filtros (estado, prioridad, texto), cambiar estado respetando transiciones, log de cada cambio (anterior, nuevo, actor, fecha, motivo, IP), historial y comentarios. Errores de validación y de negocio mostrados en el campo correspondiente. |
| 4. Interacción | Filtro rápido por estado sin recargar, confirmación antes de resolver/bloquear/reabrir, contadores por estado, resaltado de vencidas (SLA), bloqueadas y «revisar datos». |
| 5. Diagnóstico | `php artisan incidents:diagnose` con dry-run por defecto. Ver §5. |

### Estados y transiciones

```
abierta ──► en revisión ──► resuelta ──(reabrir · motivo)──► abierta
                │  ▲
                ▼  │ (desbloquear · motivo)
             bloqueada (bloquear · motivo)
```

Cada transición genera un asiento en `incident_logs` con una acción explícita:

| Acción | Cuándo |
|---|---|
| `created` | Alta (`∅ → open`) |
| `status_changed` | Transición ordinaria |
| `reopened` | `resolved → open`: **log explícito de reapertura** (exige motivo) |
| `unblocked` | `blocked → under_review` (exige motivo) |
| `reconciled` | Asiento compensatorio escrito por el diagnóstico (nunca por un operador) |

**Vencida:** no resuelta y fuera del SLA de su prioridad (crítica 4 h · alta 24 h · media 72 h · baja 168 h).

## 4. Frontend: estado, acciones y refresco

| Pregunta | Respuesta |
|---|---|
| ¿Dónde vive el estado? | Los datos (`incidents`, `counts`, `filters`, `options`) son **props de Inertia** que manda el servidor. El estado local de UI es mínimo: filtros en `useIncidentFilters`, diálogo de confirmación en `useStatusTransition`, operador en `useOperator` (localStorage). |
| ¿Dónde se dispara la acción? | Los componentes solo emiten eventos (`onTransition`, `onSelect`). `useStatusTransition` decide si confirmar y llama a `features/incidents/api/incidentsApi.js`, el único sitio que conoce URLs. |
| ¿Cómo se refresca? | Con **visitas parciales de Inertia**: tras un cambio solo se piden `incidents`, `counts` y `flash`; al filtrar, `incidents`, `counts` y `filters`. No hay recarga completa ni caché duplicada en el cliente. El historial se pide aparte (JSON) y se recarga tras cada cambio. |

## 5. Diagnóstico y corrección de inconsistencias

### Datos simulados

`InconsistentDataSeeder` (incluido en `migrate:fresh --seed`) escribe directamente en la base de datos, saltándose el dominio, como haría un `UPDATE` manual, un script antiguo o un doble envío:

| Incidencia | Inconsistencia | Origen realista |
|---|---|---|
| `[SIM-1]` | Resuelta sin log de resolución | `UPDATE incidents SET status='resolved'` manual |
| `[SIM-2]` | Estado (`under_review`) ≠ último log (`blocked`) | Script que revierte el estado sin dejar asiento |
| `[SIM-3]` | Log duplicado (mismo cambio dos veces en 1 s) | Doble clic / reintento sin idempotencia |
| `[SIM-4]` | Log incoherente: `open → resolved`, transición prohibida | Importación fuera del dominio |
| `[SIM-5]` | Log incoherente: reapertura registrada como `status_changed`, sin log explícito de reapertura | Código antiguo |

### Ejecución

```bash
php artisan incidents:diagnose                                   # DRY-RUN: solo informa
php artisan incidents:diagnose --fix                             # corrige lo seguro y marca el resto
php artisan incidents:diagnose --fix --accept-current-status     # además reconcilia «estado ≠ último log»
php artisan incidents:diagnose --json                            # salida para CI/monitorización
```

Resultado con los datos de ejemplo:

```text
dry-run                        Total: 5 · se corregirían: 2 · requieren confirmación: 1 · revisión manual: 2
--fix                          corregidas: 2 · marcadas para revisión: 3
--fix (otra vez)               Total: 3 · marcadas para revisión: 3   ← idempotente, no duplica nada
--fix --accept-current-status  corregidas: 1 · marcadas para revisión: 2 · marcas cerradas: 1
```

### Consultas de detección (MySQL 8)

«Último log» de una incidencia (orden por `created_at` con microsegundos; desempate por UUIDv7):

```sql
(SELECT l2.id FROM incident_logs l2 WHERE l2.incident_id = i.id
  ORDER BY l2.created_at DESC, l2.id DESC LIMIT 1)
```

**1. Resuelta sin log de resolución:** está en `resolved` y ningún asiento la lleva a `resolved`.

```sql
SELECT i.id, i.title, last.new_value AS last_logged
  FROM incidents i
  LEFT JOIN incident_logs last ON last.id = (<último log>)
 WHERE i.status = 'resolved'
   AND NOT EXISTS (SELECT 1 FROM incident_logs l
                    WHERE l.incident_id = i.id AND l.new_value = 'resolved'
                      AND l.action IN ('status_changed','reopened','unblocked','reconciled'));
```

**2. Estado ≠ último log:** el estado guardado no es el que deja el historial (o no hay historial). Se excluyen las ya reportadas en (1).

```sql
SELECT i.id, i.title, i.status, last.new_value AS last_logged
  FROM incidents i
  LEFT JOIN incident_logs last ON last.id = (<último log>)
 WHERE last.id IS NULL OR last.new_value <> i.status;
```

**3. Duplicados y logs incoherentes:** una pasada comparando cada asiento con el anterior de la misma incidencia.

```sql
SELECT l.*, LAG(l.id) OVER w AS prev_id, LAG(l.action) OVER w AS prev_action,
       LAG(l.old_value) OVER w AS prev_old, LAG(l.new_value) OVER w AS prev_new,
       LAG(l.user_name) OVER w AS prev_user, LAG(l.created_at) OVER w AS prev_created_at
  FROM incident_logs l
WINDOW w AS (PARTITION BY l.incident_id ORDER BY l.created_at, l.id);
```

- **Duplicado:** misma acción, valores y usuario que el anterior, con 5 s o menos de diferencia.
- **Incoherente:** `old_value` no enlaza con el `new_value` anterior, la transición no la permite la máquina de estados, o la acción no es la que corresponde (p. ej. reapertura sin `reopened`). Se reutilizan las reglas del dominio, no se duplican en SQL.

### Qué se corrige y qué no

| Caso | Con `--fix` | Por qué |
|---|---|---|
| Resuelta sin log | **Se crea el log que falta** como `reconciled` (último estado → `resolved`), con nota de que no es un cambio real. | El estado final es inequívoco; falta la constancia. |
| Duplicado | **Se borra el duplicado** y se conserva el original. Antes se copia la fila a `storage/app/diagnostics/removed_logs_YYYYMMDD.jsonl`. | Es una repetición exacta: no se pierde información. |
| Estado ≠ último log | **Se marca para revisión manual.** Solo con `--accept-current-status` se reconcilia asumiendo el estado actual como verdad. | Hay dos hipótesis (el log es correcto o lo es el estado) y solo una persona puede decidir. |
| Log incoherente | **Se marca para revisión manual**, nunca se corrige. | No hay información para reconstruir lo que pasó; corregir sería inventar historia. |

Garantías:

1. Sin `--fix` no se escribe nada.
2. **Nunca se modifica `incidents.status`** ni se reescribe un log existente.
3. Cada corrección **revalida los datos dentro de su transacción** (con bloqueo) y aborta si cambiaron desde la detección.
4. Una transacción por corrección: un fallo no deja nada a medias.
5. Las **marcas de revisión** (`incident_review_flags`) aparecen en el panel como «Revisar datos» y **se cierran solas** cuando la inconsistencia desaparece.

## 6. Decisiones técnicas

- **Arquitectura por capas (DDD / hexagonal) en `src/`.** El dominio (`Incident` y su máquina de estados) es PHP puro y garantiza que no hay cambio de estado sin log. Los casos de uso (`Application/Command`) delimitan transacciones; Eloquent y SQL son adaptadores sustituibles. Las lecturas van directas a un puerto de consulta (`IncidentReadModel`) porque no tienen lógica que orquestar. `app/` queda solo como el arranque de Laravel. Detalle: [`docs/ARQUITECTURA.md`](docs/ARQUITECTURA.md).
- **Concurrencia:** el cambio de estado lee la incidencia con `SELECT … FOR UPDATE` dentro de la transacción, para que dos operadores no validen contra el mismo estado viejo.
- **Logs append-only:** Eloquent impide modificarlos o borrarlos y la FK es `RESTRICT` (la auditoría no se borra en cascada).
- **Orden fiable:** `created_at` con microsegundos e ids UUIDv7 (ordenables por tiempo) como desempate.
- **Sin strings mágicos:** los enums alimentan migraciones, validación y las transiciones que pinta la UI; los textos visibles viven en `UI/Labels`.
- **Fuera de alcance a propósito:** autenticación, roles, notificaciones, deploy y Docker (el enunciado no lo pide; se priorizó un único camino de instalación verificado).

## 7. Tests

```bash
php artisan test                           # 83 tests (SQLite en memoria, sin configuración)
php artisan test --testsuite=Unit          # dominio + casos de uso, sin BD
php artisan test --testsuite=Architecture  # reglas de dependencia entre capas

# Misma suite contra MySQL (crea antes la base incident_panel_test):
DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_DATABASE=incident_panel_test \
DB_USERNAME=incident_user DB_PASSWORD=incident_password php artisan test
```

## 8. Estructura

```
src/IncidentManagement/
  Domain/Incident/      Agregado, estados, prioridades, acciones de log, puerto IncidentRepository
  Application/          Command/ (crear, cambiar estado, comentar) · Query/ (puerto de lectura) · Diagnostics/
  Infrastructure/       Persistence/{Eloquent, Sql, Migrations, Seeders}
  UI/                   Http/ (controlador, requests, presenter, rutas) · Console/ · Labels/
src/Shared/             Reloj, UUID, transacciones
resources/js/           Pages/ · features/incidents/{api,components,hooks,lib} · shared/ · layouts/
tests/                  Unit/ · Feature/ · Architecture/ · Support/
ai-process/             Uso de IA
```
