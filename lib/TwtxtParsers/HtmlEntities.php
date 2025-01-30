<?php
namespace Twtxt\Parsers;
// replace errorous html entities
class HtmlEntities {
    static public function parse(string $rawMessage = '') {        
        $replacements = [
            '&#39;' => '\'',
            '&gt;' => '>',
            '&lt;' => '<',
        ];
        foreach($replacements AS $search => $replace) {            
            $rawMessage = mb_ereg_replace($search, $replace, $rawMessage);
        }
        return $rawMessage;
    }
}