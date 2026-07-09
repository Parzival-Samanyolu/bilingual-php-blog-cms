<?php
/**
 * About page.
 *
 * @var string $lang
 */
?>
<div class="container page page--about">
    <article class="page__body">
        <h1 class="page__title"><?= e(__('about_page_title')) ?></h1>
        <p class="page__lead"><?= e(__('about_lead')) ?></p>

        <h2><?= e(__('about_mission_title')) ?></h2>
        <p><?= e(__('about_mission_body')) ?></p>

        <h2><?= e(__('about_editorial_title')) ?></h2>
        <p><?= e(__('about_editorial_body')) ?></p>

        <h2><?= e(__('about_contribute_title')) ?></h2>
        <p>
            <?= e(__('about_contribute_body')) ?>
            <a href="/kayit"><?= e(__('register_as_author')) ?></a>
        </p>
    </article>
</div>
