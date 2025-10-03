<?php

/**
 * Webcooking ZPL to Imagick
 *
 * Copyright (c) 2025 Vincent Enjalbert
 * Licensed under LGPL-3.0-or-later. See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Webcooking\ZplToGdImage;

use Imagick;
use ImagickException;

/**
 * Class ZplToImagick
 *
 * Provides methods to rasterize ZPL -> SVG -> Imagick objects.
 */
class ZplToImagick
{
    /**
     * Rasterize an SVG string into an Imagick object.
     *
     * @param string $svgContent
     * @param int $width Target width in pixels
     * @param int $height Target height in pixels
     * @param int $dpi Resolution (DPI)
     * @return Imagick
     * @throws \RuntimeException
     */
    public static function fromSvg(string $svgContent, int $widthPixels, int $heightPixels, int $dpi): \Imagick
    {
        // Try rsvg-convert first if available (more reliable for complex SVGs)
        if (self::isRsvgConvertAvailable()) {
            return self::fromSvgUsingRsvgConvert($svgContent, $widthPixels, $heightPixels, $dpi);
        }
        
        // Fallback to direct Imagick conversion
        $imagick = new \Imagick();
        
        // Set density for proper rendering
        $imagick->setResolution($dpi, $dpi);
        
        // Read SVG content
        $imagick->readImageBlob($svgContent);
        
        // Resize to exact dimensions if needed
        $imagick->resizeImage($widthPixels, $heightPixels, \Imagick::FILTER_LANCZOS, 1);
        
        // Flatten the image (remove transparency)
        $imagick->setImageBackgroundColor('white');
        $imagick = $imagick->flattenImages();
        
        return $imagick;
    }
    
    private static function isRsvgConvertAvailable(): bool
    {
        static $available = null;
        if ($available === null) {
            $output = [];
            $returnCode = 0;
            exec('which rsvg-convert 2>/dev/null', $output, $returnCode);
            $available = ($returnCode === 0);
        }
        return $available;
    }
    
    private static function fromSvgUsingRsvgConvert(string $svgContent, int $widthPixels, int $heightPixels, int $dpi): \Imagick
    {
        // Create temporary file for SVG
        $svgTempFile = tempnam(sys_get_temp_dir(), 'zpl_svg_') . '.svg';
        file_put_contents($svgTempFile, $svgContent);
        
        try {
            // Create temporary file for PNG output
            $pngTempFile = tempnam(sys_get_temp_dir(), 'zpl_png_') . '.png';
            
            // Use rsvg-convert to render SVG to PNG
            $command = sprintf(
                'rsvg-convert --width=%d --height=%d --dpi-x=%d --dpi-y=%d --format=png --output=%s %s 2>&1',
                $widthPixels,
                $heightPixels,
                $dpi,
                $dpi,
                escapeshellarg($pngTempFile),
                escapeshellarg($svgTempFile)
            );
            
            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0 || !file_exists($pngTempFile)) {
                throw new \RuntimeException('rsvg-convert failed: ' . implode("\n", $output));
            }
            
            // Load PNG into Imagick
            $imagick = new \Imagick();
            $imagick->readImage($pngTempFile);
            
            // Cleanup
            unlink($pngTempFile);
            
            return $imagick;
        } finally {
            // Cleanup SVG file
            if (file_exists($svgTempFile)) {
                unlink($svgTempFile);
            }
        }
    }

    /**
     * Convert a ZPL string directly to an Imagick object.
     *
     * @param string $zpl
     * @param float $widthInches
     * @param float $heightInches
     * @param int $dpi
     * @param string $fontRenderer
     * @return Imagick
     */
    public static function convert(string $zpl, float $widthInches = 4.0, float $heightInches = 6.0, int $dpi = 300, string $fontRenderer = 'noto'): Imagick
    {
        $svg = ZplToSvg::convert($zpl, $widthInches, $heightInches, $dpi, $fontRenderer);
        $width = (int)round($widthInches * $dpi);
        $height = (int)round($heightInches * $dpi);
        return self::fromSvg($svg, $width, $height, $dpi);
    }
}
