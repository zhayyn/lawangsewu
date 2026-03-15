<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class StagingViewRenderer
{
    public function render(string $view, array $data = []): string
    {
        $template = dirname(__DIR__) . '/Views/' . str_replace('..', '', $view) . '.php';
        if (!is_file($template)) {
            throw new RuntimeException('View template not found: ' . $view);
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $template;
        $output = ob_get_clean();

        return is_string($output) ? $output : '';
    }
}