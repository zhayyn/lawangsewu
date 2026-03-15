<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| WidgetRegistry Route Stub
|--------------------------------------------------------------------------
|
| Stub route untuk katalog widget publik Lawangsewu.
| Target utamanya mempertahankan kontrak `/daftar-widget`.
|
*/

return [
    'GET /daftar-widget' => 'WidgetRegistry\\DirectoryController::index',
];