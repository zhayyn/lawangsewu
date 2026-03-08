<?php
require __DIR__ . '/bootstrap.php';
gateway_logout();
gateway_flash_set('auth_error', 'Anda sudah logout dari Lawangsewu.');
header('Location: ' . gateway_wa_admin_portal_logout_url());
exit;