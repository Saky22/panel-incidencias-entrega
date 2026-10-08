<?php

namespace OptimaRetail\IncidentManagement\UI\Http\Controller;

use Inertia\Inertia;
use Inertia\Response;
use OptimaRetail\IncidentManagement\Application\Query\IncidentReadModel;
use OptimaRetail\IncidentManagement\Application\Query\OperatorReadModel;
use OptimaRetail\IncidentManagement\UI\Http\Docs\LocalDocs;
use OptimaRetail\IncidentManagement\UI\Http\Presenter\IncidentPresenter;

/** Página de ayuda: README y arquitectura del repositorio, legibles desde la app. */
final class HelpController
{
    public function __construct(
        private readonly IncidentReadModel $read,
        private readonly OperatorReadModel $operators,
        private readonly IncidentPresenter $presenter,
    ) {}

    public function index(LocalDocs $docs): Response
    {
        return Inertia::render('Help/Index', [
            'docs' => $docs->all(),
            // La cabecera también lleva selector de operador en esta página.
            'authors' => fn () => $this->presenter->authors($this->operators->activeNames(), $this->read->knownAuthors()),
        ]);
    }
}
