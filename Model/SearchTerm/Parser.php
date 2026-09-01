<?php
/**
 * Copyright © YoungDogs. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace YoungDogs\CmsSearch\Model\SearchTerm;

/**
 * Splits a raw search term into exact "quoted phrases" and individual words,
 * similar to a typical search engine query box.
 */
class Parser
{
    /**
     * @return array{phrases: string[], words: string[]}
     */
    public function parse(string $term): array
    {
        $term = trim($term);
        $phrases = [];

        if (preg_match_all('/"([^"]+)"/', $term, $matches)) {
            foreach ($matches[1] as $phrase) {
                $phrase = trim($phrase);
                if ($phrase !== '') {
                    $phrases[] = $phrase;
                }
            }
            $term = preg_replace('/"([^"]+)"/', ' ', $term);
        }

        $words = array_values(array_filter(
            preg_split('/\s+/', trim((string) $term)) ?: [],
            static fn (string $word): bool => $word !== ''
        ));

        return ['phrases' => $phrases, 'words' => $words];
    }
}
