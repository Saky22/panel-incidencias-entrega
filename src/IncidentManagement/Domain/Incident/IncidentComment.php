<?php

namespace OptimaRetail\IncidentManagement\Domain\Incident;

use OptimaRetail\IncidentManagement\Domain\Incident\Exception\InvalidIncidentData;

/** Comentario operativo; entidad interna del agregado Incident. */
final readonly class IncidentComment
{
    public string $authorName;

    public string $body;

    public function __construct(
        public IncidentId $incidentId,
        string $authorName,
        string $body,
        public \DateTimeImmutable $createdAt,
        public ?string $id = null,
    ) {
        $this->authorName = trim($authorName);
        $this->body = trim($body);

        if ($this->authorName === '') {
            throw InvalidIncidentData::required('author_name');
        }
        if ($this->body === '') {
            throw InvalidIncidentData::required('body');
        }
    }
}
