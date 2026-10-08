<?php

namespace OptimaRetail\Shared\Application\Transaction;

/** Puerto de unidad de trabajo: los casos de uso delimitan la atomicidad sin conocer la BD. */
interface TransactionManager
{
    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function run(callable $callback): mixed;
}
