<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Portal Route Stub
|--------------------------------------------------------------------------
|
| Stub route untuk dashboard utama Lawangsewu.
| Target utamanya mempertahankan kontrak `/portal`.
|
*/

return [
    'GET /portal' => 'Portal\\HomeController::index',
];