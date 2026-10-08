<?php

namespace OptimaRetail\IncidentManagement\Infrastructure\Persistence\Seeders;

use Illuminate\Database\Seeder;
use OptimaRetail\IncidentManagement\Domain\Incident\Incident;
use OptimaRetail\IncidentManagement\Domain\Incident\IncidentRepository;
use OptimaRetail\IncidentManagement\Domain\Incident\IncidentStatus as S;
use OptimaRetail\IncidentManagement\Domain\Incident\Priority as P;
use OptimaRetail\Shared\Domain\Clock;

/** Datos coherentes creados a través del dominio (generan sus logs correctamente). */
final class IncidentSeeder extends Seeder
{
    public function run(IncidentRepository $repo, Clock $clock): void
    {
        $now = new \DateTimeImmutable;

        // [título, descripción, prioridad, solicitante, asignado, horas atrás, camino de estados [estado, motivo]]
        $data = [
            ['TPV de caja 3 no imprime tickets', 'La impresora térmica da error de papel aunque tiene rollo.', P::HIGH, 'Tienda Diagonal', 'Marta Soler', 30, [[S::UNDER_REVIEW, null]]],
            ['Lector de códigos no lee EAN-13', 'Varios productos de alimentación no se escanean en caja 1.', P::MEDIUM, 'Tienda Gràcia', 'Jordi Puig', 5, []],
            ['Caída del datáfono en horario punta', 'El datáfono pierde conexión cada 10 minutos.', P::CRITICAL, 'Tienda Sants', 'Marta Soler', 6, [[S::UNDER_REVIEW, null], [S::BLOCKED, 'Esperando técnico de la entidad bancaria']]],
            ['Precio incorrecto en etiqueta electrónica', 'La ESL del pasillo 4 muestra el precio antiguo.', P::LOW, 'Tienda Born', null, 2, []],
            ['Stock negativo en artículo 88412', 'El ERP muestra -3 unidades tras la regularización.', P::MEDIUM, 'Central Compras', 'Laura Vidal', 100, [[S::UNDER_REVIEW, null], [S::RESOLVED, null]]],
            ['Fallo de sincronización de pedidos web', 'Los pedidos click&collect no llegan a tienda.', P::CRITICAL, 'E-commerce', 'Jordi Puig', 1, [[S::UNDER_REVIEW, null]]],
            ['Cámara de frío marca 9 ºC', 'Alarma de temperatura en la cámara de lácteos.', P::HIGH, 'Tienda Poblenou', 'Laura Vidal', 50, [[S::UNDER_REVIEW, null], [S::RESOLVED, null], [S::OPEN, 'Vuelve a subir la temperatura por la noche']]],
            ['Cajón portamonedas no abre', 'Caja 2 requiere apertura manual con llave.', P::LOW, 'Tienda Gràcia', null, 200, []],
        ];

        foreach ($data as [$title, $desc, $priority, $requester, $assignee, $hoursAgo, $path]) {
            $at = $now->modify("-{$hoursAgo} hours");
            $incident = Incident::open($repo->nextIdentity(), $title, $desc, $priority, $requester, $assignee, $requester, $at);

            foreach ($path as $step => [$status, $reason]) {
                $at = $at->modify('+'.(20 + $step * 15).' minutes');
                $incident->changeStatus($status, $assignee ?? 'Operador', $reason, ['source' => 'seeder'], $at);
            }
            if ($path) {
                $incident->addComment($assignee ?? 'Operador', 'Revisado en remoto, se hace seguimiento.', $at->modify('+5 minutes'));
            }
            $repo->save($incident);
        }
    }
}
