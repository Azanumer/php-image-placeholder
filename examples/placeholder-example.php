<?php

declare(strict_types=1);

/**
 * Example: generate a few placeholder images.
 *
 * Run: php examples/placeholder-example.php
 * Output lands in examples/output/.
 */

require __DIR__ . '/../src/Placeholder.php';

use Azanumer\Placeholder\Placeholder;

$outDir = __DIR__ . '/output';
if (!is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}

$samples = [
    // Default label ("800x600") on the default slate background.
    ['width' => 800, 'height' => 600, 'options' => [], 'file' => 'default.png'],
    // OG-card size with a navy-to-blue gradient and custom text.
    [
        'width'   => 1200,
        'height'  => 630,
        'options' => ['text' => 'Blog header', 'bg' => '#1e3a8a', 'gradient' => '#3b82f6'],
        'file'    => 'og-card.png',
    ],
    // Named colours + rgb() syntax, JPEG output.
    [
        'width'   => 400,
        'height'  => 300,
        'options' => ['text' => 'Thumbnail', 'bg' => 'rgb(220, 38, 38)', 'fg' => 'white'],
        'file'    => 'thumbnail.jpg',
    ],
    // Long text wraps onto several lines with the built-in font.
    [
        'width'   => 300,
        'height'  => 250,
        'options' => ['text' => 'A longer label that wraps', 'bg' => '#0d9488'],
        'file'    => 'wrapped.png',
    ],
];

foreach ($samples as $sample) {
    $image = Placeholder::create($sample['width'], $sample['height'], $sample['options']);
    $path  = $outDir . '/' . $sample['file'];
    Placeholder::save($image, $path);
    imagedestroy($image);
    echo 'Wrote ' . $path . ' (' . filesize($path) . " bytes)\n";
}
