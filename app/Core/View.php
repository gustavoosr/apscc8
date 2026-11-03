<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    /** Renderiza resources/views/{$view}.php dentro do layout (ou sem layout). */
    public static function render(string $view, array $data = [], ?string $layout = 'layout'): never
    {
        $dir = Config::$basePath . '/resources/views/';
        extract($data, EXTR_SKIP);
        ob_start();
        require $dir . $view . '.php';
        $content = ob_get_clean();
        header('Content-Type: text/html; charset=utf-8');
        if ($layout) {
            require $dir . $layout . '.php';
        } else {
            echo $content;
        }
        exit;
    }

    public static function e(mixed $v): string
    {
        return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
