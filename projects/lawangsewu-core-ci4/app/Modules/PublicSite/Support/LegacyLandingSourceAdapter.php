<?php

declare(strict_types=1);

namespace App\Modules\PublicSite\Support;

final class LegacyLandingSourceAdapter
{
    public function render(array $server = []): ?string
    {
        $requestPath = (string) ($server['REQUEST_URI'] ?? '/');
        $fullPath = dirname(__DIR__, 6) . '/widgets/views/php/public/landing.php';
        if (!is_file($fullPath) || !is_readable($fullPath)) {
            return null;
        }

        $script = '$_SERVER["REQUEST_METHOD"] = "GET"; '
            . '$_SERVER["REQUEST_URI"] = ' . var_export($requestPath, true) . '; '
            . '$_GET = []; $_POST = []; '
            . 'ob_start(); require ' . var_export($fullPath, true) . '; '
            . '$html = ob_get_clean(); fwrite(STDOUT, is_string($html) ? $html : "");';

        $command = ['php', '-r', $script];
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorSpec, $pipes);
        if (!is_resource($process)) {
            return null;
        }

        fclose($pipes[0]);
        $html = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);
        if ($exitCode !== 0 || !is_string($html) || $html === '') {
            return null;
        }

        return $html;
    }
}