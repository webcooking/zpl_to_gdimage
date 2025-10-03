# ZPL to GDImage Converter

A PHP library to convert ZPL (Zebra Programming Language) content into images. **Generates SVG output** with support for TrueType fonts and GDImage conversion.

## Features

This library provides **3 levels of ZPL conversion**:

- **ZPL → SVG** (`ZplToSvg`) - Vector rendering with TrueType fonts
- **ZPL → Imagick** (`ZplToImagick`) - Rasterization via rsvg-convert or Imagick 
- **ZPL → GDImage** (`ZplToGdImage`) - PNG/JPEG export

## Requirements

- PHP 8.0 or higher
- GD extension enabled
- **Imagick extension** (for SVG to GDImage conversion)
  - Install: `pecl install imagick`
  - Or: `apt-get install php-imagick` (Linux)
  - Or: `brew install imagemagick` + `pecl install imagick` (macOS)
- **rsvg-convert** (optional but **highly recommended** for reliable rasterization)
  - Install: `apt-get install librsvg2-bin` (Linux)
  - Or: `brew install librsvg` (macOS)
  - Automatically used when available, provides better SVG compatibility

## Installation

Clone the repository and run Composer install:

```bash
git clone https://github.com/webcooking/zpl_to_gdimage.git
cd zpl_to_gdimage
composer install
```

## Usage

### Method 1: Generate SVG (Recommended)

```php
use Webcooking\ZplToGdImage\ZplToSvg;

$zplContent = file_get_contents('path/to/your/label.zpl');

// Default: Noto Sans font
$svgContent = ZplToSvg::convert($zplContent);

// Use IBM VGA font
$svgContent = ZplToSvg::convert($zplContent, 4.0, 6.0, 300, 'ibm-vga');

// Use your own custom font
$svgContent = ZplToSvg::convert($zplContent, 4.0, 6.0, 300, '/path/to/your/font.ttf');

// Save to file
file_put_contents('output.svg', $svgContent);

// View SVG in browser or convert to PNG with ImageMagick:
// convert -density 300 -background white output.svg output.png
```

### Available Fonts

**Built-in fonts:**
- `'noto'` - Noto Sans - **DEFAULT**
- `'ibm-vga'` - IBM VGA 8x16

**Custom fonts:**
- Provide a path to any `.ttf` file: `'/path/to/your/font.ttf'`

### Method 2: Convert to GDImage (Raster with Imagick)

```php
use Webcooking\ZplToGdImage\ZplToGdImage;

$zplContent = file_get_contents('path/to/your/label.zpl');

// Convert to GDImage with default font (Noto Sans)
$image = ZplToGdImage::convert($zplContent);
imagepng($image, 'output.png');
imagedestroy($image);

// With custom parameters and font
$image = ZplToGdImage::convert(
    $zplContent,
    4.0,           // width in inches
    6.0,           // height in inches
    300,           // DPI
    'ibm-vga'      // font: 'noto', 'ibm-vga', or '/path/to/font.ttf'
);
imagepng($image, 'output.png');
imagedestroy($image);
```

### Method 3: Direct File Export (PNG/JPEG)

```php
use Webcooking\ZplToGdImage\ZplToGdImage;

$zplContent = file_get_contents('path/to/your/label.zpl');

// Save directly as PNG (best quality)
ZplToGdImage::toPng(
    $zplContent,
    'output.png',  // output path
    4.0,           // width in inches
    6.0,           // height in inches
    300,           // DPI
    'noto',        // font renderer
    9              // PNG compression (0-9, 9=max)
);

// Save directly as JPEG
ZplToGdImage::toJpeg(
    $zplContent,
    'output.jpg',  // output path
    4.0,           // width in inches
    6.0,           // height in inches
    300,           // DPI
    'noto',        // font renderer
    90             // JPEG quality (0-100)
);

// Just get SVG string (no rasterization)
$svg = ZplToGdImage::toSvg($zplContent, 4.0, 6.0, 300, 'noto');
file_put_contents('output.svg', $svg);
```

### Method 4: Get an Imagick object directly

If you prefer to work with Imagick directly (for advanced processing, composites or filters), use `ZplToImagick` which returns an `Imagick` object.

```php
use Webcooking\ZplToGdImage\ZplToImagick;

$imagick = ZplToImagick::convert($zplContent, 4.0, 6.0, 300, 'noto');
// Save as PNG/JPEG using Imagick methods
$imagick->writeImage('output_imagick.png');
$imagick->setImageFormat('jpeg');
$imagick->setImageCompressionQuality(90);
$imagick->writeImage('output_imagick.jpg');
$imagick->clear();
$imagick->destroy();
```

**Architecture Note**: `ZplToGdImage` now uses `ZplToImagick::convert()` directly, which handles both SVG generation and rasterization. This automatically benefits from rsvg-convert when available.

The `ZplToImagick` class is useful when you want to manipulate the result with Imagick APIs (filters, composites, etc.) before converting to GD or saving to disk.

### Parameters

- `$zpl` (string): ZPL content to convert
- `$widthInches` (float): Label width in inches (default: 4.0)
- `$heightInches` (float): Label height in inches (default: 6.0)
- `$dpi` (int): Dots per inch - 203, 300, or 600 (default: 300)
- `$fontRenderer` (string): Font to use - 'noto', 'ibm-vga', or path to TTF file (default: 'noto')
- `$quality` (int): Compression quality for PNG (0-9) or JPEG (0-100)

These parameters provide consistent ZPL rendering.

## Examples

The `examples/` folder contains ready-to-use ZPL files and test scripts.

### Testing Examples

**Test a specific example:**
```bash
# Test with default font (Noto)
php examples/test.php simple_label.zpl

# Test with specific font
php examples/test.php shipping_label.zpl noto
php examples/test.php product_tag.zpl ibm-vga
php examples/test.php name_badge.zpl /path/to/custom.ttf
```

**Test all examples at once:**
```bash
# Test all with Noto Sans (default)
php examples/test_all.php

# Test all with IBM VGA
php examples/test_all.php ibm-vga

# Test all with custom font
php examples/test_all.php /path/to/custom.ttf
```

Output files are created as `examples/output_*.svg` and `examples/output_*.jpg`.

### Docker Testing

A Docker environment is provided for consistent testing with all dependencies:

```bash
# Build and run tests in Docker
./docker-build-and-run.sh

# Or manually:
docker build -t php-imagick-gd .
docker run --rm -v "$(pwd):/workspace" php-imagick-gd bash -c "cd /workspace && php examples/test_all.php"
```

The Docker environment includes `rsvg-convert` for reliable rasterization, solving potential blank image issues.

### Font Comparison

| Font | Style | File Size |
|------|-------|-----------|
| **Noto Sans** | Modern, clean | ~1.9 MB |
| **IBM VGA** | Retro, pixel style | ~35 KB |

### Creating Your Own Examples

1. Create a `.zpl` file in the `examples/` folder
2. Use standard ZPL commands:
   - `^XA` ... `^XZ` - Label start/end
   - `^FO x,y` - Field position
   - `^A0N,h,w` - Font size (height, width)
   - `^FD text ^FS` - Field data (text)
   - `^GB w,h,t` - Graphic box (line or rectangle)
   - `^BCN,h` - Code 128 barcode
   - `^FR` - Reverse mode (white on black)

3. Test it: `php examples/test.php your_file.zpl`

## Testing

Run the example test scripts:

```bash
# Test a specific ZPL file
php examples/test.php simple_label.zpl

# Test all examples
php examples/test_all.php

# Test with different fonts
php examples/test_all.php ibm-vga
```

See the `examples/` folder for more details.

## Supported ZPL Commands

Currently supports:
- `^A0N`: Font settings (height, width)
- `^FO`: Field origin (position x,y)
- `^FN`: Field number (for data association)
- `^FD`: Field data (text content)
- `^FR`: Field reverse (white text on black)
- `^GB`: Graphic box (rectangles, lines, filled boxes)
- `^BCN`: Barcode Code 128

More commands can be added as needed.

## Troubleshooting

### Issue: Blank/White JPG Images

**Problem:** Generated JPEG/PNG images appear completely white or blank, even though SVG output is correct.

**Cause:** Some Imagick installations don't properly handle SVG files with embedded fonts (data URLs in @font-face rules).

**Solution:** The library detects and uses `rsvg-convert` when available. All classes automatically use the best available renderer:

1. **Install rsvg-convert** (recommended):
   ```bash
   # Ubuntu/Debian
   sudo apt-get install librsvg2-bin
   
   # CentOS/RHEL
   sudo yum install librsvg2-tools
   
   # macOS
   brew install librsvg
   ```

2. **Docker setup** (if using containerization):
   ```dockerfile
   RUN apt-get update && apt-get install -y librsvg2-bin librsvg2-dev
   ```

3. **Verify installation:**
   ```bash
   which rsvg-convert  # Should show path to binary
   rsvg-convert --version  # Should show version info
   ```

When `rsvg-convert` is available, the library automatically uses it for SVG rasterization, providing much better compatibility with embedded fonts and complex SVG elements. If `rsvg-convert` is not available, it falls back to direct Imagick conversion.

**How automatic fallback works:**

1. **With `rsvg-convert`** (recommended):
   ```php
   $image = ZplToGdImage::convert($zpl, 4, 6, 300); // Uses rsvg-convert automatically
   ```

2. **Without `rsvg-convert`** (fallback):
   ```php  
   $image = ZplToGdImage::convert($zpl, 4, 6, 300); // Falls back to Imagick (may produce white images)
   ```

**Transparent integration:** Since the recent optimization, `ZplToGdImage` calls `ZplToImagick::convert()` directly which automatically handles choosing the best renderer. No need to generate SVG separately.

All methods benefit from this automatic detection:
- `ZplToGdImage::convert()` → GDImage object (automatically uses rsvg-convert via ZplToImagick)
- `ZplToGdImage::toPng()` → PNG file (automatically uses rsvg-convert via ZplToImagick)
- `ZplToGdImage::toJpeg()` → JPEG file (automatically uses rsvg-convert via ZplToImagick)
- `ZplToImagick::convert()` → Imagick object (automatic rsvg-convert detection or Imagick fallback)

**Testing the fix:**
```bash
# Test SVG generation (should always work)
php examples/test.php simple_label.zpl

# Check if JPG contains content (not all white)
identify -verbose examples/output_simple_label.jpg | grep "Colors:"
# Should show "Colors: 256" or similar (not "Colors: 1")
```

## Resources

- [Zebra Programming Guide (PDF)](https://www.zebra.com/content/dam/zebra/manuals/printers/common/programming/zpl-zbi2-pm-en.pdf)

## License

**Project**: LGPL-3.0-or-later

This library is free software; you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.

**Fonts**:
- **Noto Sans**: SIL Open Font License 1.1 (see `fonts/Noto_Sans/OFL.txt`)
- **IBM VGA**: Creative Commons CC BY-SA 4.0 by VileR (int10h.org) (see `fonts/IBM_VGA/LICENSE`)

## Support

If you use this package in a commercial or paid project and find it helpful, I'd be grateful if you considered offering a small token of thanks : https://ko-fi.com/nuranto

No pressure — this is only if the library saved you time and you feel like supporting future work.