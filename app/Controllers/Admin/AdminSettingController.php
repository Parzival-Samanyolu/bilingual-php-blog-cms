<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Image;
use App\Core\Session;
use App\Core\View;
use App\Models\SettingModel;
use RuntimeException;

/**
 * Admin site settings editor.
 */
class AdminSettingController extends BaseController
{
    /**
     * String setting keys that are edited via the form.
     *
     * @var array<int,string>
     */
    private const KEYS = [
        'site_name_tr',
        'site_name_en',
        'adsense_client_id',
        'ga_measurement_id',
        'contact_email',
        'articles_per_page',
        'cache_ttl',
        'og_default_image',
    ];

    /**
     * GET /admin/ayarlar — settings form.
     */
    public function index(): void
    {
        $this->requireAdmin();

        $settings = (new SettingModel())->getAll();

        View::render('admin/settings/index', [
            'pageTitle' => __('admin_settings'),
            'settings'  => $settings,
        ], 'admin');
    }

    /**
     * POST /admin/ayarlar — persist settings (with optional OG image upload).
     */
    public function save(): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $model = new SettingModel();

        $values = [];
        foreach (self::KEYS as $key) {
            if ($key === 'og_default_image') {
                continue; // handled separately below
            }
            if (array_key_exists($key, $_POST)) {
                $values[$key] = trim((string) $_POST[$key]);
            }
        }

        // Normalise numeric fields.
        if (isset($values['articles_per_page'])) {
            $values['articles_per_page'] = (string) max(1, (int) $values['articles_per_page']);
        }
        if (isset($values['cache_ttl'])) {
            $values['cache_ttl'] = (string) max(0, (int) $values['cache_ttl']);
        }

        // Optional OG default image upload.
        if (isset($_FILES['og_default_image']) && ($_FILES['og_default_image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            try {
                $path = Image::upload($_FILES['og_default_image'], 'site');
                $old = $model->get('og_default_image');
                $values['og_default_image'] = $path;
                if ($old !== null && $old !== '' && str_starts_with($old, '/uploads/')) {
                    Image::delete($old);
                }
            } catch (RuntimeException $e) {
                Session::setFlash('error', __('admin_image_upload_failed') . ' ' . $e->getMessage());
                View::redirect('/admin/ayarlar');
                return;
            }
        } elseif (isset($_POST['og_default_image']) && trim((string) $_POST['og_default_image']) !== '') {
            // Allow keeping/overriding with a manual path value.
            $values['og_default_image'] = trim((string) $_POST['og_default_image']);
        }

        if ($values !== []) {
            $model->setMultiple($values);
        }

        Session::setFlash('success', __('admin_settings_saved'));
        View::redirect('/admin/ayarlar');
    }
}
