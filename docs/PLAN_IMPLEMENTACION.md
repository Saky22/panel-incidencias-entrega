# Plan de Implementación - Panel Operativo de Incidencias

## 📋 Resumen del Proyecto

**Aplicación**: Panel Operativo de Incidencias  
**Stack Técnico**: Laravel 12 + Inertia.js 3 + React 19 + MySQL 8 + Tailwind CSS 4  
**Duración Estimada**: 90 minutos  
**Perfil**: Desarrollador Mid/Senior Laravel  

**Objetivo Principal**: Demostrar competencias técnicas en:
- Modelado de datos con criterio
- Mantenimiento de trazabilidad
- Localización de inconsistencias
- Propuesta de correcciones seguras
- Implementación de patrones arquitectónicos avanzados

## 🏗️ Arquitectura Técnica

### Patrón Hexagonal/DDD (Domain-Driven Design)
```
┌─────────────────────────────────────────────────────────┐
│                    Capa de Presentación                  │
│  • Inertia.js (Puente Laravel-React)                    │
│  • Componentes React organizados por funcionalidad      │
│  • Comandos Artisan para diagnóstico                    │
└──────────────────────────┬──────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────┐
│                  Capa de Aplicación                      │
│  • Casos de Uso (Use Cases)                             │
│  • DTOs (Data Transfer Objects)                         │
│  • Servicios de Aplicación                              │
└──────────────────────────┬──────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────┐
│                   Capa de Dominio                        │
│  • Entidades (Incidente, Log, Comentario)               │
│  • Objetos de Valor (Estado, Prioridad)                 │
│  • Servicios de Dominio                                 │
│  • Interfaces de Repositorio                            │
└──────────────────────────┬──────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────┐
│               Capa de Infraestructura                    │
│  • Adaptadores Eloquent                                 │
│  • Modelos de Base de Datos                             │
│  • Migraciones MySQL                                    │
└─────────────────────────────────────────────────────────┘
```

## 🎯 Ejercicios a Implementar

### Ejercicio 1: Maquetación de la Pantalla Principal
- ✅ Cabecera con nombre y descripción funcional
- ✅ Listado de incidencias en tabla/tarjetas
- ✅ Indicadores visuales claros para estados
- ✅ Formulario para crear nueva incidencia
- ✅ Acción visible para cambiar estado
- ✅ Acceso al historial de cambios
- ✅ Diseño limpio, responsive y usable

### Ejercicio 2: Base de Datos y Modelo de Datos
- ✅ Tabla `incidents` con campos mínimos
- ✅ Tabla `incident_logs` para trazabilidad
- ✅ Tabla `incident_comments` para comentarios operativos
- ✅ Claves foráneas donde corresponda
- ✅ Índices razonables para consultas habituales
- ✅ Centralización de estados (evitar strings mágicos)
- ✅ Validación de transiciones de estado coherentes
- ✅ Guardado de cambios críticos en logs
- ✅ Uso de transacciones para operaciones atómicas

### Ejercicio 3: Programación Funcional Mínima
- ✅ Crear incidencia con validación de datos
- ✅ Listar incidencias con filtros (estado, prioridad, texto)
- ✅ Cambiar estado respetando transiciones permitidas
- ✅ Registrar en logs cada cambio de estado
- ✅ Mostrar historial de cambios y comentarios
- ✅ Añadir comentario operativo
- ✅ Gestión clara de errores de validación

### Ejercicio 4: JavaScript/React/Interacción
- ✅ Implementar al menos 2 funcionalidades de:
  - Filtro rápido por estado sin recargar página
  - Confirmación antes de resolver/bloquear incidencia
  - Contador visual de incidencias por estado
  - Resaltado de incidencias vencidas/bloqueadas
  - Componente React/Livewire para filtrar/actualizar/resaltar

### Ejercicio 5: Diagnóstico y Corrección de Inconsistencias
- ✅ Simular 3 inconsistencias:
  1. Incidencia resuelta sin log de resolución
  2. Estado de incidencia no coincide con su último log
  3. Log duplicado o incoherente
- ✅ Detectar inconsistencias mediante SQL/Eloquent
- ✅ Implementar diagnóstico claro (comando Artisan/clase servicio)
- ✅ Corregir datos de forma segura
- ✅ Incluir opción dry-run
- ✅ Documentar query y explicación de detección
- ✅ Demostrar prudencia en correcciones automáticas

## 📁 Estructura de Carpetas del Proyecto

```
incident-panel/
├── app/
│   ├── Domain/                    # Lógica de negocio pura
│   │   ├── Entities/              # Entidades de dominio
│   │   ├── ValueObjects/          # Objetos de valor (ENUMs)
│   │   ├── Services/              # Servicios de dominio
│   │   └── Interfaces/            # Interfaces de repositorio
│   ├── Application/               # Casos de uso y orquestación
│   │   ├── UseCases/              # Casos de uso
│   │   ├── DTOs/                  # Objetos de transferencia de datos
│   │   └── Services/              # Servicios de aplicación
│   ├── Infrastructure/            # Implementaciones concretas
│   │   └── Persistence/
│   │       ├── Eloquent/          # Adaptadores Eloquent
│   │       └── Models/            # Modelos de base de datos
│   └── Http/
│       └── Controllers/           # Controladores Inertia
├── resources/
│   └── js/
│       ├── Components/            # Componentes React organizados
│       │   ├── Incidents/         # Componentes específicos de incidencias
│       │   ├── Layout/            # Componentes de layout
│       │   └── Shared/            # Componentes compartidos
│       ├── Pages/                 # Páginas Inertia
│       └── hooks/                 # Custom hooks React
├── database/
│   ├── migrations/                # Migraciones de base de datos
│   └── seeders/                   # Seeders con datos de prueba
├── tests/                         # Pruebas unitarias y de características
├── ai-process/                    # Documentación de uso de IA
└── docs/                          # Documentación del proyecto
```

## 🔧 Stack Tecnológico Detallado

### Backend
- **PHP 8.4+** con Laravel 12
- **MySQL** como base de datos principal
- **Inertia.js** para puente entre Laravel y React
- **Eloquent ORM** con adaptadores DDD
- **Artisan Console** para comandos de diagnóstico

### Frontend
- **React 19** con hooks funcionales
- **Inertia.js** para routing y comunicación con backend
- **Tailwind CSS** para estilos responsivos
- **Componentes funcionales** organizados por feature
- **Custom hooks** para gestión de estado

### Patrones y Principios
- **DDD/Hexagonal Architecture** - Separación clara de capas
- **SOLID Principles** - Aplicados en todo el código
- **Repository Pattern** - Abstracción de acceso a datos
- **DTO Pattern** - Transferencia de datos entre capas
- **Use Case Pattern** - Aislamiento de lógica de negocio
- **Transaction Pattern** - Consistencia en operaciones críticas

## ⏱️ Planificación Temporal (90 minutos)

### Fase 1: Configuración Inicial (15 min)
- [ ] Crear proyecto Laravel con Inertia.js + React
- [ ] Configurar Tailwind CSS y Vite
- [ ] Configurar base de datos MySQL
- [ ] Estructura básica de carpetas DDD

### Fase 2: Capa de Dominio (20 min)
- [ ] Implementar entidades (Incidente, Log, Comentario)
- [ ] Crear ENUMs para Estado y Prioridad
- [ ] Implementar servicios de dominio
- [ ] Crear interfaces de repositorio
- [ ] Escribir pruebas unitarias para lógica de dominio

### Fase 3: Infraestructura (15 min)
- [ ] Crear migraciones de base de datos
- [ ] Implementar modelos Eloquent
- [ ] Crear adaptadores de repositorio
- [ ] Configurar inyección de dependencias

### Fase 4: Capa de Aplicación (15 min)
- [ ] Implementar DTOs para transferencia de datos
- [ ] Crear casos de uso principales
- [ ] Implementar servicios de aplicación
- [ ] Escribir pruebas unitarias para casos de uso

### Fase 5: Backend - Presentación (15 min)
- [ ] Crear controladores Inertia
- [ ] Implementar formularios de validación
- [ ] Crear comando Artisan para diagnóstico
- [ ] Configurar rutas y middlewares
- [ ] Escribir pruebas de características

### Fase 6: Frontend - React (15 min)
- [ ] Crear componentes React organizados
- [ ] Implementar filtros rápidos sin recarga
- [ ] Añadir diálogos de confirmación
- [ ] Crear contadores visuales de estado
- [ ] Implementar diseño responsivo

### Fase 7: Diagnóstico de Datos (10 min)
- [ ] Implementar detector de inconsistencias
- [ ] Crear lógica de corrección segura
- [ ] Implementar opción dry-run
- [ ] Crear seeder con datos inconsistentes

### Fase 8: Documentación (5 min)
- [ ] Crear README completo
- [ ] Documentar uso de IA
- [ ] Probar instalación y ejecución
- [ ] Preparar repositorio GitHub

## 🎨 Componentes Frontend a Desarrollar

### Layout Principal
- `AppLayout.jsx` - Layout base de la aplicación
- `Header.jsx` - Cabecera con nombre y descripción
- `Navigation.jsx` - Navegación principal

### Componentes de Incidencias
- `IncidentList.jsx` - Listado principal con filtros
- `IncidentCard.jsx` - Tarjeta individual de incidencia
- `IncidentForm.jsx` - Formulario de creación
- `StatusFilter.jsx` - Filtro rápido por estado (sin recarga)
- `StatusBadge.jsx` - Indicador visual de estado
- `IncidentHistory.jsx` - Historial de cambios y comentarios

### Componentes Compartidos
- `ConfirmationDialog.jsx` - Diálogo de confirmación (para cambios de estado)
- `StatusCounter.jsx` - Contador visual por estado
- `Button.jsx` - Botones reutilizables
- `Modal.jsx` - Modal genérico
- `Alert.jsx` - Alertas y notificaciones

### Hooks Personalizados
- `useIncidents.js` - Gestión de estado de incidencias
- `useFilters.js` - Gestión de filtros
- `useConfirmDialog.js` - Gestión de diálogos de confirmación

## 🗄️ Esquema de Base de Datos

### Tabla `incidents`
```sql
CREATE TABLE incidents (
    id UUID PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('open', 'under_review', 'blocked', 'resolved') NOT NULL,
    priority ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    requester_name VARCHAR(255) NOT NULL,
    assigned_to VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_status_created (status, created_at),
    INDEX idx_priority_created (priority, created_at),
    INDEX idx_assigned_status (assigned_to, status)
);
```

### Tabla `incident_logs`
```sql
CREATE TABLE incident_logs (
    id UUID PRIMARY KEY,
    incident_id UUID NOT NULL,
    action VARCHAR(100) NOT NULL,
    old_value VARCHAR(255) NULL,
    new_value VARCHAR(255) NOT NULL,
    user_name VARCHAR(255) NOT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (incident_id) REFERENCES incidents(id) ON DELETE RESTRICT,
    INDEX idx_incident_created (incident_id, created_at),
    INDEX idx_action_new_value (action, new_value)
);
```
> Implementado: `created_at` con precisión de microsegundos, `new_value`/`old_value` VARCHAR(32),
> `ON DELETE RESTRICT` (la auditoría no se borra en cascada). Ver `docs/ARQUITECTURA.md`.

### Tabla `incident_comments`
```sql
CREATE TABLE incident_comments (
    id UUID PRIMARY KEY,
    incident_id UUID NOT NULL,
    author_name VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (incident_id) REFERENCES incidents(id) ON DELETE CASCADE,
    INDEX idx_incident_created (incident_id, created_at)
);
```

## 🔍 Reglas de Negocio Clave

### Transiciones de Estado Permitidas
```
open → under_review
under_review → blocked        (motivo obligatorio)
under_review → resolved
blocked → under_review        (desbloqueo = "intervención manual", motivo obligatorio)
resolved → open               (reapertura, motivo obligatorio → queda en el log)
```
> Decisión 08/10/2026: el plan original dejaba `blocked` y `resolved` sin salida definida.
> Se concretan como transiciones explícitas con motivo. Fuente de verdad: `IncidentStatus`.

### Validaciones
1. **Creación de Incidencia**:
   - Título obligatorio (mínimo 5 caracteres)
   - Descripción obligatoria
   - Solicitante obligatorio
   - Prioridad válida

2. **Cambio de Estado**:
   - Validar transición permitida
   - Registrar log con usuario y timestamp
   - Usar transacción para garantizar consistencia

3. **Comentarios**:
   - Autor obligatorio
   - Cuerpo no vacío
   - Relación con incidencia existente

### Trazabilidad
- Todo cambio de estado genera log automático
- Logs incluyen: valor anterior, valor nuevo, usuario, IP, timestamp
- Los logs son inmutables (solo lectura)
- Los comentarios son editables por el autor

## 🧪 Estrategia de Testing

### Pruebas Unitarias (Domain Layer)
- ✅ Entidades: Validar reglas de negocio
- ✅ Objetos de Valor: Validar transiciones de estado
- ✅ Servicios de Dominio: Validar lógica de negocio

### Pruebas Unitarias (Application Layer)
- ✅ Casos de Uso: Validar orquestación
- ✅ DTOs: Validar transformación de datos

### Pruebas de Características
- ✅ Controladores: Validar endpoints HTTP
- ✅ Comandos: Validar funcionalidad CLI
- ✅ Integración: Validar flujos completos

### Pruebas Frontend
- ✅ Componentes: Validar renderizado y comportamiento
- ✅ Hooks: Validar gestión de estado
- ✅ Integración: Validar comunicación con backend

## 🛡️ Consideraciones de Seguridad y Calidad

### Seguridad de Datos
- ✅ Validación en todas las capas (frontend, backend, base de datos)
- ✅ Transacciones para operaciones atómicas
- ✅ Logs inmutables para auditoría
- ✅ Dry-run por defecto en correcciones

### Calidad de Código
- ✅ Aplicación de principios SOLID
- ✅ Separación clara de responsabilidades
- ✅ Cobertura de pruebas en lógica crítica
- ✅ Documentación clara y completa

### Mantenibilidad
- ✅ Arquitectura extensible (DDD/Hexagonal)
- ✅ Código auto-documentado
- ✅ Organización por feature/dominio
- ✅ Configuración externalizada

## 📊 Métricas de Éxito

### Funcionales
- ✅ Todas las funcionalidades requeridas implementadas
- ✅ Interfaz usable en desktop y móvil
- ✅ Filtros y búsquedas funcionando
- ✅ Diagnóstico de inconsistencias operativo

### Técnicas
- ✅ Arquitectura DDD/Hexagonal implementada correctamente
- ✅ Código siguiendo principios SOLID
- ✅ Pruebas unitarias para lógica crítica
- ✅ Documentación completa y clara

### Temporales
- ✅ Implementación completa en ≤ 90 minutos
- ✅ Tiempos por fase dentro de lo estimado
- ✅ Buffer de tiempo utilizado eficientemente

## 🚀 Entregables Finales

### Repositorio GitHub
- ✅ Código fuente completo
- ✅ Migraciones y seeders
- ✅ README con instrucciones de instalación
- ✅ .env.example sin credenciales reales

### Documentación
- ✅ Explicación de inconsistencias simuladas
- ✅ Documentación de diagnóstico/corrección
- ✅ Documentación de uso de IA (si aplica)
- ✅ Decisiones técnicas documentadas

### Aplicación Funcional
- ✅ Panel de incidencias operativo
- ✅ Funcionalidades de filtrado y búsqueda
- ✅ Sistema de trazabilidad completo
- ✅ Herramienta de diagnóstico de datos

---

## 📝 Notas de Implementación

### Prioridades
1. **Funcionalidad Core** - Ejercicios 1-3 completos
2. **Arquitectura** - Patrones DDD/Hexagonal bien implementados
3. **Frontend** - 2+ interacciones mejoradas implementadas
4. **Diagnóstico** - Sistema de detección de inconsistencias
5. **Calidad** - Pruebas y documentación

### Riesgos y Mitigación
- **Riesgo**: Tiempo insuficiente
  - **Mitigación**: Seguir planificación estricta, priorizar funcionalidad core
- **Riesgo**: Complejidad arquitectural
  - **Mitigación**: Implementar DDD de forma pragmática, simplificar donde sea necesario
- **Riesgo**: Problemas con Inertia.js + React
  - **Mitigación**: Usar configuración estándar, seguir documentación oficial

### Criterios de Aceptación
- Proyecto se ejecuta siguiendo README sin adivinanzas
- Todas las funcionalidades requeridas funcionan
- Código demuestra criterio técnico mid/senior
- Arquitectura permite mantenimiento y extensión
- Sistema de trazabilidad y diagnóstico operativo

---

## 🔀 Desviaciones respecto al plan (08/10/2026)

| Plan | Implementado | Motivo |
|---|---|---|
| Laravel 10 / React 18 | Laravel 12 / React 19 / Inertia 3 / Tailwind 4 | Versiones ya instaladas en el proyecto |
| `blocked`/`resolved` sin salida | Desbloquear y reabrir con motivo | Evitar incidencias atascadas |
| Logs `ON DELETE CASCADE` | `RESTRICT` + logs inmutables en Eloquent | Integridad de la auditoría |
| `findInconsistencies()` en el repositorio | Puertos `InconsistencyDetector` / `InconsistencyRepairer` | Separar escritura, lectura y diagnóstico |
| `Application/Services` | Puertos `Application/Contracts`, `Queries`, `Diagnostics` | No hacían falta servicios de aplicación genéricos |
| `Components/Shared/StatusCounter`, `StatusFilter` | `Components/Incidents/StatusCounter` (contador = filtro rápido) | Una pieza cubre dos requisitos del Ejercicio 4 |
| `useIncidents.js` | `useIncidentHistory.js` + `useOperator.js` | El listado lo gestiona Inertia (visitas parciales) |
| — | Comando `incidents:diagnose` con `--fix`, `--accept-current-status`, `--json` y marcas de revisión manual | Prudencia graduada (ver README §5) |
| Reapertura como cambio de estado | Acciones de log `reopened` / `unblocked` | El enunciado pide «log explícito de reapertura» |
| Docker como entorno principal | Solo MySQL local; Docker eliminado | El enunciado excluye Docker del alcance y no se había verificado |

| `app/{Domain,Application,Infrastructure}` | `src/{Shared,IncidentManagement}/{Domain,Application,Infrastructure,UI}` | Bounded contexts autocontenidos y separados del shell de Laravel (2ª iteración) |
| `UseCases/` + `DTOs/` | `Command/<Caso>/` con Handler; lecturas por puerto `IncidentReadModel` | CQRS ligero sin piezas de paso |
| — | `tests/Architecture` | Las reglas de dependencia se verifican en cada `php artisan test` |

Detalle completo de la revisión de arquitectura: [`ARQUITECTURA.md`](ARQUITECTURA.md). La estructura de carpetas de arriba es la del plan original; la vigente está en `ARQUITECTURA.md` §3.

*Este documento será actualizado durante la implementación para reflejar el progreso real y cualquier desviación del plan.*
