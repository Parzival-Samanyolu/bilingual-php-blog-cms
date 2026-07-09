<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Lang;
use App\Core\Session;
use App\Core\View;
use App\Models\SettingModel;

/**
 * Static informational pages: About and Contact (incl. contact form handling).
 */
final class PageController extends BaseController
{
    /**
     * GET /hakkimizda — About page.
     */
    public function about(): void
    {
        View::render('pages/about', [
            'lang' => Lang::getLang(),
            'meta' => [
                'title'       => __('about_page_title'),
                'description' => __('about_meta_description'),
                'og_type'     => 'website',
                'url_tr'      => '/hakkimizda',
                'url_en'      => '/about',
            ],
        ]);
    }

    /**
     * GET /iletisim — Contact page (form).
     */
    public function contact(): void
    {
        $email = SettingModel::get('contact_email', 'info@real.com.tr');

        View::render('pages/contact', [
            'lang'         => Lang::getLang(),
            'contactEmail' => $email,
            'meta' => [
                'title'       => __('contact_page_title'),
                'description' => __('contact_meta_description'),
                'og_type'     => 'website',
                'url_tr'      => '/iletisim',
                'url_en'      => '/contact',
            ],
        ]);
    }

    /**
     * POST /iletisim — send the contact message via mail().
     */
    public function sendContact(): void
    {
        $token = (string) ($_POST['_csrf'] ?? '');
        if (!Session::validateToken($token)) {
            Session::setFlash('error', __('flash_error'));
            View::redirect('/iletisim');

            return;
        }

        $name    = trim((string) ($_POST['name'] ?? ''));
        $email   = trim((string) ($_POST['email'] ?? ''));
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));

        if (
            $name === ''
            || $message === ''
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            Session::setFlash('error', __('contact_error'));
            View::redirect('/iletisim');

            return;
        }

        $to = SettingModel::get('contact_email', 'info@real.com.tr') ?? 'info@real.com.tr';
        $from = $_ENV['MAIL_FROM'] ?? ('no-reply@' . ($_SERVER['SERVER_NAME'] ?? 'real.com.tr'));

        $mailSubject = '[real.com.tr] ' . ($subject !== '' ? $subject : __('contact_page_title'));

        $body = "Ad / Name: {$name}\r\n"
            . "E-posta / Email: {$email}\r\n"
            . "Konu / Subject: {$subject}\r\n\r\n"
            . $message . "\r\n";

        $headers = 'From: ' . $this->encodeHeader($from) . "\r\n"
            . 'Reply-To: ' . $email . "\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . 'X-Mailer: PHP/' . PHP_VERSION;

        $encodedSubject = '=?UTF-8?B?' . base64_encode($mailSubject) . '?=';

        $sent = @mail($to, $encodedSubject, $body, $headers);

        if ($sent) {
            Session::setFlash('success', __('contact_success'));
        } else {
            Session::setFlash('error', __('contact_error'));
        }

        View::redirect('/iletisim');
    }

    /**
     * Guard a header value against header injection.
     */
    private function encodeHeader(string $value): string
    {
        return trim(str_replace(["\r", "\n"], '', $value));
    }
}
