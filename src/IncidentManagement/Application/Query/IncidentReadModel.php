<?php

namespace OptimaRetail\IncidentManagement\Application\Query;

/**
 * Puerto de lectura (lado "query" de CQRS): vistas planas listas para pantalla,
 * con valores de dominio y datos derivados de sus reglas (SLA, transiciones),
 * sin textos de UI. La UI lo consume directamente: no hay lógica que orquestar.
 *
 * @phpstan-type IncidentView array{
 *     id: string, title: string, description: string, status: string, priority: string,
 *     requester_name: string, assigned_to: ?string, comments_count: int,
 *     created_at: \DateTimeImmutable, updated_at: \DateTimeImmutable, due_at: \DateTimeImmutable,
 *     is_overdue: bool, transitions: list<array{status: string, requires_reason: bool}>,
 *     review_flags: list<array{type: string, created_at: \DateTimeImmutable}>
 * }
 */
interface IncidentReadModel
{
    /** Tope de sugerencias del selector de autor (siempre admite escribir uno nuevo). */
    public const int KNOWN_AUTHORS_LIMIT = 200;

    /** @return array{items: list<IncidentView>, page: int, last_page: int, per_page: int, total: int} */
    public function search(IncidentFilters $filters, int $page, int $perPage = 20): array;

    /** @return array<string, int> recuento por valor de estado */
    public function countByStatus(IncidentFilters $filters): array;

    /** @return array{incident: IncidentView, timeline: list<array>}|null null si no existe */
    public function history(string $incidentId): ?array;

    /**
     * Nombres de persona ya usados (operadores de logs, autores de comentarios,
     * solicitantes y asignados): alimenta el selector de autor. Sin autenticación
     * en el alcance, es la forma de mantener los nombres consistentes.
     *
     * @return list<string> sin vacíos; puede repetir nombres (la UI deduplica)
     */
    public function knownAuthors(int $limit = self::KNOWN_AUTHORS_LIMIT): array;
}
