<?php
/**
 * In-content AdSense ad block.
 *
 * Renders a responsive display ad only when an AdSense client id is configured
 * in settings (adsense_client_id). The auto-ads / adsbygoogle.js loader itself
 * is emitted by App\Core\SEO::buildMeta() in the document <head>; this partial
 * only inserts the ad slot and pushes it.
 *
 * Optional variable:
 *   - string $ad_slot : the AdSense ad slot id for this placement (default '').
 *
 * @var string|null $ad_slot
 */

use App\Models\SettingModel;

$adsenseClient = (new SettingModel())->get('adsense_client_id', '');
$adsenseClient = $adsenseClient === null ? '' : trim($adsenseClient);

if ($adsenseClient === '') {
    return;
}

$slot = isset($ad_slot) ? trim((string) $ad_slot) : '';
?>
<aside class="ad-block ad-block--in-content" aria-label="<?= e(__('aria_advertisement')) ?>">
    <ins class="adsbygoogle"
         style="display:block"
         data-ad-client="<?= e($adsenseClient) ?>"
         <?php if ($slot !== ''): ?>data-ad-slot="<?= e($slot) ?>"<?php endif; ?>
         data-ad-format="auto"
         data-full-width-responsive="true"></ins>
    <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
</aside>
