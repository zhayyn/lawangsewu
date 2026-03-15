<?php

declare(strict_types=1);

namespace App\Modules\PublicSite\Controllers;

require_once dirname(__DIR__) . '/Support/LegacyLandingSourceAdapter.php';

use App\Modules\PublicSite\Services\LegacyLandingPageService;
use App\Modules\PublicSite\Support\LegacyLandingSourceAdapter;

final class LandingController
{
    private LegacyLandingPageService $landing;
    private LegacyLandingSourceAdapter $sourceAdapter;

    public function __construct(?LegacyLandingPageService $landing = null, ?LegacyLandingSourceAdapter $sourceAdapter = null)
    {
        $this->landing = $landing ?? new LegacyLandingPageService();
        $this->sourceAdapter = $sourceAdapter ?? new LegacyLandingSourceAdapter();
    }

    public function index(array $server = []): array
    {
        $legacyHtml = $this->sourceAdapter->render($server);
        if (is_string($legacyHtml) && $legacyHtml !== '') {
            return [
                'status' => 200,
                'rawHtml' => $legacyHtml,
            ];
        }

        return [
            'view' => 'publicsite/landing',
            'data' => $this->landing->buildState($server),
        ];
    }

    public function authenticateInline(array $server = [], array $post = []): array
    {
        $state = $this->landing->buildState($server, $post);
        if (($state['redirectTo'] ?? null) !== null) {
            return [
                'redirect' => $state['redirectTo'],
            ];
        }

        return [
            'view' => 'publicsite/landing',
            'data' => $state,
        ];
    }
}