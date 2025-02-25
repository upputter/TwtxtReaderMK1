<?php

namespace Twtxt\Parsers;

use Alchemy\Component\Yaml\Yaml;
use Exception;

// TODO: cleanup public key usage
// decrypt direct messages
class TwtxtDirectMessage
{
    public static function parse(string $rawMessage = '', $publicKey = '')
    {
        global $config, $validUser;

        $encryptIcon = '🔓 ';
        $messagePlaceholder = ' ***';

        // TODO: find pattern for inline (`) and block (```) code
        $pattern = '/(?!`\s)(?<!`)!<([^ ]+)\s([^>]+)>/'; // "(?!`\s)(?<!`)" - skips markdown code block (regex lookahead, lookbehind)
        $replace = $encryptIcon . '[@$1]('.$_SERVER["SCRIPT_NAME"].'?action=own&url=$2)|'; // set url to posts of user

        if (preg_match($pattern, $rawMessage, $check)) {
            if (count($check) == 3) {
                if (filter_var($check[2], FILTER_VALIDATE_URL)) {
                    $parsedRawMessage = preg_replace($pattern, $replace, $rawMessage);
                    list($mention, $encryptedData) = explode('|', $parsedRawMessage);

                    if (!$validUser or !$publicKey) {
                        return $rawMessage;
                    }

                    // use YAML to load stored public keys
                    $yaml = new Yaml();
                    $publicKeys = $yaml->load('private/pubkeys.yaml');
                    $pubkeyName = $check[1] . '@' . $check[2];
                    $ownMention = $config->settings['nick'] . '@' . $config->settings['twturl'];
                    // if ($pubkeyName == $ownMention)
                    $publicKeyFromStorage = $publicKeys[$pubkeyName];
                    try {
                        $decryptor = new \TwtxtDirectMessageCryptor($publicKeyFromStorage, $config->settings['dmPrivKey']);
                        $decryptedMessage = @$decryptor->decrypt(trim(base64_decode($encryptedData)));
                    } catch (Exception $e) {
                        return $rawMessage;
                    }
                    return $mention . ' ' . $decryptedMessage;
                }
            }
        }
        return $rawMessage;
    }
}
