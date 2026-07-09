<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Lang;
use App\Core\View;
use App\Models\ArticleModel;
use App\Models\CategoryModel;
use App\Models\TagModel;

/**
 * Public home page: hero search, trending strip, category cards, latest grid
 * and a popular-tag cloud.
 */
final class HomeController extends BaseController
{
    /**
     * GET / — Turkish landing page.
     */
    public function index(): void
    {
        $this->renderHome('tr');
    }

    /**
     * GET /en — English landing page.
     */
    public function indexEn(): void
    {
        $this->renderHome('en');
    }

    private function renderHome(string $lang): void
    {
        Lang::useLang($lang);

        $articles = new ArticleModel();
        $categories = new CategoryModel();
        $tags = new TagModel();

        $trending = $articles->getTrending($lang, 6, 7);
        $latest   = $articles->getLatest($lang, 12, 1);
        $tree     = $categories->getTree($lang);
        $popular  = $tags->getPopular($lang, 20);

        View::render('home/index', [
            'lang'         => $lang,
            'trending'     => $trending,
            'latest'       => $latest,
            'categoryTree' => $tree,
            'popularTags'  => $popular,
            'meta'         => [
                'title'       => __('site_tagline'),
                'description' => __('site_description'),
                'og_type'     => 'website',
                'url_tr'      => '/',
                'url_en'      => '/en',
            ],
        ]);
    }
}
