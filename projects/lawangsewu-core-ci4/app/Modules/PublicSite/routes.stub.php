<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| PublicSite Route Stub
|--------------------------------------------------------------------------
|
| Stub route untuk landing dan front-facing shell Lawangsewu.
| Target utamanya mempertahankan kontrak route `/`.
|
*/

return [
    'GET /' => 'PublicSite\\LandingController::index',
    'POST /' => 'PublicSite\\LandingController::authenticateInline',
];