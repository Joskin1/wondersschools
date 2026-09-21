<?php

declare(strict_types=1);

namespace App\Support;

class Color
{
    /**
     * Compute contrast text color (#FFFFFF or #0B2545) based on relative luminance.
     */
    public static function contrastOn(?string $hex, string $darkText = '#0B2545', string $lightText = '#FFFFFF'): string
    {
        if (empty($hex)) {
            return $lightText;
        }

        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
            return $lightText;
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $rLin = ($r <= 0.04045) ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $gLin = ($g <= 0.04045) ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $bLin = ($b <= 0.04045) ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

        $luminance = 0.2126 * $rLin + 0.7152 * $gLin + 0.0722 * $bLin;

        // If luminance is high (light background), use dark text; otherwise white text.
        return $luminance > 0.4 ? $darkText : $lightText;
    }
}
