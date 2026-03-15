<?php

declare(strict_types=1);

return [
    'GET /' => [
        'controller' => 'PublicSite',
        'method' => 'index',
    ],
    'POST /' => [
        'controller' => 'PublicSite',
        'method' => 'authenticateInline',
    ],
    'GET /portal' => [
        'controller' => 'Portal',
        'method' => 'index',
    ],
    'GET /portal/launch' => [
        'controller' => 'Portal',
        'method' => 'launch',
    ],
    'GET /walkthrough' => [
        'controller' => 'DocsRegistry',
        'method' => 'index',
    ],
    'GET /daftar-widget' => [
        'controller' => 'WidgetRegistry',
        'method' => 'index',
    ],
    'GET /app-registry' => [
        'controller' => 'AppRegistry',
        'method' => 'index',
    ],
    'GET /app-registry/launch' => [
        'controller' => 'AppRegistry',
        'method' => 'launch',
    ],
];