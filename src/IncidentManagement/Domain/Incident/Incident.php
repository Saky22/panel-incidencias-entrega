<?php

namespace OptimaRetail\IncidentManagement\Domain\Incident;

use OptimaRetail\IncidentManagement\Domain\Incident\Exception\InvalidIncidentData;
use OptimaRetail\IncidentManagement\Domain\Incident\Exception\InvalidStatusTransition;
use OptimaRetail\IncidentManagement\Domain\Incident\Exception\StatusChangeReasonRequired;

/**
 * Raíz de agregado. Toda mutación de estado pasa por aquí y genera su asiento
 * de log en el mismo movimiento: estado y trazabilidad no pueden divergir
 * mientras la persistencia guarde ambos en una transacción.
 *
 * PHP puro: el tiempo llega siempre como argumento (puerto Clock en la capa de aplicación).
 */
final class Incident
{
    public const int TITLE_MIN_LENGTH = 5;

    /** @var list<IncidentLog> */
    private array $pendingLogs = [];

    /** @var list<IncidentComment> */
    private array $pendingComments = [];

    private function __construct(
        private readonly IncidentId $id,
        private readonly string $title,
        private readonly string $description,
        private IncidentStatus $status,
        private readonly Priority $priority,
        private readonly string $requesterName,
        private readonly ?string $assignedTo,
        private readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {}

    /** Alta: siempre nace «open» y con su asiento de creación. */
    public static function open(
        IncidentId $id,
        string $title,
        string $description,
        Priority $priority,
        string $requesterName,
        ?string $assignedTo,
        string $actor,
        \DateTimeImmutable $at,
    ): self {
        $title = trim($title);
        $description = trim($description);
        $requesterName = trim($requesterName);
        $assignedTo = $assignedTo !== null && trim($assignedTo) !== '' ? trim($assignedTo) : null;

        if (mb_strlen($title) < self::TITLE_MIN_LENGTH) {
            throw InvalidIncidentData::tooShort('title', self::TITLE_MIN_LENGTH);
        }
        if ($description === '') {
            throw InvalidIncidentData::required('description');
        }
        if ($requesterName === '') {
            throw InvalidIncidentData::required('requester_name');
        }

        $incident = new self($id, $title, $description, IncidentStatus::OPEN, $priority, $requesterName, $assignedTo, $at, $at);
        $incident->record(LogAction::CREATED, null, IncidentStatus::OPEN, $actor, [], $at);

        return $incident;
    }

    /** Rehidratación desde persistencia: no valida ni genera asientos. */
    public static function reconstitute(
        IncidentId $id,
        string $title,
        string $description,
        IncidentStatus $status,
        Priority $priority,
        string $requesterName,
        ?string $assignedTo,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self($id, $title, $description, $status, $priority, $requesterName, $assignedTo, $createdAt, $updatedAt);
    }

    /** @param array<string, scalar|null> $context datos de auditoría (ip, origen...) */
    public function changeStatus(
        IncidentStatus $target,
        string $actor,
        ?string $reason,
        array $context,
        \DateTimeImmutable $at,
    ): void {
        $reason = $reason !== null ? trim($reason) : null;

        if (! $this->status->canTransitionTo($target)) {
            throw new InvalidStatusTransition($this->status, $target);
        }
        if ($this->status->requiresReasonFor($target) && ($reason === null || $reason === '')) {
            throw new StatusChangeReasonRequired($this->status, $target);
        }

        $from = $this->status;
        $this->status = $target;
        $this->updatedAt = $at;

        $metadata = array_filter([...$context, 'reason' => $reason], fn ($v) => $v !== null && $v !== '');
        // Reabrir y desbloquear quedan con su propia acción: trazabilidad explícita.
        $this->record(LogAction::forTransition($from, $target), $from, $target, $actor, $metadata, $at);
    }

    public function addComment(string $authorName, string $body, \DateTimeImmutable $at): IncidentComment
    {
        $comment = new IncidentComment($this->id, $authorName, $body, $at);
        $this->pendingComments[] = $comment;
        $this->updatedAt = $at;

        return $comment;
    }

    public function dueAt(): \DateTimeImmutable
    {
        return $this->priority->dueFrom($this->createdAt);
    }

    /** Vencida = no resuelta y fuera del SLA de su prioridad. */
    public function isOverdue(\DateTimeImmutable $now): bool
    {
        return ! $this->status->isFinal() && $this->dueAt() < $now;
    }

    /** @return list<IncidentLog> */
    public function pullPendingLogs(): array
    {
        [$logs, $this->pendingLogs] = [$this->pendingLogs, []];

        return $logs;
    }

    /** @return list<IncidentComment> */
    public function pullPendingComments(): array
    {
        [$comments, $this->pendingComments] = [$this->pendingComments, []];

        return $comments;
    }

    private function record(LogAction $action, ?IncidentStatus $old, IncidentStatus $new, string $actor, array $metadata, \DateTimeImmutable $at): void
    {
        $actor = trim($actor);
        if ($actor === '') {
            throw InvalidIncidentData::required('user_name');
        }
        $this->pendingLogs[] = new IncidentLog($this->id, $action, $old, $new, $actor, $metadata, $at);
    }

    public function id(): IncidentId
    {
        return $this->id;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function status(): IncidentStatus
    {
        return $this->status;
    }

    public function priority(): Priority
    {
        return $this->priority;
    }

    public function requesterName(): string
    {
        return $this->requesterName;
    }

    public function assignedTo(): ?string
    {
        return $this->assignedTo;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
