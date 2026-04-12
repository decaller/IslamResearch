<?php

namespace App\Support;

class RootHighlighter
{
    /**
     * Highlights words in Arabic text based on exact zero-indexed positions.
     * The $positions array comes from the 'sentence_word' pivot table.
     *
     * @param  string  $text  Full Arabic text
     * @param  array  $positions  Array of indices (e.g., [3, 14]) to highlight
     * @param  string  $rootValue  Raw root string to inject in the data attribute
     * @return string Highlighted HTML string
     */
    public static function highlight(string $text, array $positions, string $rootValue = ''): string
    {
        if (empty($positions) || empty(trim($text))) {
            return $text;
        }

        // Tokenize by whitespace (Arabic is space-delimited as well)
        // using preg_split to preserve whitespaces during reconstruction if we want,
        // or just explode and implode space. Let's do simple explode.
        $words = explode(' ', $text);

        foreach ($positions as $pos) {
            if (isset($words[$pos])) {
                $words[$pos] = sprintf(
                    '<mark data-root="%s">%s</mark>',
                    htmlspecialchars($rootValue),
                    $words[$pos]
                );
            }
        }

        return implode(' ', $words);
    }
}
