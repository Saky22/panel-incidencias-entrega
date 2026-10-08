<?php

namespace OptimaRetail\IncidentManagement\UI\Http\Controller;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use OptimaRetail\IncidentManagement\Application\Command\DeactivateOperator\DeactivateOperator;
use OptimaRetail\IncidentManagement\Application\Command\DeactivateOperator\DeactivateOperatorHandler;
use OptimaRetail\IncidentManagement\Application\Command\RegisterOperator\RegisterOperatorHandler;
use OptimaRetail\IncidentManagement\Application\Query\OperatorReadModel;
use OptimaRetail\IncidentManagement\UI\Http\Presenter\IncidentPresenter;
use OptimaRetail\IncidentManagement\UI\Http\Request\StoreOperatorRequest;

/** Adaptador primario HTTP del catálogo de operadores (altas y bajas). */
final class OperatorController
{
    public function __construct(
        private readonly OperatorReadModel $operators,
        private readonly IncidentPresenter $presenter,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Operators/Index', [
            'operators' => fn () => $this->presenter->operators($this->operators->list()),
            // La cabecera también lleva selector de operador en esta página.
            'authors' => fn () => $this->presenter->authors($this->operators->activeNames(), []),
        ]);
    }

    public function store(StoreOperatorRequest $request, RegisterOperatorHandler $handler): RedirectResponse
    {
        $handler->handle($request->toCommand());

        return back()->with('success', 'Operador «'.trim($request->validated('name')).'» dado de alta.');
    }

    public function deactivate(string $operator, DeactivateOperatorHandler $handler): RedirectResponse
    {
        $handler->handle(new DeactivateOperator($operator));

        return back()->with('success', 'Operador dado de baja.');
    }
}
