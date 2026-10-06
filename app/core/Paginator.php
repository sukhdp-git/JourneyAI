<?php
declare(strict_types=1);

namespace App\Core;

final class Paginator
{
    public readonly int $page;
    public readonly int $pages;
    public readonly int $offset;

    /** @param ?string $pattern clean public pattern such as "/blog/page/{n}" (page 1 uses $firstPage) */
    public function __construct(public readonly int $total, public readonly int $perPage, int $page, private readonly ?string $pattern = null, private readonly string $firstPage = '/')
    {
        $this->pages = max(1, (int) ceil($total / max(1, $perPage)));
        $this->page = min(max(1, $page), $this->pages);
        $this->offset = ($this->page - 1) * $perPage;
    }

    /** Builds a URL to another page, keeping the current (whitelisted) query string. */
    public function url(int $page): string
    {
        if ($this->pattern !== null) {
            $q = array_intersect_key($_GET, ['q' => 1]);
            $path = $page <= 1 ? $this->firstPage : str_replace('{n}', (string) $page, $this->pattern);
            return url($path) . ($q ? '?' . http_build_query($q) : '');
        }
        $q = $_GET;
        $q['page'] = $page;
        if ($page === 1) {
            unset($q['page']);
        }
        $path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
        return $path . ($q ? '?' . http_build_query($q) : '');
    }
}
