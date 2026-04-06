<?php
require __DIR__ . '/bootstrap.php';

$requestedReturn = gateway_normalize_return_path((string) ($_REQUEST['return'] ?? ''), gateway_ui_url('index'));

// Hindari fallback ke /portal agar alur login konsisten ke dashboard gateway.
if ($requestedReturn === '/portal' || $requestedReturn === '/lawangsewu/portal') {
    $requestedReturn = '/gateway/index';
}

$postAction = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/gateway/login'), PHP_URL_PATH);
if ($postAction === '') {
    $postAction = '/gateway/login';
}

if (gateway_is_logged_in()) {
    header('Location: ' . $requestedReturn);
    exit;
}

$landingUrl = '/?login=1';
if ($requestedReturn !== '/gateway/index') {
    $landingUrl .= '&return=' . rawurlencode($requestedReturn);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedUsername = trim((string) ($_POST['username'] ?? ''));
    $result = gateway_attempt_login($submittedUsername, (string) ($_POST['password'] ?? ''));
    if (!empty($result['ok'])) {
        gateway_flash_set('auth_success', 'Login berhasil. Anda sudah masuk ke dashboard portal.');
        header('Location: ' . $requestedReturn);
        exit;
    }

    $baseError = (string) ($result['message'] ?? 'Login gagal.');
    gateway_flash_set(
        'auth_error',
        $baseError === 'Username atau password salah.'
            ? 'Username atau password salah. Hubungi Dubes Prakom jika Anda membutuhkan bantuan akses.'
            : $baseError
    );
}

header('Location: ' . $landingUrl, true, 302);
exit;