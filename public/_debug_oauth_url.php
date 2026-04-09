<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

try {
    $driver = \Laravel\Socialite\Facades\Socialite::driver('google');
    $driver->setScopes(['openid', 'email', 'profile'])
           ->with(['response_mode' => 'query', 'access_type' => 'online'])
           ->stateless();

    $ref = new ReflectionClass($driver);
    $paramProp = $ref->getProperty('parameters');
    $paramProp->setAccessible(true);
    $params = $paramProp->getValue($driver);
    echo "Internal params (after with): " . json_encode($params) . "\n\n";

    $url = $driver->redirect()->getTargetUrl();
    echo "URL: $url\n\n";
    $parts = parse_url($url);
    parse_str($parts['query'] ?? '', $qParams);
    foreach ($qParams as $k => $v) {
        echo "$k = $v\n";
    }
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
