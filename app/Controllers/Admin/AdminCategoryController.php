<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\CategoryModel;

/**
 * Admin management of the category tree (bilingual TR/EN).
 */
class AdminCategoryController extends BaseController
{
    /**
     * GET /admin/kategoriler — tree listing.
     */
    public function list(): void
    {
        $this->requireAdmin();

        View::render('admin/categories/list', [
            'pageTitle' => __('admin_categories'),
            'tree'      => (new CategoryModel())->getTree('tr'),
        ], 'admin');
    }

    /**
     * GET /admin/kategoriler/yeni — new category form.
     */
    public function create(): void
    {
        $this->requireAdmin();

        View::render('admin/categories/edit', [
            'pageTitle'   => __('admin_category_new'),
            'category'    => null,
            'translations' => ['tr' => [], 'en' => []],
            'parents'     => $this->parentOptions(null),
            'formAction'  => '/admin/kategoriler/yeni',
        ], 'admin');
    }

    /**
     * POST /admin/kategoriler/yeni — persist new category.
     */
    public function store(): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $data = $this->collect();
        if ($data['name_tr'] === '') {
            Session::setFlash('error', __('admin_category_name_required'));
            View::redirect('/admin/kategoriler/yeni');
            return;
        }

        $db = Database::getInstance();
        $slug = $data['slug'] !== '' ? $this->slugify($data['slug']) : $this->slugify($data['name_tr']);
        $slug = $this->uniqueSlug($slug, 0);

        $db->beginTransaction();
        try {
            $db->execute(
                "INSERT INTO `categories` (`parent_id`, `slug`, `sort_order`, `created_at`)
                 VALUES (?, ?, ?, NOW())",
                [$data['parent_id'], $slug, $data['sort_order']]
            );
            $catId = $db->lastInsertId();
            $this->saveTranslations($catId, $data);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollback();
            Session::setFlash('error', __('admin_save_failed'));
            View::redirect('/admin/kategoriler/yeni');
            return;
        }

        Session::setFlash('success', __('admin_category_saved'));
        View::redirect('/admin/kategoriler');
    }

    /**
     * GET /admin/kategoriler/{id} — edit form.
     */
    public function edit(int $id): void
    {
        $this->requireAdmin();

        $db = Database::getInstance();
        $category = $db->fetch("SELECT * FROM `categories` WHERE `id` = ? LIMIT 1", [$id]);
        if ($category === null) {
            Session::setFlash('error', __('admin_category_not_found'));
            View::redirect('/admin/kategoriler');
            return;
        }

        $rows = $db->fetchAll(
            "SELECT `lang`, `name`, `description` FROM `category_translations` WHERE `category_id` = ?",
            [$id]
        );
        $translations = ['tr' => [], 'en' => []];
        foreach ($rows as $row) {
            $translations[(string) $row['lang']] = $row;
        }

        View::render('admin/categories/edit', [
            'pageTitle'    => __('admin_category_edit'),
            'category'     => $category,
            'translations' => $translations,
            'parents'      => $this->parentOptions($id),
            'formAction'   => '/admin/kategoriler/' . $id,
        ], 'admin');
    }

    /**
     * POST /admin/kategoriler/{id} — persist edits.
     */
    public function update(int $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $db = Database::getInstance();
        $category = $db->fetch("SELECT * FROM `categories` WHERE `id` = ? LIMIT 1", [$id]);
        if ($category === null) {
            Session::setFlash('error', __('admin_category_not_found'));
            View::redirect('/admin/kategoriler');
            return;
        }

        $data = $this->collect();
        if ($data['name_tr'] === '') {
            Session::setFlash('error', __('admin_category_name_required'));
            View::redirect('/admin/kategoriler/' . $id);
            return;
        }

        // Prevent a category being its own parent.
        $parentId = $data['parent_id'] === $id ? null : $data['parent_id'];

        $slug = $data['slug'] !== '' ? $this->slugify($data['slug']) : $this->slugify($data['name_tr']);
        $slug = $this->uniqueSlug($slug, $id);

        $db->beginTransaction();
        try {
            $db->execute(
                "UPDATE `categories` SET `parent_id` = ?, `slug` = ?, `sort_order` = ? WHERE `id` = ?",
                [$parentId, $slug, $data['sort_order'], $id]
            );
            $this->saveTranslations($id, $data);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollback();
            Session::setFlash('error', __('admin_save_failed'));
            View::redirect('/admin/kategoriler/' . $id);
            return;
        }

        Session::setFlash('success', __('admin_category_saved'));
        View::redirect('/admin/kategoriler');
    }

    /**
     * POST /admin/kategoriler/{id}/sil — delete if empty.
     */
    public function delete(int $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $model = new CategoryModel();
        $db = Database::getInstance();

        $childCount = (int) $db->fetchColumn(
            "SELECT COUNT(*) FROM `categories` WHERE `parent_id` = ?",
            [$id]
        );
        $articleCount = (int) $db->fetchColumn(
            "SELECT COUNT(*) FROM `articles` WHERE `category_id` = ?",
            [$id]
        );

        if ($childCount > 0 || $articleCount > 0) {
            Session::setFlash('error', __('admin_category_delete_blocked'));
            View::redirect('/admin/kategoriler');
            return;
        }

        $model->delete($id);
        Session::setFlash('success', __('admin_category_deleted'));
        View::redirect('/admin/kategoriler');
    }

    /**
     * Read + normalise the category form fields.
     *
     * @return array{name_tr:string,desc_tr:string,name_en:string,desc_en:string,slug:string,parent_id:?int,sort_order:int}
     */
    private function collect(): array
    {
        $parent = ($_POST['parent_id'] ?? '') !== '' ? (int) $_POST['parent_id'] : null;

        return [
            'name_tr'    => trim((string) ($_POST['name_tr'] ?? '')),
            'desc_tr'    => trim((string) ($_POST['description_tr'] ?? '')),
            'name_en'    => trim((string) ($_POST['name_en'] ?? '')),
            'desc_en'    => trim((string) ($_POST['description_en'] ?? '')),
            'slug'       => trim((string) ($_POST['slug'] ?? '')),
            'parent_id'  => $parent,
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ];
    }

    /**
     * Upsert TR and EN translation rows for a category.
     *
     * @param array{name_tr:string,desc_tr:string,name_en:string,desc_en:string,slug:string,parent_id:?int,sort_order:int} $data
     */
    private function saveTranslations(int $catId, array $data): void
    {
        $db = Database::getInstance();
        $pairs = [
            'tr' => [$data['name_tr'], $data['desc_tr']],
            'en' => [$data['name_en'] !== '' ? $data['name_en'] : $data['name_tr'], $data['desc_en']],
        ];

        foreach ($pairs as $lang => [$name, $desc]) {
            $db->execute(
                "INSERT INTO `category_translations` (`category_id`, `lang`, `name`, `description`)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`)",
                [$catId, $lang, $name, $desc !== '' ? $desc : null]
            );
        }
    }

    /**
     * Flat list of categories usable as a parent (excludes given id).
     *
     * @return array<int,array{id:int,name:string}>
     */
    private function parentOptions(?int $excludeId): array
    {
        $rows = Database::getInstance()->fetchAll(
            "SELECT c.id, COALESCE(t.name, c.slug) AS name
             FROM `categories` c
             LEFT JOIN `category_translations` t ON t.category_id = c.id AND t.lang = 'tr'
             ORDER BY name ASC"
        );

        $out = [];
        foreach ($rows as $row) {
            if ($excludeId !== null && (int) $row['id'] === $excludeId) {
                continue;
            }
            $out[] = ['id' => (int) $row['id'], 'name' => (string) $row['name']];
        }

        return $out;
    }

    private function uniqueSlug(string $slug, int $exceptId): string
    {
        $db = Database::getInstance();
        $base = $slug;
        $i = 2;
        while (
            (int) $db->fetchColumn(
                "SELECT COUNT(*) FROM `categories` WHERE `slug` = ? AND `id` <> ?",
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

        return $text !== '' ? $text : 'kategori-' . substr(md5(uniqid('', true)), 0, 8);
    }
}
