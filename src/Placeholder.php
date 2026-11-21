<?php

declare(strict_types=1);

namespace Azanumer\Placeholder;

/**
 * Dependency-free placeholder image generator (GD only).
 *
 * Creates a solid or vertically-graduated image with centred label text,
 * e.g. "800x600", in PNG, JPEG or WebP. Built-in GD bitmap fonts are used
 * by default; pass a TrueType font path for sharper, scalable text.
 *
 * Example:
 *   $img = Placeholder::create(800, 600, ['text' => 'Hero banner']);
 *   Placeholder::savePng($img, __DIR__ . '/hero.png');
 */
final class Placeholder
{
    /** @var array<string, array{int, int, int}> Small named-colour map. */
    private const NAMED_COLORS = [
        'black' => [0, 0, 0],
        'white' => [255, 255, 255],
        'gray'  => [128, 128, 128],
        'grey'  => [128, 128, 128],
        'red'   => [220, 38, 38],
        'green' => [34, 197, 94],
        'blue'  => [37, 99, 235],
        'navy'  => [30, 58, 138],
        'teal'  => [13, 148, 136],
        'amber' => [245, 158, 11],
        'pink'  => [236, 72, 153],
        'slate' => [100, 116, 139],
    ];

    public const MAX_DIMENSION = 4000;

    /**
     * @param int   $width   1..4000
     * @param int   $height  1..4000
     * @param array $options text, bg, fg, gradient (bg2), font (TTF path), format
     *
     * @throws \InvalidArgumentException on bad dimensions or colours.
     * @throws \RuntimeException when GD is missing.
     */
    public static function create(int $width, int $height, array $options = [])
    {
        if (!\extension_loaded('gd')) {
            throw new \RuntimeException('The GD extension is required.');
        }
        if ($width < 1 || $width > self::MAX_DIMENSION || $height < 1 || $height > self::MAX_DIMENSION) {
            throw new \InvalidArgumentException(
                'Width and height must be between 1 and ' . self::MAX_DIMENSION . '.'
            );
        }

        $text = $options['text'] ?? $width . 'x' . $height;
        $bg   = self::parseColor($options['bg'] ?? '#64748b');
        $fg   = self::parseColor($options['fg'] ?? '#ffffff');

        $image = imagecreatetruecolor($width, $height);
        if (false === $image) {
            throw new \RuntimeException('Could not create an image canvas.');
        }

        if (isset($options['gradient'])) {
            $bg2 = self::parseColor($options['gradient']);
            self::fillGradient($image, $width, $height, $bg, $bg2);
        } else {
            $color = imagecolorallocate($image, $bg[0], $bg[1], $bg[2]);
            imagefill($image, 0, 0, $color);
        }

        $fgColor = imagecolorallocate($image, $fg[0], $fg[1], $fg[2]);
        self::drawCentredText($image, $width, $height, (string) $text, $fgColor, $options['font'] ?? null);

        return $image;
    }

    /**
     * Parse '#rgb', '#rrggbb', 'rgb(r, g, b)' or a named colour into [r, g, b].
     *
     * @throws \InvalidArgumentException on an unrecognised colour.
     */
    public static function parseColor(string $color): array
    {
        $color = strtolower(trim($color));

        if (isset(self::NAMED_COLORS[$color])) {
            return self::NAMED_COLORS[$color];
        }

        if (preg_match('/^#([0-9a-f]{3})$/', $color, $m)) {
            return [
                (int) hexdec($m[1][0] . $m[1][0]),
                (int) hexdec($m[1][1] . $m[1][1]),
                (int) hexdec($m[1][2] . $m[1][2]),
            ];
        }

        if (preg_match('/^#([0-9a-f]{6})$/', $color, $m)) {
            return [
                (int) hexdec(substr($m[1], 0, 2)),
                (int) hexdec(substr($m[1], 2, 2)),
                (int) hexdec(substr($m[1], 4, 2)),
            ];
        }

        if (preg_match('/^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/', $color, $m)) {
            $rgb = [(int) $m[1], (int) $m[2], (int) $m[3]];
            foreach ($rgb as $channel) {
                if ($channel > 255) {
                    throw new \InvalidArgumentException('RGB channel out of range in "' . $color . '".');
                }
            }
            return $rgb;
        }

        throw new \InvalidArgumentException('Unrecognised colour: "' . $color . '".');
    }

    /** Fill with a vertical gradient from $top to $bottom. */
    private static function fillGradient($image, int $width, int $height, array $top, array $bottom): void
    {
        for ($y = 0; $y < $height; $y++) {
            $t = $height > 1 ? $y / ($height - 1) : 0;
            $r = (int) round($top[0] + ($bottom[0] - $top[0]) * $t);
            $g = (int) round($top[1] + ($bottom[1] - $top[1]) * $t);
            $b = (int) round($top[2] + ($bottom[2] - $top[2]) * $t);
            $color = imagecolorallocate($image, $r, $g, $b);
            imageline($image, 0, $y, $width - 1, $y, $color);
        }
    }

    /**
     * Draw text centred on the canvas. With a TTF font the size scales to
     * fit the width; otherwise the largest built-in GD font (5) is used and
     * long text is wrapped onto several lines.
     */
    private static function drawCentredText($image, int $width, int $height, string $text, int $fgColor, ?string $font): void
    {
        $text = trim($text);
        if ('' === $text) {
            return;
        }

        if (null !== $font && is_readable($font)) {
            self::drawTtfText($image, $width, $height, $text, $fgColor, $font);
            return;
        }

        // Built-in bitmap font 5: 9px wide, 15px tall per character.
        $fontWidth  = imagefontwidth(5);
        $fontHeight = imagefontheight(5);
        $maxChars   = max(1, (int) floor(($width - 16) / $fontWidth));
        $lines      = self::wrapWords($text, $maxChars);
        $totalH     = count($lines) * $fontHeight;
        $y          = (int) (($height - $totalH) / 2);

        foreach ($lines as $line) {
            $x = (int) (($width - strlen($line) * $fontWidth) / 2);
            imagestring($image, 5, max(0, $x), $y, $line, $fgColor);
            $y += $fontHeight;
        }
    }

    private static function drawTtfText($image, int $width, int $height, string $text, int $fgColor, string $font): void
    {
        // Start large, shrink until the text fits (with 8% side margins).
        $size = (int) ($width / max(1, strlen($text)) * 1.6);
        $size = max(10, min(200, $size));
        while ($size > 10) {
            $box = imagettfbbox($size, 0, $font, $text);
            $textWidth = $box[2] - $box[0];
            if ($textWidth <= $width * 0.92) {
                break;
            }
            $size -= 2;
        }
        $box       = imagettfbbox($size, 0, $font, $text);
        $textWidth = $box[2] - $box[0];
        $textHeight = $box[1] - $box[7];
        $x = (int) (($width - $textWidth) / 2 - $box[0]);
        $y = (int) (($height + $textHeight) / 2 - $box[7]);
        imagettftext($image, $size, 0, $x, $y, $fgColor, $font, $text);
    }

    /** Word-wrap a string into lines no longer than $maxChars. */
    private static function wrapWords(string $text, int $maxChars): array
    {
        $lines   = [];
        $current = '';
        foreach (explode(' ', $text) as $word) {
            // Hard-split a single word that is wider than the canvas.
            while (strlen($word) > $maxChars) {
                if ('' !== $current) {
                    $lines[] = $current;
                    $current = '';
                }
                $lines[] = substr($word, 0, $maxChars);
                $word    = substr($word, $maxChars);
            }
            $candidate = '' === $current ? $word : $current . ' ' . $word;
            if (strlen($candidate) <= $maxChars) {
                $current = $candidate;
            } else {
                $lines[] = $current;
                $current = $word;
            }
        }
        if ('' !== $current) {
            $lines[] = $current;
        }
        return $lines;
    }

    /**
     * Encode the image to a binary string.
     *
     * @param string $format png|jpg|jpeg|webp
     * @param int    $quality 0-100 for jpg/webp, 0-9 for png compression.
     */
    public static function encode($image, string $format = 'png', int $quality = 85): string
    {
        $format = strtolower($format);
        ob_start();
        switch ($format) {
            case 'png':
                imagepng($image, null, max(0, min(9, (int) round($quality / 10))));
                break;
            case 'jpg':
            case 'jpeg':
                imagejpeg($image, null, max(0, min(100, $quality)));
                break;
            case 'webp':
                if (!\function_exists('imagewebp')) {
                    ob_end_clean();
                    throw new \RuntimeException('WebP output is not supported by this GD build.');
                }
                imagewebp($image, null, max(0, min(100, $quality)));
                break;
            default:
                ob_end_clean();
                throw new \InvalidArgumentException('Unsupported format "' . $format . '" (png, jpg, webp).');
        }
        $data = ob_get_clean();
        return false === $data ? '' : $data;
    }

    /** Save the image to a file (format guessed from the extension). */
    public static function save($image, string $path, ?string $format = null, int $quality = 85): void
    {
        $format = $format ?? strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $data   = self::encode($image, $format, $quality);
        if (false === file_put_contents($path, $data)) {
            throw new \RuntimeException('Could not write to "' . $path . '".');
        }
    }
}
