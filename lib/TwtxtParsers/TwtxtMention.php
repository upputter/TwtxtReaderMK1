<?php
namespace Twtxt\Parsers;
// render twtxt mentions
class TwtxtMention {
    static public function parse(string $rawMessage = '') {        
        $pattern = '/(?!`\s)(?<!`)@<([^ ]+)\s([^>]+)>/'; // "(?!`\s)(?<!`)" - skips markdown code block (regex lookahead, lookbehind)
        $replace = '[@$1]('.$_SERVER["SCRIPT_NAME"].'?action=own&url=$2)'; // set url to posts of user
        if (preg_match($pattern, $rawMessage, $check)) {
            if (count($check) == 3) {                
                if (filter_var($check[2], FILTER_VALIDATE_URL)) {
                    $rawMessage = preg_replace($pattern, $replace, $rawMessage);    
                }
            }
        }      	
        return $rawMessage;
    }
}