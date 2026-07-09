<?php
/**
 * 404 Not Found page.
 */
?>
<div class="container page page--error">
    <div class="error-page">
        <p class="error-page__code">404</p>
        <h1 class="error-page__title"><?= e(__('error_404_title')) ?></h1>
        <p class="error-page__message"><?= e(__('error_404_message')) ?></p>
        <a class="btn btn--primary" href="/"><?= e(__('error_back_home')) ?></a>
    </div>
</div>
