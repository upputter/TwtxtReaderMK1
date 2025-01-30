<?php
namespace Twtxt\Parsers;
// escape hashtags to prevent heading rendering in markdown
class MaskHashtags {
    static public function parse(string $rawMessage = '') {
        if (preg_match_all('/(^|\n)(\#)([^\s]+)/', $rawMessage, $matches)) {
            foreach ($matches[0] AS $match) {
                $match = trim($match, "\n");
                $rawMessage = mb_ereg_replace($match, "\n".'\\' .$match, $rawMessage);
            }
        } 
            return $rawMessage;
    }
}