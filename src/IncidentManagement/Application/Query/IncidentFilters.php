<?php

namespace OptimaRetail\IncidentManagement\Application\Query;

use OptimaRetail\IncidentManagement\Domain\Incident\IncidentStatus;
use OptimaRetail\IncidentManagement\Domain\Incident\Priority;

/** Criterios de búsqueda normalizados (valores desconocidos se ignoran). */
final readonly class IncidentFilters
{
    public const int SEARCH_MAX_LENGTH = 100;

    public function __construct(
        public ?IncidentStatus $status = null,
        public ?Priority $priority = null,
        public ?string $search = null,
    ) {}

    public static function fromPrimitives(?string $status, ?string $priority, ?string $search): self
    {
        $search = trim((string) $search);

        return new self(
            status: IncidentStatus::tryFrom((string) $status),
            priority: Priority::tryFrom((string) $priority),
            search: $search !== '' ? mb_substr($search, 0, self::SEARCH_MAX_LENGTH) : null,
        );
    }

    public function withoutStatus(): self
    {
        return new self(null, $this->priority, $this->search);
    }

    /** @return array{status: ?string, priority: ?string, search: ?string} */
    public function toPrimitives(): array
    {
        return [
            'status' => $this->status?->value,
            'priority' => $this->priority?->value,
            'search' => $this->search,
        ];
    }
}
