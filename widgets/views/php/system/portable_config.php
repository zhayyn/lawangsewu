<?php

if (!function_exists('lw_load_env_file')) {
    function lw_load_env_file(string $filePath): array
    {
        $values = [];
        if (!is_readable($filePath)) {
            return $values;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return $values;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            $value = trim($value, "\"'");
            $values[$key] = $value;
        }

        return $values;
    }
}

if (!function_exists('lw_config')) {
    function lw_config(): array
    {
        static $config = null;
        if ($config !== null) {
            return $config;
        }

        $root = defined('LAWANGSEWU_ROOT') ? LAWANGSEWU_ROOT : dirname(__DIR__, 4);
        $viewsRoot = dirname(__DIR__, 2);
        $appEnv = lw_load_env_file($root . '/.env');
        $legacyViewEnv = lw_load_env_file($viewsRoot . '/.env');
        $viewEnv = lw_load_env_file($viewsRoot . '/config/.env');
        $env = array_merge($appEnv, $legacyViewEnv, $viewEnv);

        $sippBase = rtrim($env['LW_SIPP_BASE_URL'] ?? 'https://sipp.pa-semarang.go.id', '/');
        $server10Base = rtrim($env['LW_SERVER10_BASE_URL'] ?? 'http://192.168.88.10', '/');
        $server10AllowedRaw = (string)($env['LW_SERVER10_ALLOWED_PATHS'] ?? '/lumpiapasar/panjar/_panjar_data_wilayah.php,/lumpiapasar/panjar/_cerai_proses.php,/lumpiapasar/api.php,/api/data');
        $server10Allowed = [];
        foreach (explode(',', $server10AllowedRaw) as $entry) {
            $path = trim($entry);
            if ($path === '' || !str_starts_with($path, '/')) {
                continue;
            }
            $server10Allowed[] = $path;
        }
        if (!$server10Allowed) {
            $server10Allowed = ['/lumpiapasar/panjar/_panjar_data_wilayah.php', '/lumpiapasar/panjar/_cerai_proses.php', '/lumpiapasar/api.php', '/api/data'];
        }

        $config = [
            'root' => $root,
            'sipp_base_url' => $sippBase,
            'sipp_slide_url' => $env['LW_SIPP_SLIDE_URL'] ?? ($sippBase . '/slide_sidang'),
            'sipp_search_url' => $env['LW_SIPP_SEARCH_URL'] ?? ($sippBase . '/?s='),
            'slide_local_file' => $env['LW_SLIDE_LOCAL_FILE'] ?? ($root . '/widgets/views/html/public/slide_sidang.html'),
            'log_file' => $env['LW_LOG_FILE'] ?? ($root . '/logs/antrian_controller.log'),
            'server10_base_url' => $server10Base,
            'server10_token' => (string)($env['LW_SERVER10_TOKEN'] ?? ''),
            'server10_timeout' => (int)($env['LW_SERVER10_TIMEOUT'] ?? 15),
            'server10_retries' => (int)($env['LW_SERVER10_RETRIES'] ?? 2),
            'server10_log_file' => $env['LW_SERVER10_LOG_FILE'] ?? ($root . '/logs/server10-bridge.log'),
            'server10_allowed_paths' => $server10Allowed,
        ];

        return $config;
    }
}
