<?php
namespace Twtxt\Parsers;
class iFrameVideo {
    private static $pattern = '/!\[iframe\]\((.+)\)/';
    static public function parse(string $rawMessage = '') {        
        $replace = "\n" . '<iframe src="$1" class="embeded-video" allow="encrypted-media" allowfullscreen="allowfullscreen" frameborder="0" scrolling="auto"><a href="$1" target="_blank">$1</a></iframe>';
        $string = preg_replace(self::$pattern, $replace, $rawMessage);
        return $string;
    }
}