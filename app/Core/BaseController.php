<?php
declare(strict_types=1);

namespace App\Core;

abstract class BaseController
{
    protected function id(Request $r): int
    {
        return (int) ($r->params['id'] ?? 0);
    }
}
