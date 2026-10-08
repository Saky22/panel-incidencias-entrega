<?php

namespace OptimaRetail\Shared;

use Illuminate\Support\ServiceProvider;
use OptimaRetail\Shared\Application\Transaction\TransactionManager;
use OptimaRetail\Shared\Domain\Clock;
use OptimaRetail\Shared\Infrastructure\Persistence\LaravelTransactionManager;
use OptimaRetail\Shared\Infrastructure\Time\SystemClock;

/** Composition root del Shared Kernel (puertos transversales → adaptadores). */
final class SharedServiceProvider extends ServiceProvider
{
    public array $singletons = [
        Clock::class => SystemClock::class,
        TransactionManager::class => LaravelTransactionManager::class,
    ];
}
