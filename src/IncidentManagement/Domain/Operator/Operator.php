<?php

namespace OptimaRetail\IncidentManagement\Domain\Operator;

use OptimaRetail\IncidentManagement\Domain\Operator\Exception\InvalidOperatorData;

/**
 * Trabajador interno que puede figurar como autor en logs y comentarios.
 * Alta flexible: los textos libres históricos siguen valiendo; el selector
 * sugiere primero los operadores activos. Baja lógica para no romper la trazabilidad.
 *
 * PHP puro: el tiempo llega siempre como argumento (puerto Clock en la capa de aplicación).
 */
final class Operator
{
    public const int NAME_MIN_LENGTH = 2;

    private function __construct(
        private readonly OperatorId $id,
        private string $name,
        private bool $active,
        private readonly \DateTimeImmutable $createdAt,
    ) {}

    public static function register(OperatorId $id, string $name, \DateTimeImmutable $at): self
    {
        $name = trim($name);
        if ($name === '') {
            throw InvalidOperatorData::required('name');
        }
        if (mb_strlen($name) < self::NAME_MIN_LENGTH) {
            throw InvalidOperatorData::tooShort('name', self::NAME_MIN_LENGTH);
        }

        return new self($id, $name, true, $at);
    }

    /** Rehidratación desde persistencia: no valida. */
    public static function reconstitute(OperatorId $id, string $name, bool $active, \DateTimeImmutable $createdAt): self
    {
        return new self($id, $name, $active, $createdAt);
    }

    public function deactivate(): void
    {
        $this->active = false;
    }

    public function reactivate(): void
    {
        $this->active = true;
    }

    public function id(): OperatorId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
