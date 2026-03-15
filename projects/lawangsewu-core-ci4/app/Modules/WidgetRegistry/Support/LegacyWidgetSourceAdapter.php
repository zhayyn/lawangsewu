<?php

declare(strict_types=1);

namespace App\Modules\WidgetRegistry\Support;

final class LegacyWidgetSourceAdapter
{
    public function render(array $item): ?string
    {
        return $this->renderSourcePath(
            (string) ($item['sourcePath'] ?? ''),
            (string) ($item['path'] ?? '/')
        );
    }

    public function renderSourcePath(string $sourcePath, string $requestPath): ?string
    {
        $sourcePath = trim($sourcePath);
        if ($sourcePath === '') {
            return null;
        }

        $fullPath = dirname(__DIR__, 6) . '/' . ltrim($sourcePath, '/');
        if (!is_file($fullPath) || !is_readable($fullPath)) {
            return null;
        }

        if (str_ends_with(strtolower($fullPath), '.html')) {
            $html = file_get_contents($fullPath);
            return is_string($html) && $html !== '' ? $html : null;
        }

        if (!str_ends_with(strtolower($fullPath), '.php')) {
            return null;
        }

        return $this->renderPhpSource($fullPath, $requestPath);
    }

    private function renderPhpSource(string $fullPath, string $requestPath): ?string
    {
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
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);
        if ($exitCode !== 0 || !is_string($html) || $html === '') {
            return null;
        }

        return $html;
    }
}