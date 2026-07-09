<?php
/**
 * Contact page with a mail() form.
 *
 * @var string      $lang
 * @var string|null $contactEmail
 */

use App\Core\Session;

$email = (string) ($contactEmail ?? '');
$action = $lang === 'en' ? '/contact' : '/iletisim';
?>
<div class="container page page--contact">
    <h1 class="page__title"><?= e(__('contact_page_title')) ?></h1>
    <p class="page__lead"><?= e(__('contact_lead')) ?></p>

    <?php if ($email !== ''): ?>
        <p class="contact__direct">
            <?= e(__('contact_direct_label')) ?>:
            <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
        </p>
    <?php endif; ?>

    <form class="contact-form" method="post" action="<?= e($action) ?>">
        <input type="hidden" name="_csrf" value="<?= e(Session::getToken()) ?>">

        <label class="form-field">
            <span class="form-field__label"><?= e(__('contact_field_name')) ?></span>
            <input type="text" name="name" class="form-field__input" required maxlength="255">
        </label>

        <label class="form-field">
            <span class="form-field__label"><?= e(__('contact_field_email')) ?></span>
            <input type="email" name="email" class="form-field__input" required maxlength="255">
        </label>

        <label class="form-field">
            <span class="form-field__label"><?= e(__('contact_field_subject')) ?></span>
            <input type="text" name="subject" class="form-field__input" maxlength="255">
        </label>

        <label class="form-field">
            <span class="form-field__label"><?= e(__('contact_field_message')) ?></span>
            <textarea name="message" class="form-field__textarea" rows="6" required maxlength="5000"></textarea>
        </label>

        <div class="form-actions">
            <button type="submit" class="btn btn--primary"><?= e(__('contact_submit')) ?></button>
        </div>
    </form>
</div>
