<?php

namespace Twtxt\Parsers;

// replace errorous html entities
class HtmlEntities
{
    public static function parse(string $rawMessage = '')
    {
        $replacements = [
            '&#39;' => '\'',
            '&gt;' => '>',
            '&lt;' => '<',
        ];
        foreach ($replacements as $search => $replace) {
            $rawMessage = mb_ereg_replace($search, $replace, $rawMessage);
        }
        return $rawMessage;
    }
}
