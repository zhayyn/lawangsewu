<?php

declare(strict_types=1);

namespace App\Modules\PublicSite\Services;

use App\Modules\AuthGateway\Services\LegacyGatewayAuthBridge;
use App\Modules\PublicSite\Contracts\LandingPageInterface;

final class LegacyLandingPageService implements LandingPageInterface
{
    private LegacyGatewayAuthBridge $auth;

    public function __construct(?LegacyGatewayAuthBridge $auth = null)
    {
        $this->auth = $auth ?? new LegacyGatewayAuthBridge();
    }

    public function buildState(array $server, array $post = []): array
    {
        $requestUri = (string) ($server['REQUEST_URI'] ?? '/');
        $method = strtoupper((string) ($server['REQUEST_METHOD'] ?? 'GET'));
        $portalUrl = $this->publicUrl('/portal', $requestUri);
        $landingUrl = $this->publicUrl('/', $requestUri);
        $loggedInUser = $this->auth->currentUser();
        $loginError = '';
        $submittedUsername = '';
        $showLoginPanel = false;
        $redirectTo = null;

        if ($method === 'POST' && (string) ($post['landing_login'] ?? '') === '1') {
            $submittedUsername = trim((string) ($post['username'] ?? ''));
            $result = $this->auth->attemptLogin($submittedUsername, (string) ($post['password'] ?? ''));

            if (!empty($result['ok'])) {
                $redirectTo = $portalUrl;
            } else {
                $loginError = (string) ($result['message'] ?? 'Login gagal.');
                $showLoginPanel = true;
            }
        }

        return [
            'portalUrl' => $portalUrl,
            'landingUrl' => $landingUrl,
            'loginError' => $loginError,
            'submittedUsername' => $submittedUsername,
            'showLoginPanel' => $showLoginPanel,
            'loggedInUser' => $loggedInUser,
            'loggedInName' => $this->displayName($loggedInUser),
            'isLoggedIn' => $loggedInUser !== null,
            'redirectTo' => $redirectTo,
        ];
    }

    private function publicUrl(string $path, string $requestUri): string
    {
        return $this->prefixFromUri($requestUri) . $path;
    }

    private function prefixFromUri(string $requestUri): string
    {
        $path = (string) parse_url($requestUri, PHP_URL_PATH);
        if ($path === '/lawangsewu' || str_starts_with($path, '/lawangsewu/')) {
            return '/lawangsewu';
        }

        return '';
    }

    private function displayName(?array $user): string
    {
        if (!is_array($user)) {
            return '';
        }

        $fullName = trim((string) ($user['full_name'] ?? ''));
        if ($fullName !== '') {
            return $fullName;
        }

        return (string) ($user['username'] ?? 'Portal User');
    }
}