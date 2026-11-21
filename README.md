# PHP Image Placeholder

Dependency-free placeholder image generator (GD only). Creates solid or gradient images with centred label text — handy for mockups, OG cards, dev fixtures and tests.

## Usage

```php
require 'src/Placeholder.php';

use Azanumer\Placeholder\Placeholder;

$image = Placeholder::create(800, 600, [
    'text'     => 'Hero banner',
    'bg'       => '#1e3a8a',
    'gradient' => '#3b82f6',   // optional vertical gradient
    'fg'       => '#ffffff',
]);

Placeholder::save($image, __DIR__ . '/hero.png');
imagedestroy($image);
```

Or encode in memory:

```php
$png = Placeholder::encode($image, 'png');   // binary string
$jpg = Placeholder::encode($image, 'jpg', 80);
```

## CLI

```bash
php generate.php --width=800 --height=600 --output=hero.png
php generate.php --width=1200 --height=630 --text="Blog header" \
    --bg=#1e3a8a --gradient=#3b82f6 --fg=white --format=jpg --output=header.jpg
```

| Option | Description |
|---|---|
| `--width`, `--height` | Pixels, 1–4000 (required) |
| `--text` | Label text (default `WIDTHxHEIGHT`) |
| `--bg` | Background colour |
| `--gradient` | Second colour for a vertical gradient |
| `--fg` | Text colour (default white) |
| `--font` | Path to a `.ttf` font for sharper, scalable text |
| `--format` | `png`, `jpg` or `webp` (default: from output extension) |
| `--quality` | 0–100 for jpg/webp, 0–9 compression for png (default 85) |
| `--output` | Output file (default `placeholder-WIDTHxHEIGHT.png`) |

## Colours

`#rgb`, `#rrggbb`, `rgb(r, g, b)` or a named colour: black, white, gray/grey, red, green, blue, navy, teal, amber, pink, slate.

```php
Placeholder::parseColor('#f59');        // [255, 85, 153]
Placeholder::parseColor('rgb(1, 2, 3)'); // [1, 2, 3]
Placeholder::parseColor('navy');         // [30, 58, 138]
```

## Examples

`php examples/placeholder-example.php` generates four samples (default, OG card, JPEG thumbnail, wrapped long text) into `examples/output/`.

## Requirements

- PHP 7.4+
- GD extension (WebP output needs a GD build with WebP support)

## License

MIT — see [LICENSE](LICENSE).
