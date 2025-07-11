<?php

namespace App\Helpers;

class EmailHelper
{
    public static function convertRelativeUrlsToAbsolute(string $html): string
    {
        // Convert relative image URLs to absolute URLs
        $html = preg_replace_callback(
            '/(<img[^>]+src=")([^"]+)(")/i',
            function ($matches) {
                $src = $matches[2];
                if (! str_starts_with($src, 'http')) {
                    $src = url($src);
                }

                return $matches[1] . $src . $matches[3];
            },
            $html
        );

        // Convert relative links to absolute URLs
        $html = preg_replace_callback(
            '/(<a[^>]+href=")([^"]+)(")/i',
            function ($matches) {
                $href = $matches[2];
                if (! str_starts_with($href, 'http') && ! str_starts_with($href, 'mailto:')) {
                    $href = url($href);
                }

                return $matches[1] . $href . $matches[3];
            },
            $html
        );

        return $html;
    }
}
