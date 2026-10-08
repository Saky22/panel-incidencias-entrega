<?php

namespace OptimaRetail\IncidentManagement\UI\Http\Docs;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Exception\CommonMarkException;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Documentación del repositorio renderizada para la página de ayuda.
 * Lee los .md del proyecto y los convierte a HTML (tablas incluidas,
 * HTML crudo eliminado). Sin dominio implicado: es un adaptador de lectura.
 */
final class LocalDocs
{
    private MarkdownConverter $converter;

    /** @var array<string, string> slug => ruta relativa a la raíz del proyecto */
    private const array DOCUMENTS = [
        'readme' => 'README.md',
        'arquitectura' => 'docs/ARQUITECTURA.md',
    ];

    public function __construct()
    {
        // Defensa en profundidad: sin HTML crudo ni enlaces javascript:/data:.
        $environment = new Environment(['html_input' => 'strip', 'allow_unsafe_links' => false]);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new TableExtension);

        $this->converter = new MarkdownConverter($environment);
    }

    /** @return array<string, string> slug => HTML
     * @throws CommonMarkException
     */
    public function all(): array
    {
        $docs = [];
        foreach (self::DOCUMENTS as $slug => $relative) {
            $docs[$slug] = $this->render(base_path($relative));
        }

        return $docs;
    }

    /**
     * @throws CommonMarkException
     */
    private function render(string $path): string
    {
        if (! is_file($path)) {
            return '<p>Documento no disponible.</p>';
        }

        $html = (string) $this->converter->convert(file_get_contents($path));

        // Los .md enlazan entre sí con rutas del repositorio (docs/ARQUITECTURA.md), que dentro
        // de /ayuda darían 404: se reescriben a la pestaña correspondiente (#slug).
        foreach (self::DOCUMENTS as $slug => $relative) {
            $html = str_replace(['href="'.$relative.'"', 'href="../'.$relative.'"', 'href="'.basename($relative).'"'], 'href="#'.$slug.'"', $html);
        }

        return $html;
    }
}
