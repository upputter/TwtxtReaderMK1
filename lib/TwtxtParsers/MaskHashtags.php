<?php

namespace Twtxt\Parsers;

// escape hashtags to prevent heading rendering in markdown
class MaskHashtags
{
    public static function parse(string $rawMessage = '')
    {
        if (preg_match_all('/(^|\n)(\#)([^\s]+)/', $rawMessage, $matches)) {
            foreach ($matches[0] as $match) {
                $match = trim($match, "\n");
                $rawMessage = mb_ereg_replace($match, "\n".'\\' .$match, $rawMessage);
            }
        }
        return $rawMessage;
    }
}
