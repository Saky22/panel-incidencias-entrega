<?php

// Solo sobreescribe lo que difiere del paquete (se fusiona por claves de primer nivel).
return [
    'pages' => [
        'ensure_pages_exist' => false,
        // El proyecto usa resources/js/Pages (convención con mayúscula).
        'paths' => [resource_path('js/Pages')],
        'extensions' => ['jsx', 'js', 'tsx', 'ts'],
    ],
];
