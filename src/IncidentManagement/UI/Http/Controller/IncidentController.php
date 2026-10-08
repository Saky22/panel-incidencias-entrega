<?php

namespace OptimaRetail\IncidentManagement\UI\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use OptimaRetail\IncidentManagement\Application\Command\AddIncidentComment\AddIncidentCommentHandler;
use OptimaRetail\IncidentManagement\Application\Command\ChangeIncidentStatus\ChangeIncidentStatusHandler;
use OptimaRetail\IncidentManagement\Application\Command\CreateIncident\CreateIncidentHandler;
use OptimaRetail\IncidentManagement\Application\Query\IncidentFilters;
use OptimaRetail\IncidentManagement\Application\Query\IncidentReadModel;
use OptimaRetail\IncidentManagement\Application\Query\OperatorReadModel;
use OptimaRetail\IncidentManagement\UI\Http\Presenter\IncidentPresenter;
use OptimaRetail\IncidentManagement\UI\Http\Request\ChangeIncidentStatusRequest;
use OptimaRetail\IncidentManagement\UI\Http\Request\StoreCommentRequest;
use OptimaRetail\IncidentManagement\UI\Http\Request\StoreIncidentRequest;
use OptimaRetail\IncidentManagement\UI\Labels\IncidentLabels;

/**
 * Adaptador primario HTTP. Escrituras: Request → Command → Handler.
 * Lecturas: directamente al puerto IncidentReadModel (no hay lógica que orquestar).
 */
final class IncidentController
{
    public function __construct(
        private readonly IncidentReadModel $read,
        private readonly OperatorReadModel $operators,
        private readonly IncidentPresenter $presenter,
    ) {}

    public function index(Request $request): Response
    {
        $filters = IncidentFilters::fromPrimitives($request->query('status'), $request->query('priority'), $request->query('search'));

        return Inertia::render('Incidents/Index', [
            'incidents' => fn () => $this->presenter->list($this->read->search($filters, (int) $request->query('page', 1))),
            // Los contadores sirven de filtro rápido: ignoran el estado seleccionado.
            'counts' => fn () => $this->read->countByStatus($filters->withoutStatus()),
            'filters' => $filters->toPrimitives(),
            'options' => $this->presenter->options(),
            // Selector de autor: operadores activos primero, luego nombres históricos.
            'authors' => fn () => $this->presenter->authors($this->operators->activeNames(), $this->read->knownAuthors()),
        ]);
    }

    public function store(StoreIncidentRequest $request, CreateIncidentHandler $handler): RedirectResponse
    {
        $handler->handle($request->toCommand());

        return back()->with('success', "Incidencia «{$request->validated('title')}» creada.");
    }

    public function changeStatus(string $incident, ChangeIncidentStatusRequest $request, ChangeIncidentStatusHandler $handler): RedirectResponse
    {
        $status = $handler->handle($request->toCommand($incident));

        return back()->with('success', 'Estado cambiado a «'.IncidentLabels::status($status).'».');
    }

    public function storeComment(string $incident, StoreCommentRequest $request, AddIncidentCommentHandler $handler): RedirectResponse
    {
        $handler->handle($request->toCommand($incident));

        return back()->with('success', 'Comentario añadido.');
    }

    /** Historial bajo demanda (JSON) para el panel lateral. */
    public function history(string $incident): JsonResponse
    {
        $history = $this->read->history($incident);
        abort_if($history === null, 404, 'La incidencia no existe.');

        return response()->json($this->presenter->history($history));
    }
}
