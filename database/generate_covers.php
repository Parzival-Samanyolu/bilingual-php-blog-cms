<?php

declare(strict_types=1);

/**
 * Generates a real, unique branded cover image for every published article that
 * has no cover, plus the default OG share image. Each cover renders the article
 * title over a category-coloured gradient with the site wordmark — the same
 * "social card" technique used by dev.to / GitHub, so no generic placeholders
 * remain. Output is static WebP written to public/uploads/gen/ (the host needs
 * no fonts; images are produced here).
 *
 * Usage: php database/generate_covers.php
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}
if (!function_exists('imagettftext') || !function_exists('imagewebp')) {
    fwrite(STDERR, "GD with FreeType + WebP is required.\n");
    exit(1);
}

$root = dirname(__DIR__);

/* --- Font (bold + regular) ------------------------------------------------ */
$fontBold = null;
foreach ([
    '/System/Library/Fonts/Supplemental/Arial Bold.ttf',
    '/Library/Fonts/Arial Bold.ttf',
    '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
] as $f) {
    if (is_file($f)) { $fontBold = $f; break; }
}
if ($fontBold === null) {
    fwrite(STDERR, "No usable bold TTF font found.\n");
    exit(1);
}

/* --- .env / MySQL --------------------------------------------------------- */
$env = [];
foreach (@file($root . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) { continue; }
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v, " \t\"'");
}
$pdo = new PDO(
    "mysql:host=" . ($env['DB_HOST'] ?? '127.0.0.1') . ";dbname=" . ($env['DB_NAME'] ?? '') . ";charset=utf8mb4",
    $env['DB_USER'] ?? 'root',
    $env['DB_PASS'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

/* --- Category palette (base colour per slug) ------------------------------ */
$palette = [
    'saglik' => [16, 185, 129], 'egitim' => [99, 102, 241], 'genel-kultur' => [139, 92, 246],
    'ekonomi' => [245, 158, 11], 'yasam' => [236, 72, 153], 'otomotiv' => [239, 68, 68],
    'canlilar' => [20, 184, 166], 'kadinca' => [217, 70, 239], 'internet' => [59, 130, 246],
    'teknoloji' => [37, 99, 235], 'bankalar' => [14, 165, 233], 'doviz' => [34, 197, 94],
    'emtialar' => [234, 179, 8],
];
$default = [37, 99, 235];

$outDir = $root . '/public/uploads/gen';
if (!is_dir($outDir)) { mkdir($outDir, 0755, true); }

/* --- Helpers -------------------------------------------------------------- */
$wrap = static function (string $text, string $font, float $size, int $maxW): array {
    $words = preg_split('/\s+/', trim($text)) ?: [];
    $lines = [];
    $cur = '';
    foreach ($words as $w) {
        $try = $cur === '' ? $w : $cur . ' ' . $w;
        $bb = imagettfbbox($size, 0, $font, $try);
        $width = abs($bb[2] - $bb[0]);
        if ($width > $maxW && $cur !== '') {
            $lines[] = $cur;
            $cur = $w;
        } else {
            $cur = $try;
        }
    }
    if ($cur !== '') { $lines[] = $cur; }
    return $lines;
};

/**
 * Render one card. Returns the WebP bytes.
 */
$render = static function (int $w, int $h, array $base, string $category, string $title) use ($fontBold, $wrap): string {
    $img = imagecreatetruecolor($w, $h);

    // Vertical gradient: base (top) -> ~55% darker (bottom).
    for ($y = 0; $y < $h; $y++) {
        $t = $y / $h;
        $r = (int) ($base[0] * (1 - 0.45 * $t));
        $g = (int) ($base[1] * (1 - 0.45 * $t));
        $b = (int) ($base[2] * (1 - 0.45 * $t));
        $col = imagecolorallocate($img, $r, $g, $b);
        imageline($img, 0, $y, $w, $y, $col);
    }

    // Soft brand watermark circle, lower-right.
    $white = imagecolorallocate($img, 255, 255, 255);
    $wmA = imagecolorallocatealpha($img, 255, 255, 255, 118);
    imagefilledellipse($img, (int) ($w * 0.9), (int) ($h * 0.92), (int) ($w * 0.5), (int) ($w * 0.5), $wmA);

    $scale = $w / 640.0; // layout tuned at 640px, scaled for OG size
    $marginX = (int) (40 * $scale);

    // Category label (uppercase).
    $catSize = 13 * $scale;
    $cat = mb_strtoupper($category);
    $catColor = imagecolorallocatealpha($img, 255, 255, 255, 40);
    imagettftext($img, $catSize, 0, $marginX, (int) (52 * $scale), $catColor, $fontBold, $cat);

    // Title, wrapped, vertically centred in the mid band.
    $titleSize = 30 * $scale;
    $maxW = $w - 2 * $marginX;
    $lines = $wrap($title, $fontBold, $titleSize, $maxW);
    $lines = array_slice($lines, 0, 4);
    $lineH = (int) ($titleSize * 1.32);
    $blockH = count($lines) * $lineH;
    $startY = (int) (($h - $blockH) / 2 + $titleSize);
    $shadow = imagecolorallocatealpha($img, 0, 0, 0, 90);
    foreach ($lines as $i => $line) {
        $y = $startY + $i * $lineH;
        imagettftext($img, $titleSize, 0, $marginX + 1, $y + 1, $shadow, $fontBold, $line);
        imagettftext($img, $titleSize, 0, $marginX, $y, $white, $fontBold, $line);
    }

    // Wordmark bottom-left.
    $wmSize = 15 * $scale;
    $wmColor = imagecolorallocatealpha($img, 255, 255, 255, 30);
    imagettftext($img, $wmSize, 0, $marginX, $h - (int) (26 * $scale), $wmColor, $fontBold, 'real.com.tr');

    ob_start();
    imagewebp($img, null, 86);
    $bytes = (string) ob_get_clean();
    imagedestroy($img);
    return $bytes;
};

/* --- Generate article covers --------------------------------------------- */
$rows = $pdo->query(
    "SELECT a.id, c.slug AS cat_slug, COALESCE(ct.name, c.slug) AS cat_name, t.title
     FROM articles a
     JOIN categories c ON c.id = a.category_id
     LEFT JOIN category_translations ct ON ct.category_id = c.id AND ct.lang = 'tr'
     JOIN article_translations t ON t.article_id = a.id AND t.lang = 'tr'
     WHERE a.cover_image IS NULL OR a.cover_image = ''"
)->fetchAll();

echo 'Generating ' . count($rows) . " article covers...\n";
$upd = $pdo->prepare("UPDATE articles SET cover_image = ? WHERE id = ?");
$n = 0;
foreach ($rows as $r) {
    $base = $palette[$r['cat_slug']] ?? $default;
    $bytes = $render(640, 360, $base, (string) $r['cat_name'], (string) $r['title']);
    $rel = '/uploads/gen/' . $r['id'] . '.webp';
    file_put_contents($root . '/public' . $rel, $bytes);
    $upd->execute([$rel, $r['id']]);
    if (++$n % 100 === 0) { echo "  {$n}...\n"; }
}
echo "  {$n} covers written.\n";

/* --- Default OG share image (1200x630) ----------------------------------- */
$og = $render(1200, 630, $default, 'real.com.tr', 'Bilgiye açılan kapınız');
if (!is_dir($root . '/public/img')) { mkdir($root . '/public/img', 0755, true); }
file_put_contents($root . '/public/img/og-default.jpg', $og); // WebP bytes; renamed below
// Save a real JPEG version so the og:image content-type matches.
$ogImg = imagecreatefromstring($og);
imagejpeg($ogImg, $root . '/public/img/og-default.jpg', 88);
imagedestroy($ogImg);
echo "OG default image written.\n";

echo "Done.\n";
