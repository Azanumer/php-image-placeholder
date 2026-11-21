<?php

declare(strict_types=1);

/**
 * CLI: generate a placeholder image.
 *
 * Usage:
 *   php generate.php --width=800 --height=600 --output=hero.png
 *   php generate.php --width=1200 --height=630 --text="Blog header" \
 *       --bg=#1e3a8a --gradient=#3b82f6 --fg=white --format=jpg --output=header.jpg
 *
 * Options:
 *   --width=     pixels (1-4000, required)
 *   --height=    pixels (1-4000, required)
 *   --text=      label text (default "WIDTHxHEIGHT")
 *   --bg=        background colour: #rgb, #rrggbb, rgb(r,g,b) or a name
 *   --gradient=  second colour for a vertical gradient
 *   --fg=        text colour (default white)
 *   --font=      path to a .ttf font for sharper text
 *   --format=    png|jpg|webp (default: from --output extension, else png)
 *   --quality=   0-100 (jpg/webp) or 0-9 compression (png), default 85
 *   --output=    output file (default: placeholder-WIDTHxHEIGHT.png)
 */

require __DIR__ . '/src/Placeholder.php';

use Azanumer\Placeholder\Placeholder;

function cli_usage(): void
{
    fwrite(STDERR, "Usage: php generate.php --width=800 --height=600 [--text=...] [--bg=#64748b] [--gradient=...] [--fg=white] [--font=/path/font.ttf] [--format=png|jpg|webp] [--quality=85] [--output=file.png]\n");
    exit(1);
}

$opts = getopt('', ['width:', 'height:', 'text::', 'bg::', 'gradient::', 'fg::', 'font::', 'format::', 'quality::', 'output::']);

if (empty($opts['width']) || empty($opts['height']) || !ctype_digit((string) $opts['width']) || !ctype_digit((string) $opts['height'])) {
    fwrite(STDERR, "Error: --width and --height are required positive integers.\n");
    cli_usage();
}

$width  = (int) $opts['width'];
$height = (int) $opts['height'];
$output = $opts['output'] ?? "placeholder-{$width}x{$height}.png";
$format = $opts['format'] ?? strtolower(pathinfo($output, PATHINFO_EXTENSION));

$createOptions = [];
if (isset($opts['text'])) {
    $createOptions['text'] = $opts['text'];
}
if (isset($opts['bg'])) {
    $createOptions['bg'] = $opts['bg'];
}
if (isset($opts['gradient'])) {
    $createOptions['gradient'] = $opts['gradient'];
}
if (isset($opts['fg'])) {
    $createOptions['fg'] = $opts['fg'];
}
if (isset($opts['font'])) {
    $createOptions['font'] = $opts['font'];
}

try {
    $image = Placeholder::create($width, $height, $createOptions);
    Placeholder::save($image, $output, $format, isset($opts['quality']) ? (int) $opts['quality'] : 85);
    imagedestroy($image);
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}

$bytes = filesize($output);
echo "Wrote {$output} ({$width}x{$height}, " . number_format((int) $bytes) . " bytes)\n";
