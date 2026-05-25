<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;

/**
 * Generates branded article images (thumbnail, cover, Open Graph) entirely in
 * pure PHP via the GD extension — no system binaries, headless browsers, or
 * external services. Keeps ToolPDF's "zero dependencies" promise intact.
 */
class ArticleImageController extends Controller
{
    /** Output dimensions per variant. */
    private const VARIANTS = [
        'thumbnail' => [600, 315],
        'image'     => [1200, 630],   // cover (also the historical `image` column)
        'og_image'  => [1200, 630],   // social sharing
    ];

    /**
     * Render all variants for a post title.
     *
     * @return array{image:string,thumbnail:string,og_image:string} relative storage paths
     */
    public function generate(string $title, string $language = 'en'): array
    {
        $id = (string) Str::uuid();
        $dir = storage_path('app/public/images/posts');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $paths = [];
        foreach (self::VARIANTS as $variant => [$w, $h]) {
            $relative = 'images/posts/' . $id . '-' . $variant . '.png';
            $this->renderCard($title, $language, $w, $h, storage_path('app/public/' . $relative));
            $paths[$variant] = $relative;
        }

        return $paths;
    }

    /** Pick a bundled TTF font that covers the post's script. */
    private function fontFor(string $language): string
    {
        $bold = public_path('fonts/DejaVuSans-Bold.ttf');

        // Optional per-script overrides — drop the files in to enable.
        $overrides = [
            'zh' => public_path('fonts/NotoSansSC-Bold.ttf'),
            'hi' => public_path('fonts/NotoSansDevanagari-Bold.ttf'),
        ];
        if (isset($overrides[$language]) && is_file($overrides[$language])) {
            return $overrides[$language];
        }

        return is_file($bold) ? $bold : public_path('fonts/DejaVuSans.ttf');
    }

    /** Wrap text to a max pixel width using the actual font metrics. */
    private function wrapText(string $text, string $font, int $size, int $maxWidth): array
    {
        $words = preg_split('/\s+/u', trim($text)) ?: [];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current . ' ' . $word;
            $box = imagettfbbox($size, 0, $font, $candidate);
            $width = abs($box[2] - $box[0]);
            if ($width > $maxWidth && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines ?: [$text];
    }

    private function hex(string $hex): array
    {
        return [
            hexdec(substr($hex, 1, 2)),
            hexdec(substr($hex, 3, 2)),
            hexdec(substr($hex, 5, 2)),
        ];
    }

    private function renderCard(string $title, string $language, int $w, int $h, string $out): void
    {
        $s = $w / 1200.0; // scale factor relative to the 1200px reference
        $font = $this->fontFor($language);

        $im = imagecreatetruecolor($w, $h);
        imagesavealpha($im, true);

        // Vertical brand gradient: #1a1a2e → #0f3460
        [$r1, $g1, $b1] = $this->hex('#1a1a2e');
        [$r2, $g2, $b2] = $this->hex('#0f3460');
        for ($y = 0; $y < $h; $y++) {
            $t = $y / max(1, $h - 1);
            $col = imagecolorallocate(
                $im,
                (int) round($r1 + ($r2 - $r1) * $t),
                (int) round($g1 + ($g2 - $g1) * $t),
                (int) round($b1 + ($b2 - $b1) * $t)
            );
            imageline($im, 0, $y, $w, $y, $col);
        }

        // Soft red radial glow in the top-right corner
        imagealphablending($im, true);
        $glowR = (int) round(460 * $s);
        $cx = (int) round($w - 90 * $s);
        $cy = (int) round(60 * $s);
        $steps = 70;
        for ($i = 0; $i < $steps; $i++) {
            $rad = (int) round($glowR * (1 - $i / $steps));
            $glow = imagecolorallocatealpha($im, 229, 50, 45, 123);
            imagefilledellipse($im, $cx, $cy, $rad, $rad, $glow);
        }

        $red = imagecolorallocate($im, 229, 50, 45);
        $white = imagecolorallocate($im, 255, 255, 255);
        $muted = imagecolorallocate($im, 170, 180, 200);

        // Left accent bar
        imagefilledrectangle($im, 0, 0, (int) round(12 * $s), $h, $red);

        $padX = (int) round(70 * $s);
        $topY = (int) round(70 * $s);

        // Logo + brand wordmark
        $logoSize = (int) round(92 * $s);
        $logoPath = public_path('favicon.png');
        if (is_file($logoPath)) {
            $logo = @imagecreatefrompng($logoPath);
            if ($logo) {
                imagealphablending($im, true);
                imagecopyresampled($im, $logo, $padX, $topY, 0, 0, $logoSize, $logoSize, imagesx($logo), imagesy($logo));
                imagedestroy($logo);
            }
        }
        imagettftext($im, (int) round(30 * $s), 0, $padX + $logoSize + (int) round(22 * $s), $topY + (int) round($logoSize * 0.42), $white, $font, 'ToolPDF');
        imagettftext($im, (int) round(15 * $s), 0, $padX + $logoSize + (int) round(23 * $s), $topY + (int) round($logoSize * 0.78), $muted, $font, 'toolpdf.org');

        // Title — wrapped and auto-shrunk to fit at most 4 lines
        $titleSize = (int) round(54 * $s);
        $minSize = (int) round(28 * $s);
        $maxW = $w - 2 * $padX;
        $lines = $this->wrapText($title, $font, $titleSize, $maxW);
        while (count($lines) > 4 && $titleSize > $minSize) {
            $titleSize -= max(1, (int) round(3 * $s));
            $lines = $this->wrapText($title, $font, $titleSize, $maxW);
        }

        $lineH = (int) round($titleSize * 1.32);
        $blockH = count($lines) * $lineH;
        $regionTop = $topY + $logoSize;
        $startY = (int) round($regionTop + (($h - $regionTop - $blockH) / 2));
        $y = $startY + $titleSize;
        foreach ($lines as $line) {
            imagettftext($im, $titleSize, 0, $padX, $y, $white, $font, $line);
            $y += $lineH;
        }

        // Language badge bottom-right
        $lang = strtoupper($language);
        $badgeSize = (int) round(20 * $s);
        $box = imagettfbbox($badgeSize, 0, $font, $lang);
        $textW = abs($box[2] - $box[0]);
        $bx2 = $w - $padX;
        $by2 = $h - (int) round(46 * $s);
        $padB = (int) round(12 * $s);
        imagefilledrectangle($im, $bx2 - $textW - 2 * $padB, $by2 - $badgeSize - $padB, $bx2, $by2 + $padB, $red);
        imagettftext($im, $badgeSize, 0, $bx2 - $textW - $padB, $by2, $white, $font, $lang);

        imagepng($im, $out);
        imagedestroy($im);
    }
}
