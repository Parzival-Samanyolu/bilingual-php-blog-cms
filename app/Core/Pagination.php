<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Pagination calculator and link builder.
 *
 * Produces the SQL offset, the total page count and a flat list of link
 * descriptors (including prev/next and ellipses) for a view to render.
 */
final class Pagination
{
    private int $totalItems;
    private int $perPage;
    private int $currentPage;
    private string $baseUrl;
    private int $totalPages;

    public function __construct(int $totalItems, int $perPage, int $currentPage, string $baseUrl)
    {
        $this->totalItems  = max(0, $totalItems);
        $this->perPage     = max(1, $perPage);
        $this->totalPages  = (int) max(1, (int) ceil($this->totalItems / $this->perPage));
        $this->currentPage = min(max(1, $currentPage), $this->totalPages);
        $this->baseUrl     = $baseUrl;
    }

    public function getOffset(): int
    {
        return ($this->currentPage - 1) * $this->perPage;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    public function hasPages(): bool
    {
        return $this->totalPages > 1;
    }

    /**
     * Build the page link descriptors.
     *
     * @return array<int, array{number:?int, url:?string, active:bool, disabled:bool, label:string}>
     */
    public function getLinks(): array
    {
        $links = [];

        // Previous
        $links[] = [
            'number'   => $this->currentPage - 1,
            'url'      => $this->currentPage > 1 ? $this->pageUrl($this->currentPage - 1) : null,
            'active'   => false,
            'disabled' => $this->currentPage <= 1,
            'label'    => '‹',
        ];

        $window = 2;
        $start  = max(1, $this->currentPage - $window);
        $end    = min($this->totalPages, $this->currentPage + $window);

        if ($start > 1) {
            $links[] = $this->numberLink(1);
            if ($start > 2) {
                $links[] = $this->ellipsis();
            }
        }

        for ($page = $start; $page <= $end; $page++) {
            $links[] = $this->numberLink($page);
        }

        if ($end < $this->totalPages) {
            if ($end < $this->totalPages - 1) {
                $links[] = $this->ellipsis();
            }
            $links[] = $this->numberLink($this->totalPages);
        }

        // Next
        $links[] = [
            'number'   => $this->currentPage + 1,
            'url'      => $this->currentPage < $this->totalPages ? $this->pageUrl($this->currentPage + 1) : null,
            'active'   => false,
            'disabled' => $this->currentPage >= $this->totalPages,
            'label'    => '›',
        ];

        return $links;
    }

    /**
     * @return array{number:int, url:string, active:bool, disabled:bool, label:string}
     */
    private function numberLink(int $page): array
    {
        return [
            'number'   => $page,
            'url'      => $this->pageUrl($page),
            'active'   => $page === $this->currentPage,
            'disabled' => false,
            'label'    => (string) $page,
        ];
    }

    /**
     * @return array{number:null, url:null, active:bool, disabled:bool, label:string}
     */
    private function ellipsis(): array
    {
        return [
            'number'   => null,
            'url'      => null,
            'active'   => false,
            'disabled' => true,
            'label'    => '…',
        ];
    }

    /**
     * Append/merge a page number into the base URL, preserving existing query.
     */
    private function pageUrl(int $page): string
    {
        $parts = parse_url($this->baseUrl);
        $query = [];
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
        }
        $query['page'] = $page;

        $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
        $host   = $parts['host'] ?? '';
        $port   = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path   = $parts['path'] ?? '';

        return $scheme . $host . $port . $path . '?' . http_build_query($query);
    }
}
