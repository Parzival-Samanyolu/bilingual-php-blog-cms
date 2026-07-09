<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Lang;
use App\Core\Pagination;
use App\Core\View;
use App\Models\ArticleModel;
use App\Models\CategoryModel;
use App\Models\SettingModel;

/**
 * Category listing pages. Supports nested slug paths (e.g. teknoloji/yazilim)
 * where each segment is resolved in turn against the category hierarchy.
 */
final class CategoryController extends BaseController
{
    /**
     * GET /kategori/{slug} — Turkish category page (slug may contain '/').
     */
    public function show(string $slug): void
    {
        $this->render($slug, 'tr');
    }

    /**
     * GET /category/{slug} — English category page (slug may contain '/').
     */
    public function showEn(string $slug): void
    {
        $this->render($slug, 'en');
    }

    private function render(string $slug, string $lang): void
    {
        Lang::useLang($lang);

        $categories = new CategoryModel();
        $category   = $this->resolvePath($categories, $slug);

        if ($category === null) {
            $this->notFound();

            return;
        }

        $categoryId = (int) $category['id'];
        $category   = $categories->getWithTranslation($categoryId, $lang) ?? $category;

        $perPage = (int) SettingModel::get('articles_per_page', '12');
        if ($perPage < 1) {
            $perPage = 12;
        }
        $page = max(1, (int) ($_GET['page'] ?? 1));

        // Include articles filed under descendant categories, not just direct ones.
        $articles = new ArticleModel();
        $items = $articles->getByCategory($categoryId, $lang, 'published', $page, $perPage, true);
        $total = $articles->countByCategory($categoryId, 'published', true);

        $children = $categories->getChildren($categoryId);
        $breadcrumb = $categories->getBreadcrumb($categoryId, $lang);

        $baseUrl = ($lang === 'en' ? '/category/' : '/kategori/') . $slug;
        $pagination = new Pagination($total, $perPage, $page, $baseUrl);

        View::render('category/show', [
            'lang'        => $lang,
            'category'    => $category,
            'articles'    => $items,
            'children'    => $children,
            'breadcrumb'  => $breadcrumb,
            'total'       => $total,
            'pagination'  => $pagination,
            'meta'        => [
                'title'       => (string) ($category['name'] ?? ''),
                'description' => (string) ($category['description'] ?? ''),
                'og_type'     => 'website',
                'url_tr'      => '/kategori/' . $slug,
                'url_en'      => '/category/' . $slug,
            ],
        ]);
    }

    /**
     * Resolve a (possibly nested) category slug path.
     *
     * Category slugs are globally unique, so the deepest segment alone
     * identifies the category. When a nested path is supplied the full ancestor
     * chain is verified so that only the canonical path resolves (a mismatched
     * parent yields a 404, avoiding duplicate-content URLs). A single leaf slug
     * always resolves directly.
     *
     * @return array<string,mixed>|null the raw row of the target category
     */
    private function resolvePath(CategoryModel $categories, string $slug): ?array
    {
        $segments = array_values(array_filter(
            explode('/', trim($slug, '/')),
            static fn ($s): bool => $s !== ''
        ));
        if ($segments === []) {
            return null;
        }

        // The last segment is the target (slugs are unique).
        $target = $categories->findBySlug((string) end($segments));
        if ($target === null) {
            return null;
        }

        // Validate the ancestor chain only when the caller supplied one.
        if (count($segments) > 1) {
            $expected = array_slice($segments, 0, -1); // ancestors, root -> parent
            $chain = [];
            $parentId = $target['parent_id'] !== null ? (int) $target['parent_id'] : null;
            $guard = 0;
            while ($parentId !== null && $guard < 50) {
                $row = $categories->find($parentId);
                if ($row === null) {
                    break;
                }
                array_unshift($chain, (string) $row['slug']);
                $parentId = $row['parent_id'] !== null ? (int) $row['parent_id'] : null;
                $guard++;
            }

            if ($chain !== $expected) {
                return null;
            }
        }

        return $target;
    }

    private function notFound(): void
    {
        http_response_code(404);
        View::render('errors/404', [
            'meta' => ['title' => __('error_404_title'), 'description' => ''],
        ]);
    }
}
