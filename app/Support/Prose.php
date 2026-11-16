<?php

namespace App\Support;

/**
 * Render admin-authored plain text as safe HTML.
 *
 * Content fields (page body, article body, product description) hold plain
 * text, not HTML. Rendering them with `{!! !!}` would make every admin account
 * an XSS vector into the public site — PHASE_3_CLOSURE_AUDIT.md W6 deferred
 * this decision only until public rendering of authored text existed, which it
 * now does.
 *
 * Rather than introduce an HTML sanitizer dependency, the storage format is
 * plain text and this formatter escapes it before adding paragraph/line-break
 * markup. Every character that reaches the browser has been through e(), so
 * the output is safe by construction.
 *
 * Supported conventions (deliberately minimal):
 *   - paragraphs separated by one or more blank lines
 *   - lines starting with "- " form an unordered list
 */
final class Prose
{
    public static function toHtml(?string $text): string
    {
        if ($text === null || trim($text) === '') {
            return '';
        }

        $normalized = str_replace(["\r\n", "\r"], "\n", $text);
        $blocks = preg_split('/\n{2,}/', trim($normalized)) ?: [];

        $html = '';

        foreach ($blocks as $block) {
            $block = trim($block);

            if ($block === '') {
                continue;
            }

            if (str_starts_with($block, '- ')) {
                $html .= self::list($block);

                continue;
            }

            $html .= '<p>'.nl2br(e($block), false).'</p>';
        }

        return $html;
    }

    private static function list(string $block): string
    {
        $html = '<ul>';
        $lines = preg_split('/\n/', $block) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $html .= '<li>'.e(ltrim($line, '- ')).'</li>';
        }

        return $html.'</ul>';
    }
}
