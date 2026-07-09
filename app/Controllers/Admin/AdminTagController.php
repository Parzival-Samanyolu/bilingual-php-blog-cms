<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\TagModel;

/**
 * Admin management of tags (bilingual TR/EN, inline editing).
 */
class AdminTagController extends BaseController
{
    /**
     * GET /admin/etiketler — tag list with TR/EN names and usage counts.
     */
    public function list(): void
    {
        $this->requireAdmin();

        $tags = Database::getInstance()->fetchAll(
            "SELECT tg.id, tg.slug,
                    MAX(CASE WHEN tt.lang = 'tr' THEN tt.name END) AS name_tr,
                    MAX(CASE WHEN tt.lang = 'en' THEN tt.name END) AS name_en,
                    (SELECT COUNT(*) FROM `article_tags` at WHERE at.tag_id = tg.id) AS article_count
             FROM `tags` tg
             LEFT JOIN `tag_translations` tt ON tt.tag_id = tg.id
             GROUP BY tg.id, tg.slug
             ORDER BY name_tr ASC"
        );

        View::render('admin/tags/list', [
            'pageTitle' => __('admin_tags'),
            'tags'      => $tags,
        ], 'admin');
    }

    /**
     * POST /admin/etiketler/yeni — create a tag.
     */
    public function store(): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $nameTr = trim((string) ($_POST['name_tr'] ?? ''));
        $nameEn = trim((string) ($_POST['name_en'] ?? ''));

        if ($nameTr === '') {
            Session::setFlash('error', __('admin_tag_name_required'));
            View::redirect('/admin/etiketler');
            return;
        }

        $db = Database::getInstance();
        $slug = $this->uniqueSlug($this->slugify($nameTr), 0);

        $db->beginTransaction();
        try {
            $db->execute("INSERT INTO `tags` (`slug`, `created_at`) VALUES (?, NOW())", [$slug]);
            $tagId = $db->lastInsertId();
            $this->saveTranslations($tagId, $nameTr, $nameEn);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollback();
            Session::setFlash('error', __('admin_save_failed'));
            View::redirect('/admin/etiketler');
            return;
        }

        Session::setFlash('success', __('admin_tag_saved'));
        View::redirect('/admin/etiketler');
    }

    /**
     * POST /admin/etiketler/{id} — update a tag.
     */
    public function update(int $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $db = Database::getInstance();
        $tag = $db->fetch("SELECT * FROM `tags` WHERE `id` = ? LIMIT 1", [$id]);
        if ($tag === null) {
            Session::setFlash('error', __('admin_tag_not_found'));
            View::redirect('/admin/etiketler');
            return;
        }

        $nameTr = trim((string) ($_POST['name_tr'] ?? ''));
        $nameEn = trim((string) ($_POST['name_en'] ?? ''));
        if ($nameTr === '') {
            Session::setFlash('error', __('admin_tag_name_required'));
            View::redirect('/admin/etiketler');
            return;
        }

        $slugInput = trim((string) ($_POST['slug'] ?? ''));
        $slug = $slugInput !== '' ? $this->uniqueSlug($this->slugify($slugInput), $id) : (string) $tag['slug'];

        $db->beginTransaction();
        try {
            $db->execute("UPDATE `tags` SET `slug` = ? WHERE `id` = ?", [$slug, $id]);
            $this->saveTranslations($id, $nameTr, $nameEn);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollback();
            Session::setFlash('error', __('admin_save_failed'));
            View::redirect('/admin/etiketler');
            return;
        }

        Session::setFlash('success', __('admin_tag_saved'));
        View::redirect('/admin/etiketler');
    }

    /**
     * POST /admin/etiketler/{id}/sil — delete a tag (cascade removes links).
     */
    public function delete(int $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        (new TagModel())->delete($id);
        Session::setFlash('success', __('admin_tag_deleted'));
        View::redirect('/admin/etiketler');
    }

    private function saveTranslations(int $tagId, string $nameTr, string $nameEn): void
    {
        $db = Database::getInstance();
        $pairs = [
            'tr' => $nameTr,
            'en' => $nameEn !== '' ? $nameEn : $nameTr,
        ];
        foreach ($pairs as $lang => $name) {
            $db->execute(
                "INSERT INTO `tag_translations` (`tag_id`, `lang`, `name`)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE `name` = VALUES(`name`)",
                [$tagId, $lang, $name]
            );
        }
    }

    private function uniqueSlug(string $slug, int $exceptId): string
    {
        $db = Database::getInstance();
        $base = $slug;
        $i = 2;
        while (
            (int) $db->fetchColumn(
                "SELECT COUNT(*) FROM `tags` WHERE `slug` = ? AND `id` <> ?",
                [$slug, $exceptId]
            ) > 0
        ) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }

    private function slugify(string $text): string
    {
        $text = str_replace(
            ['ç', 'ğ', 'ı', 'İ', 'ö', 'ş', 'ü', 'Ç', 'Ğ', 'Ö', 'Ş', 'Ü'],
            ['c', 'g', 'i', 'i', 'o', 's', 'u', 'c', 'g', 'o', 's', 'u'],
            $text
        );
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        $text = trim($text, '-');

        return $text !== '' ? $text : 'etiket-' . substr(md5(uniqid('', true)), 0, 8);
    }
}
