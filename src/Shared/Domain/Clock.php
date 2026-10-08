<?php

namespace OptimaRetail\Shared\Domain;

/** Puerto de tiempo: el dominio nunca llama a `new DateTimeImmutable()` por su cuenta. */
interface Clock
{
    public function now(): \DateTimeImmutable;
}
