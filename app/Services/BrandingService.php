<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Generates the application brand assets (logo, favicon set, OG image)
 * from the configured application name.
 *
 * Everything is derived deterministically from the name, so re-running the
 * generator for the same name always produces the same artwork.
 */
class BrandingService
{
    /** Directory (inside public/) where generated assets live. */
    public const DIR = 'assets/media/branding';

    /** Candidate bold TTF fonts used to render the monogram in raster output. */
    private const FONT_CANDIDATES = [
        'C:/Windows/Fonts/seguibl.ttf',
        'C:/Windows/Fonts/segoeuib.ttf',
        'C:/Windows/Fonts/arialbd.ttf',
        'C:/Windows/Fonts/calibrib.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
        '/usr/share/fonts/TTF/DejaVuSans-Bold.ttf',
        '/System/Library/Fonts/Supplemental/Arial Bold.ttf',
    ];

    /**
     * Build every brand asset for the given application name.
     *
     * @return array<string,string> map of logical name => public-relative path
     */
    public function generate(string $appName): array
    {
        $appName = trim($appName) !== '' ? trim($appName) : 'App';
        $dir = public_path(self::DIR);

        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new \RuntimeException("Unable to create branding directory: {$dir}");
        }

        $palette = $this->palette($appName);
        $initials = $this->initials($appName);

        // --- Vector ---------------------------------------------------------
        file_put_contents("{$dir}/logo-mark.svg", $this->markSvg($initials, $palette));
        file_put_contents("{$dir}/logo-full.svg", $this->fullSvg($appName, $initials, $palette));

        // --- Raster ---------------------------------------------------------
        $master = $this->renderMark(1024, $initials, $palette);

        foreach ([16, 32, 48, 64, 180, 192, 512] as $size) {
            $name = match ($size) {
                180 => 'apple-touch-icon.png',
                192, 512 => "icon-{$size}.png",
                default => "favicon-{$size}.png",
            };
            $this->savePng($master, $size, "{$dir}/{$name}");
        }

        file_put_contents("{$dir}/favicon.ico", $this->ico($master, [16, 32, 48]));

        $og = $this->renderOgImage($appName, $initials, $palette);
        imagepng($og, "{$dir}/og-image.png", 9);
        imagedestroy($og);

        file_put_contents("{$dir}/site.webmanifest", $this->manifest($appName, $palette));

        imagedestroy($master);

        return [
            'logo' => self::DIR . '/logo-mark.svg',
            'logo_full' => self::DIR . '/logo-full.svg',
            'favicon' => self::DIR . '/favicon.ico',
            'favicon_png' => self::DIR . '/favicon-32.png',
            'apple_touch_icon' => self::DIR . '/apple-touch-icon.png',
            'og_image' => self::DIR . '/og-image.png',
            'manifest' => self::DIR . '/site.webmanifest',
            'theme_color' => $palette['primary'],
        ];
    }

    /** True when a previously generated asset set is present on disk. */
    public function exists(): bool
    {
        return is_file(public_path(self::DIR . '/logo-mark.svg'));
    }

    // ------------------------------------------------------------------
    // Derivation helpers
    // ------------------------------------------------------------------

    /** Up to two uppercase initials taken from the application name. */
    public function initials(string $appName): string
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', $appName, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($words) >= 2) {
            return Str::upper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1));
        }

        $single = $words[0] ?? 'A';

        // "StarterTemp" -> "ST" by picking up the internal capital letters.
        if (preg_match_all('/\p{Lu}/u', $single, $m) && count($m[0]) >= 2) {
            return Str::upper($m[0][0] . $m[0][1]);
        }

        return Str::upper(mb_substr($single, 0, 2));
    }

    /**
     * Deterministic two-stop gradient derived from the name.
     *
     * @return array{primary:string,secondary:string,accent:string}
     */
    public function palette(string $appName): array
    {
        $hue = hexdec(substr(md5(Str::lower($appName)), 0, 4)) % 360;

        return [
            'primary' => $this->hslToHex($hue, 72, 55),
            'secondary' => $this->hslToHex(($hue + 38) % 360, 76, 46),
            'accent' => $this->hslToHex(($hue + 18) % 360, 90, 68),
        ];
    }

    // ------------------------------------------------------------------
    // SVG output
    // ------------------------------------------------------------------

    private function markSvg(string $initials, array $palette): string
    {
        $id = 'g' . substr(md5($initials . $palette['primary']), 0, 6);
        $text = htmlspecialchars($initials, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $fontSize = mb_strlen($initials) > 1 ? 176 : 240;
        $fontStack = "'Plus Jakarta Sans','Segoe UI',system-ui,-apple-system,'Helvetica Neue',Arial,sans-serif";

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="512" height="512" role="img" aria-label="' . $text . '">'
            . '<defs><linearGradient id="' . $id . '" x1="0" y1="0" x2="1" y2="1">'
            . '<stop offset="0" stop-color="' . $palette['primary'] . '"/>'
            . '<stop offset="1" stop-color="' . $palette['secondary'] . '"/>'
            . '</linearGradient></defs>'
            . '<rect width="512" height="512" rx="116" fill="url(#' . $id . ')"/>'
            . '<path d="M256 92 424 190 256 288 88 190Z" fill="#ffffff" fill-opacity=".16"/>'
            . '<text x="256" y="262" text-anchor="middle" dominant-baseline="central" font-family="' . $fontStack . '"'
            . ' font-size="' . $fontSize . '" font-weight="800" letter-spacing="-6" fill="#ffffff">' . $text . '</text>'
            . '</svg>';
    }

    private function fullSvg(string $appName, string $initials, array $palette): string
    {
        $id = 'f' . substr(md5($appName), 0, 6);
        $name = htmlspecialchars($appName, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $text = htmlspecialchars($initials, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $width = 132 + max(1, mb_strlen($appName)) * 28;
        $fontStack = "'Plus Jakarta Sans','Segoe UI',system-ui,-apple-system,Arial,sans-serif";

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $width . ' 128" width="' . $width . '" height="128" role="img" aria-label="' . $name . '">'
            . '<defs><linearGradient id="' . $id . '" x1="0" y1="0" x2="1" y2="1">'
            . '<stop offset="0" stop-color="' . $palette['primary'] . '"/>'
            . '<stop offset="1" stop-color="' . $palette['secondary'] . '"/>'
            . '</linearGradient></defs>'
            . '<rect x="8" y="12" width="104" height="104" rx="24" fill="url(#' . $id . ')"/>'
            . '<path d="M60 32 94 52 60 72 26 52Z" fill="#ffffff" fill-opacity=".18"/>'
            . '<text x="60" y="68" text-anchor="middle" dominant-baseline="central" font-family="' . $fontStack . '"'
            . ' font-size="38" font-weight="800" letter-spacing="-1.5" fill="#ffffff">' . $text . '</text>'
            . '<text x="130" y="68" dominant-baseline="central" font-family="' . $fontStack . '"'
            . ' font-size="42" font-weight="800" letter-spacing="-1" fill="currentColor">' . $name . '</text>'
            . '</svg>';
    }

    private function manifest(string $appName, array $palette): string
    {
        return json_encode([
            'name' => $appName,
            'short_name' => Str::limit($appName, 12, ''),
            'icons' => [
                ['src' => '/' . self::DIR . '/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => '/' . self::DIR . '/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
            ],
            'theme_color' => $palette['primary'],
            'background_color' => '#ffffff',
            'display' => 'standalone',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    // ------------------------------------------------------------------
    // Raster output
    // ------------------------------------------------------------------

    /** Render the square mark at the given edge length (GD truecolor + alpha). */
    private function renderMark(int $size, string $initials, array $palette): \GdImage
    {
        $img = imagecreatetruecolor($size, $size);
        imagesavealpha($img, true);
        imagealphablending($img, false);
        imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));
        imagealphablending($img, true);

        [$r1, $g1, $b1] = $this->hexToRgb($palette['primary']);
        [$r2, $g2, $b2] = $this->hexToRgb($palette['secondary']);

        // Diagonal gradient painted as a fan of 45-degree lines.
        $steps = $size * 2;
        for ($t = 0; $t <= $steps; $t++) {
            $f = $t / $steps;
            $color = imagecolorallocate(
                $img,
                (int) round($r1 + ($r2 - $r1) * $f),
                (int) round($g1 + ($g2 - $g1) * $f),
                (int) round($b1 + ($b2 - $b1) * $f),
            );
            imageline($img, $t, 0, 0, $t, $color);
        }

        $this->roundCorners($img, $size, (int) round($size * 0.2266));

        // Faint facet echoing the SVG mark.
        $veil = imagecolorallocatealpha($img, 255, 255, 255, 107);
        imagefilledpolygon($img, [
            (int) ($size * 0.5), (int) ($size * 0.18),
            (int) ($size * 0.828), (int) ($size * 0.371),
            (int) ($size * 0.5), (int) ($size * 0.5625),
            (int) ($size * 0.172), (int) ($size * 0.371),
        ], $veil);

        $this->drawMonogram($img, $size, $initials);

        return $img;
    }

    /** Punch transparent rounded corners into a square image. */
    private function roundCorners(\GdImage $img, int $size, int $radius): void
    {
        imagealphablending($img, false);
        $clear = imagecolorallocatealpha($img, 0, 0, 0, 127);

        // [start x, start y, arc centre x, arc centre y]
        $corners = [
            [0, 0, $radius, $radius],
            [$size - $radius, 0, $size - $radius - 1, $radius],
            [0, $size - $radius, $radius, $size - $radius - 1],
            [$size - $radius, $size - $radius, $size - $radius - 1, $size - $radius - 1],
        ];

        foreach ($corners as [$ox, $oy, $cx, $cy]) {
            for ($x = $ox; $x < $ox + $radius; $x++) {
                for ($y = $oy; $y < $oy + $radius; $y++) {
                    $d = sqrt(($x - $cx) ** 2 + ($y - $cy) ** 2);

                    if ($d > $radius) {
                        imagesetpixel($img, $x, $y, $clear);
                    } elseif ($d > $radius - 1.5) {
                        // Soften the last pixel ring so downscaling stays clean.
                        $alpha = (int) round((($d - ($radius - 1.5)) / 1.5) * 127);
                        $rgb = imagecolorsforindex($img, imagecolorat($img, $x, $y));
                        imagesetpixel($img, $x, $y, imagecolorallocatealpha(
                            $img,
                            $rgb['red'],
                            $rgb['green'],
                            $rgb['blue'],
                            min(127, max(0, $alpha))
                        ));
                    }
                }
            }
        }

        imagealphablending($img, true);
    }

    /**
     * Draw the initials centred on the mark. Falls back to a geometric
     * "stacked layers" glyph when no usable TTF font is present.
     */
    private function drawMonogram(\GdImage $img, int $size, string $initials): void
    {
        $white = imagecolorallocate($img, 255, 255, 255);
        $font = $this->font();

        if ($font === null) {
            $this->drawLayersGlyph($img, $size, $white);

            return;
        }

        $fontSize = $size * (mb_strlen($initials) > 1 ? 0.30 : 0.42);
        $box = @imagettfbbox($fontSize, 0, $font, $initials);

        if ($box === false) {
            $this->drawLayersGlyph($img, $size, $white);

            return;
        }

        $textWidth = $box[2] - $box[0];
        $textHeight = $box[1] - $box[7];
        $x = (int) round(($size - $textWidth) / 2 - $box[0]);
        $y = (int) round(($size - $textHeight) / 2 - $box[7]);

        @imagettftext($img, $fontSize, 0, $x, $y, $white, $font, $initials);
    }

    /** Font-free fallback mark: three stacked diamonds. */
    private function drawLayersGlyph(\GdImage $img, int $size, int $white): void
    {
        $soft = imagecolorallocatealpha($img, 255, 255, 255, 50);

        foreach ([[0.62, $soft], [0.50, $soft], [0.36, $white]] as [$cy, $color]) {
            imagefilledpolygon($img, [
                (int) ($size * 0.5), (int) ($size * ($cy - 0.10)),
                (int) ($size * 0.76), (int) ($size * $cy),
                (int) ($size * 0.5), (int) ($size * ($cy + 0.10)),
                (int) ($size * 0.24), (int) ($size * $cy),
            ], $color);
        }
    }

    /** First readable bold TTF from the candidate list, or null. */
    private function font(): ?string
    {
        if (! function_exists('imagettftext')) {
            return null;
        }

        foreach (self::FONT_CANDIDATES as $path) {
            if (is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    /** Downscale the master mark and write it out as PNG. */
    private function savePng(\GdImage $master, int $size, string $path): void
    {
        $out = imagecreatetruecolor($size, $size);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));
        imagecopyresampled($out, $master, 0, 0, 0, 0, $size, $size, imagesx($master), imagesy($master));
        imagepng($out, $path, 9);
        imagedestroy($out);
    }

    /** Assemble a PNG-based .ico container for the given sizes. */
    private function ico(\GdImage $master, array $sizes): string
    {
        $images = [];

        foreach ($sizes as $size) {
            $tmp = tempnam(sys_get_temp_dir(), 'ico');
            $this->savePng($master, $size, $tmp);
            $images[$size] = (string) file_get_contents($tmp);
            @unlink($tmp);
        }

        $header = pack('vvv', 0, 1, count($images));
        $offset = 6 + 16 * count($images);
        $entries = '';
        $body = '';

        foreach ($images as $size => $png) {
            $entries .= pack(
                'CCCCvvVV',
                $size >= 256 ? 0 : $size,
                $size >= 256 ? 0 : $size,
                0,
                0,
                1,
                32,
                strlen($png),
                $offset
            );
            $offset += strlen($png);
            $body .= $png;
        }

        return $header . $entries . $body;
    }

    /** 1200x630 Open Graph card: gradient ground, mark, and the app name. */
    private function renderOgImage(string $appName, string $initials, array $palette): \GdImage
    {
        $w = 1200;
        $h = 630;
        $img = imagecreatetruecolor($w, $h);
        imagealphablending($img, true);
        imagesavealpha($img, true);

        [$r1, $g1, $b1] = $this->hexToRgb($palette['primary']);
        [$r2, $g2, $b2] = $this->hexToRgb($palette['secondary']);

        for ($x = 0; $x < $w; $x++) {
            $f = $x / $w;
            imageline($img, $x, 0, $x, $h, imagecolorallocate(
                $img,
                (int) round($r1 + ($r2 - $r1) * $f),
                (int) round($g1 + ($g2 - $g1) * $f),
                (int) round($b1 + ($b2 - $b1) * $f),
            ));
        }

        // Soft decorative facets.
        $veil = imagecolorallocatealpha($img, 255, 255, 255, 118);
        imagefilledpolygon($img, [1200, 0, 1200, 400, 820, 0], $veil);
        imagefilledpolygon($img, [0, 630, 0, 300, 460, 630], $veil);

        $mark = $this->renderMark(512, $initials, $palette);
        imagecopyresampled($img, $mark, 96, 175, 0, 0, 280, 280, 512, 512);
        imagedestroy($mark);

        $font = $this->font();

        if ($font !== null) {
            $white = imagecolorallocate($img, 255, 255, 255);
            $muted = imagecolorallocatealpha($img, 255, 255, 255, 40);
            @imagettftext($img, 62, 0, 428, 315, $white, $font, Str::limit($appName, 18, ''));
            @imagettftext($img, 26, 0, 432, 372, $muted, $font, 'Admin Dashboard');
        }

        return $img;
    }

    // ------------------------------------------------------------------
    // Colour utilities
    // ------------------------------------------------------------------

    /** @return array{0:int,1:int,2:int} */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    private function hslToHex(float $h, float $s, float $l): string
    {
        $s /= 100;
        $l /= 100;
        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
        $m = $l - $c / 2;

        [$r, $g, $b] = match (true) {
            $h < 60 => [$c, $x, 0.0],
            $h < 120 => [$x, $c, 0.0],
            $h < 180 => [0.0, $c, $x],
            $h < 240 => [0.0, $x, $c],
            $h < 300 => [$x, 0.0, $c],
            default => [$c, 0.0, $x],
        };

        return sprintf(
            '#%02x%02x%02x',
            (int) round(($r + $m) * 255),
            (int) round(($g + $m) * 255),
            (int) round(($b + $m) * 255),
        );
    }
}
