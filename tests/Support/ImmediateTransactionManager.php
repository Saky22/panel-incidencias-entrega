<?php

namespace Tests\Support;

use OptimaRetail\Shared\Application\Transaction\TransactionManager;

/** Ejecuta sin transacción real y cuenta las unidades de trabajo abiertas. */
final class ImmediateTransactionManager implements TransactionManager
{
    public int $runs = 0;

    public function run(callable $callback): mixed
    {
        $this->runs++;

        return $callback();
    }
}
