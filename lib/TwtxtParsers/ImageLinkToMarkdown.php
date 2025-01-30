<?php
namespace Twtxt\Parsers;
// replace image-link in $string with markdown, skip markdown formated image-urls
class ImageLinkToMarkdown {
    static public function parse(string $rawMessage = '') {        
        $markDownImagePattern = '/!\[(.*)\]\((.+)\)/';
        if (!preg_match($markDownImagePattern, $rawMessage, $check)) {
            $pattern = '/(?!\[\s)(?<!\])(http(s?)?:\/\/[^ ]+?(?:\.jpg|\.png|\.gif|\.jpeg))/i';
            $replace = '![$1]($1)'."\n>". '*$1*';
            if (preg_match($pattern, $rawMessage, $check)) {
                $rawMessage = preg_replace($pattern, $replace, $rawMessage);
            }
        }
        return $rawMessage;
    }
}