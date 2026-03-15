<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| DocsRegistry Route Stub
|--------------------------------------------------------------------------
|
| Stub route untuk walkthrough dan katalog dokumen Lawangsewu.
| Target utamanya mempertahankan kontrak `/walkthrough`.
|
*/

return [
    'GET /walkthrough' => 'DocsRegistry\\IndexController::index',
];