<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Core\Request;

/** Interpreta page, per_page, sort (ex.: sort=-reference_month) com whitelist de colunas. */
final class Pagination
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly string $orderBy
    ) {}

    public static function from(Request $r, array $sortable, string $defaultSort): self
    {
        $page = max(1, (int) $r->q('page', 1));
        $per = min(100, max(1, (int) $r->q('per_page', 15)));
        $sort = (string) $r->q('sort', $defaultSort);
        $dir = 'ASC';
        if (str_starts_with($sort, '-')) {
            $dir = 'DESC';
            $sort = substr($sort, 1);
        }
        if (!isset($sortable[$sort])) {
            $sort = ltrim($defaultSort, '-');
            $dir = str_starts_with($defaultSort, '-') ? 'DESC' : 'ASC';
        }
        return new self($page, $per, $sortable[$sort] . ' ' . $dir . ', 1');
    }

    public function limitSql(): string
    {
        return sprintf('LIMIT %d OFFSET %d', $this->perPage, ($this->page - 1) * $this->perPage);
    }

    public function meta(int $total): array
    {
        return [
            'page' => $this->page,
            'per_page' => $this->perPage,
            'total' => $total,
            'total_pages' => (int) max(1, ceil($total / $this->perPage)),
        ];
    }
}
