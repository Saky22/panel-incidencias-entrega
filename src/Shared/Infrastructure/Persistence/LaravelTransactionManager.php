<?php

namespace OptimaRetail\Shared\Infrastructure\Persistence;

use Illuminate\Database\ConnectionInterface;
use OptimaRetail\Shared\Application\Transaction\TransactionManager;
use Throwable;

final class LaravelTransactionManager implements TransactionManager
{
    private const int DEADLOCK_ATTEMPTS = 3;

    public function __construct(private readonly ConnectionInterface $db) {}

    /**
     * @throws Throwable
     */
    public function run(callable $callback): mixed
    {
        return $this->db->transaction($callback, self::DEADLOCK_ATTEMPTS);
    }
}
