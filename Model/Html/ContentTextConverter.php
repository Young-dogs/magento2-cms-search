<?php
/**
 * Copyright © YoungDogs. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace YoungDogs\CmsSearch\Model\Html;

/**
 * Converts WYSIWYG/HTML CMS content into plain, readable text (e.g. for AI agents/automation consumers).
 */
class ContentTextConverter
{
    /**
     * Block-level tags whose boundaries become a newline, so stripped text stays readable line by line.
     *
     * @var string[]
     */
    private const BLOCK_TAGS = [
        'p', 'div', 'br', 'li', 'tr', 'table',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'ul', 'ol', 'blockquote', 'section', 'article', 'header', 'footer',
    ];

    public function toPlainText(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        // Decode first so double-encoded markup (e.g. "&lt;script&gt;") is treated as real tags below.
        $text = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = (string) preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', ' ', $text);
        $text = (string) preg_replace('/<\/?(' . implode('|', self::BLOCK_TAGS) . ')\b[^>]*>/i', "\n", $text);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $lines = array_map(
            static fn (string $line): string => trim((string) preg_replace('/\s+/', ' ', $line)),
            $lines
        );
        $lines = array_values(array_filter($lines, static fn (string $line): bool => $line !== ''));

        return implode("\n", $lines);
    }
}
