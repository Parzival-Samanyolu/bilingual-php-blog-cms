<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Lang;
use App\Core\Pagination;
use App\Core\View;
use App\Models\ArticleModel;
use App\Models\CategoryModel;
use App\Models\SettingModel;
use App\Models\TagModel;

/**
 * Full-text search with optional category / tag filters.
 */
final class SearchController extends BaseController
{
    /**
     * GET /ara — Turkish search page.
     */
    public function index(): void
    {
        $this->run('tr');
    }

    /**
     * GET /search — English search page.
     */
    public function indexEn(): void
    {
        $this->run('en');
    }

    private function run(string $lang): void
    {
        Lang::useLang($lang);

        $query       = trim((string) ($_GET['q'] ?? ''));
        $categorySlug = trim((string) ($_GET['category'] ?? ''));
        $tagSlug     = trim((string) ($_GET['tag'] ?? ''));
        $page        = max(1, (int) ($_GET['page'] ?? 1));

        $perPage = (int) SettingModel::get('articles_per_page', '12');
        if ($perPage < 1) {
            $perPage = 12;
        }

        $categories = new CategoryModel();
        $tags       = new TagModel();

        $categoryId = null;
        if ($categorySlug !== '') {
            $cat = $categories->findBySlug($categorySlug);
            if ($cat !== null) {
                $categoryId = (int) $cat['id'];
            }
        }

        $tagId = null;
        if ($tagSlug !== '') {
            $tag = $tags->findBySlug($tagSlug);
            if ($tag !== null) {
                $tagId = (int) $tag['id'];
            }
        }

        $articles = new ArticleModel();
        $results  = [];
        $total    = 0;
        if ($query !== '') {
            $results = $articles->search($query, $lang, $categoryId, $tagId, $page, $perPage);
            $total   = $articles->countSearch($query, $lang, $categoryId, $tagId);
        }

        // Filter option lists.
        $categoryOptions = $this->flattenTree($categories->getTree($lang));
        $tagOptions      = $tags->getPopular($lang, 200);

        $baseUrl = $this->buildBaseUrl($lang, $query, $categorySlug, $tagSlug);
        $pagination = new Pagination($total, $perPage, $page, $baseUrl);

        View::render('search/index', [
            'lang'            => $lang,
            'query'           => $query,
            'categorySlug'    => $categorySlug,
            'tagSlug'         => $tagSlug,
            'results'         => $results,
            'total'           => $total,
            'pagination'      => $pagination,
            'categoryOptions' => $categoryOptions,
            'tagOptions'      => $tagOptions,
            'meta'            => [
                'title'       => $query !== ''
                    ? __('search_results_for', ['query' => $query])
                    : __('nav_search'),
                'description' => __('search_meta_description'),
                'og_type'     => 'website',
                'url_tr'      => '/ara',
                'url_en'      => '/search',
            ],
        ]);
    }

    /**
     * Flatten a nested category tree into a depth-annotated flat list for a
     * <select> dropdown.
     *
     * @param array<int,array<string,mixed>> $tree
     * @param int                            $depth
     * @return array<int,array{id:int,name:string,slug:string,depth:int}>
     */
    private function flattenTree(array $tree, int $depth = 0): array
    {
        $flat = [];
        foreach ($tree as $node) {
            $flat[] = [
                'id'    => (int) $node['id'],
                'name'  => (string) $node['name'],
                'slug'  => (string) $node['slug'],
                'depth' => $depth,
            ];
            if (!empty($node['children'])) {
                foreach ($this->flattenTree($node['children'], $depth + 1) as $child) {
                    $flat[] = $child;
                }
            }
        }

        return $flat;
    }

    /**
     * Build the pagination base URL preserving the active query/filters.
     */
    private function buildBaseUrl(string $lang, string $query, string $categorySlug, string $tagSlug): string
    {
        $path = $lang === 'en' ? '/search' : '/ara';
        $params = [];
        if ($query !== '') {
            $params['q'] = $query;
        }
        if ($categorySlug !== '') {
            $params['category'] = $categorySlug;
        }
        if ($tagSlug !== '') {
            $params['tag'] = $tagSlug;
        }

        return $params === [] ? $path : $path . '?' . http_build_query($params);
    }
}
