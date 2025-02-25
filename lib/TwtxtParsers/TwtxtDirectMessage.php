<?php

namespace Twtxt\Parsers;

// decrypt direct messages
class TwtxtDirectMessage
{
    public static function parse(string $rawMessage = '', $publicKey = '')
    {
        global $config, $validUser;
        $encryptIcon = '🔓 ';

        if (!$validUser) {
            return $rawMessage;
        }

        // w/o private key, no encryption is possible
        if (empty($config->settings['dmPrivKey']) or empty($publicKey)) {
            return $rawMessage;
        }


        $pattern = '/(?!`\s)(?<!`)!<([^ ]+)\s([^>]+)>/'; // "(?!`\s)(?<!`)" - skips markdown code block (regex lookahead, lookbehind)

        $replace = $encryptIcon . '[@$1]('.$_SERVER["SCRIPT_NAME"].'?action=own&url=$2)|'; // set url to posts of user

        if (preg_match($pattern, $rawMessage, $check)) {
            if (count($check) == 3) {
                if (filter_var($check[2], FILTER_VALIDATE_URL)) {
                    $rawMessage = preg_replace($pattern, $replace, $rawMessage);
                    list($mention, $encryptedData) = explode('|', $rawMessage);

                    $decryptor = new \TwtxtDirectMessageCryptor($publicKey, $config->settings['dmPrivKey']);
                    $decryptedMessage = $decryptor->decrypt(trim(base64_decode($encryptedData)));

                    return $mention . ' ' . $decryptedMessage;
                }
            }
        }
        return $rawMessage;
    }
}
