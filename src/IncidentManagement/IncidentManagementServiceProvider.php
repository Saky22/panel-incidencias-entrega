<?php

namespace OptimaRetail\IncidentManagement;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use OptimaRetail\IncidentManagement\Application\Diagnostics\InconsistencyDetector;
use OptimaRetail\IncidentManagement\Application\Diagnostics\InconsistencyRepairer;
use OptimaRetail\IncidentManagement\Application\Query\IncidentReadModel;
use OptimaRetail\IncidentManagement\Application\Query\OperatorReadModel;
use OptimaRetail\IncidentManagement\Domain\Incident\IncidentRepository;
use OptimaRetail\IncidentManagement\Domain\Operator\OperatorRepository;
use OptimaRetail\IncidentManagement\Infrastructure\Persistence\Eloquent\EloquentIncidentReadModel;
use OptimaRetail\IncidentManagement\Infrastructure\Persistence\Eloquent\EloquentIncidentRepository;
use OptimaRetail\IncidentManagement\Infrastructure\Persistence\Eloquent\EloquentOperatorReadModel;
use OptimaRetail\IncidentManagement\Infrastructure\Persistence\Eloquent\EloquentOperatorRepository;
use OptimaRetail\IncidentManagement\Infrastructure\Persistence\Sql\SqlInconsistencyDetector;
use OptimaRetail\IncidentManagement\Infrastructure\Persistence\Sql\SqlInconsistencyRepairer;
use OptimaRetail\IncidentManagement\UI\Console\DiagnoseIncidentsCommand;
use OptimaRetail\IncidentManagement\UI\Http\DomainErrorRenderer;

/**
 * Composition root del bounded context IncidentManagement (fuera del hexágono:
 * es el único punto que conoce todas las capas).
 * puertos → adaptadores, rutas, migraciones, comandos y traducción de errores.
 */
final class IncidentManagementServiceProvider extends ServiceProvider
{
    public array $bindings = [
        IncidentRepository::class => EloquentIncidentRepository::class,
        IncidentReadModel::class => EloquentIncidentReadModel::class,
        OperatorRepository::class => EloquentOperatorRepository::class,
        OperatorReadModel::class => EloquentOperatorReadModel::class,
        InconsistencyDetector::class => SqlInconsistencyDetector::class,
    ];

    public function register(): void
    {
        $this->app->bind(InconsistencyRepairer::class, fn ($app) => new SqlInconsistencyRepairer(
            $app['db']->connection(),
            Storage::disk('local'),
        ));
    }

    /**
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $base = __DIR__;

        $this->loadMigrationsFrom($base.'/Infrastructure/Persistence/Migrations');

        Route::middleware('web')->group($base.'/UI/Http/routes.php');

        if ($this->app->runningInConsole()) {
            $this->commands([DiagnoseIncidentsCommand::class]);
        }

        DomainErrorRenderer::register($this->app->make(ExceptionHandler::class));
    }
}
