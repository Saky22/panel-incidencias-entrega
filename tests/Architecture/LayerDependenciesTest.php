<?php

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Hace cumplir la regla de dependencias de la arquitectura hexagonal:
 *
 *   UI ──► Application ──► Domain ◄── Infrastructure
 *
 *  - Domain:         solo PHP y Shared\Domain. Ni framework ni capas externas.
 *  - Application:    Domain + Shared\{Domain,Application}. Ni framework ni adaptadores.
 *  - Infrastructure: puede usar framework, Domain y Application; nunca UI.
 *  - UI:             puede usar framework y Application/Domain; nunca Infrastructure (va por puertos).
 *  - Contextos:      un bounded context no importa a otro (solo Shared).
 *
 * El composition root (src/<Contexto>/<Contexto>ServiceProvider.php) es la única excepción.
 */
final class LayerDependenciesTest extends TestCase
{
    private const SRC = __DIR__.'/../../src';

    private const FRAMEWORK = ['Illuminate\\', 'Inertia\\', 'Laravel\\', 'Carbon\\', 'App\\', 'Database\\'];

    /** @return array<string, array{string, list<string>}> capa => prefijos de namespace prohibidos */
    public static function rules(): array
    {
        $ctx = 'OptimaRetail\\IncidentManagement\\';

        return [
            'Shared\\Domain' => ['Shared/Domain', [...self::FRAMEWORK, 'OptimaRetail\\Shared\\Application', 'OptimaRetail\\Shared\\Infrastructure', 'OptimaRetail\\IncidentManagement']],
            'Shared\\Application' => ['Shared/Application', [...self::FRAMEWORK, 'OptimaRetail\\Shared\\Infrastructure', 'OptimaRetail\\IncidentManagement']],
            'Domain' => ['IncidentManagement/Domain', [...self::FRAMEWORK, $ctx.'Application', $ctx.'Infrastructure', $ctx.'UI', 'OptimaRetail\\Shared\\Application', 'OptimaRetail\\Shared\\Infrastructure']],
            'Application' => ['IncidentManagement/Application', [...self::FRAMEWORK, $ctx.'Infrastructure', $ctx.'UI', 'OptimaRetail\\Shared\\Infrastructure']],
            'Infrastructure' => ['IncidentManagement/Infrastructure', [$ctx.'UI', 'App\\']],
            'UI' => ['IncidentManagement/UI', [$ctx.'Infrastructure', 'OptimaRetail\\Shared\\Infrastructure', 'App\\']],
        ];
    }

    #[DataProvider('rules')]
    public function test_layer_does_not_depend_on_forbidden_namespaces(string $dir, array $forbidden): void
    {
        $violations = [];
        foreach ($this->phpFiles(self::SRC.'/'.$dir) as $file) {
            foreach ($this->imports($file) as $import) {
                foreach ($forbidden as $prefix) {
                    if (str_starts_with($import, $prefix)) {
                        $violations[] = sprintf('%s → %s', $this->relative($file), $import);
                    }
                }
            }
        }

        $this->assertSame([], $violations, "Dependencias prohibidas en {$dir}:\n".implode("\n", $violations));
    }

    public function test_domain_and_application_classes_do_not_use_global_framework_helpers(): void
    {
        $helpers = '/\b(app|config|now|request|response|abort|event|dispatch|resolve|logger|cache|session|auth)\s*\(/';
        $violations = [];
        foreach (['IncidentManagement/Domain', 'IncidentManagement/Application', 'Shared/Domain', 'Shared/Application'] as $dir) {
            foreach ($this->phpFiles(self::SRC.'/'.$dir) as $file) {
                $code = preg_replace('#/\*.*?\*/|//[^\n]*#s', '', file_get_contents($file)); // sin comentarios
                if (preg_match_all('/(?<![\w$>:\\\\])(?<!function )'.substr($helpers, 1, -1).'/', $code, $m)) {
                    $violations[] = $this->relative($file).': '.implode(', ', array_unique($m[0]));
                }
            }
        }

        $this->assertSame([], $violations, "Helpers globales del framework en capas internas:\n".implode("\n", $violations));
    }

    public function test_every_php_file_namespace_matches_its_path(): void
    {
        $mismatches = [];
        foreach ($this->phpFiles(self::SRC) as $file) {
            $relative = $this->relative($file);
            if (str_contains($relative, '/Migrations/') || str_ends_with($relative, 'routes.php')) {
                continue; // migraciones anónimas y ficheros de rutas no declaran clase
            }
            preg_match('/^namespace\s+([^;]+);/m', file_get_contents($file), $m);
            $expected = 'OptimaRetail\\'.str_replace('/', '\\', dirname($relative));
            if (($m[1] ?? null) !== $expected) {
                $mismatches[] = "{$relative}: ".($m[1] ?? 'sin namespace')." (esperado {$expected})";
            }
        }

        $this->assertSame([], $mismatches, implode("\n", $mismatches));
    }

    public function test_ports_are_interfaces_and_live_inside_the_hexagon(): void
    {
        $ports = [
            'IncidentManagement/Domain/Incident/IncidentRepository.php',
            'IncidentManagement/Domain/Operator/OperatorRepository.php',
            'IncidentManagement/Application/Query/IncidentReadModel.php',
            'IncidentManagement/Application/Query/OperatorReadModel.php',
            'IncidentManagement/Application/Diagnostics/InconsistencyDetector.php',
            'IncidentManagement/Application/Diagnostics/InconsistencyRepairer.php',
            'Shared/Application/Transaction/TransactionManager.php',
            'Shared/Domain/Clock.php',
        ];

        foreach ($ports as $port) {
            $this->assertMatchesRegularExpression('/^interface\s/m', file_get_contents(self::SRC.'/'.$port), "{$port} debe ser una interface");
        }
    }

    /** @return list<string> */
    private function imports(string $file): array
    {
        preg_match_all('/^use\s+([^;{\s]+)/m', file_get_contents($file), $m);
        preg_match_all('/new\s+\\\\?((?:[A-Z]\w*\\\\)+\w+)/', file_get_contents($file), $n);

        return array_map(fn ($i) => ltrim($i, '\\'), [...$m[1], ...$n[1]]);
    }

    /** @return iterable<string> */
    private function phpFiles(string $dir): iterable
    {
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->getExtension() === 'php') {
                yield $f->getPathname();
            }
        }
    }

    private function relative(string $file): string
    {
        return ltrim(str_replace(realpath(self::SRC), '', realpath($file)), '/');
    }
}
