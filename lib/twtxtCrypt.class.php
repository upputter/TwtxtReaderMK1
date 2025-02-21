<?php

// AES encryption and decryption from https://stackoverflow.com/questions/68835102/how-to-convert-openssl-encrypt-and-decrypt-into-php

// https://docs.openssl.org/3.0/man7/EVP_KDF-PBKDF2/
// https://security.stackexchange.com/questions/31564/key-length-and-hash-function-in-pbkdf2
// https://stackoverflow.com/questions/58823814/what-default-parameters-uses-openssl-pbkdf2
// https://www.php.net/manual/en/function.openssl-pbkdf2.php
// https://github.com/meixler/web-browser-based-file-encryption-decryption/tree/master
// https://github.com/blocktrail/cryptojs-aes-php/blob/master/src/CryptoJSAES.php
// https://crypto.stackexchange.com/questions/3298/is-there-a-standard-for-openssl-interoperable-aes-encryption/79855#79855
// https://eapl.me/tw.txt

class TwtxtDirectMessageCryptor
{
    public const CYPHER = 'aes-256-cbc';
    public const SALTSIZE = 8;
    public const ITERATIONS = 100000;
    public const PBKDF2_KEY_SIZE = 48;
    public const PBKDF2_ALGO = 'sha256';

    protected $salt;
    protected $sharedKey;

    public function __construct(
        public string $publicKey,
        public string $privateKey,
        public bool $debug = false,
    ) {
        $this->publicKey = $this->getFullPemFromKeyString($this->publicKey, false);
        $this->privateKey = $this->getFullPemFromKeyString($this->privateKey, true);
        // generate shared key pair
        $this->sharedKey = openssl_pkey_derive($this->publicKey, $this->privateKey);
    }

    protected function debug($info)
    {
        if ($this->debug) {
            echo $info . '<br />' . PHP_EOL;
        }
    }

    public function encrypt($message)
    {
        $this->salt = openssl_random_pseudo_bytes(self::SALTSIZE);
        $pbkdf2key = $this->generatEPBKDF2key($this->sharedKey);
        $encryptedMessage = $this->aesEncryption($pbkdf2key, $message);
        return base64_decode($encryptedMessage);
    }

    public function decrypt($encryptedMessage)
    {
        $decodedData = $this->decode($encryptedMessage);
        $this->salt = $decodedData['salt'];
        $pbkdf2key = $this->generatEPBKDF2key($this->sharedKey);
        $decryptedMessage = $this->aesDecryption($pbkdf2key, $encryptedMessage);
        return $decryptedMessage;
    }

    protected function aesEncryption($pbkdf2key, $data)
    {
        $iv = $this->getIVfromEPBKDF2key($pbkdf2key);
        $encryptedData = openssl_encrypt($data, self::CYPHER, $pbkdf2key, true, $iv);
        return $this->encode($encryptedData, $this->salt);
    }

    protected function getIVfromEPBKDF2key($pbkdf2key)
    {
        return hex2bin(substr(bin2hex($pbkdf2key), 64, 32));  // iv is bytes 32 to 47 of the pbkdf2key | use HEX for safer handling
    }

    protected function aesDecryption($pbkdf2key, $encData)
    {
        if ($this->is_base64($encData)) {
            $encData = base64_decode($encData);
        }

        $decodedData = $this->decode($encData);
        $iv = $this->getIVfromEPBKDF2key($pbkdf2key);

        $data = openssl_decrypt($decodedData['data'], self::CYPHER, $pbkdf2key, true, $iv);
        return $data;
    }

    // add 'Salted__' string and salt to content
    protected function encode($ct, $salt)
    {
        return base64_encode('Salted__' . $salt . $ct);
    }

    protected function decode($data)
    {
        // the string "Salted__" is 8 bytes long
        if (substr($data, 0, 8) !== 'Salted__') {
            return false;
        }
        // get the salt after the "Salted__" string wit the given salt size
        $salt = substr($data, 8, self::SALTSIZE);

        // get the encrypted content after the salt ends
        $content = substr($data, 8 + self::SALTSIZE);
        return [
            'data' => $content,
            'salt' => $salt
        ];
    }

    protected function generatEPBKDF2key($key)
    {
        $generated_key = openssl_pbkdf2($key, $this->salt, self::PBKDF2_KEY_SIZE, self::ITERATIONS, self::PBKDF2_ALGO);
        return $generated_key;
    }

    // openssl_pkey_derive() needs the full PEM key structure
    protected function getFullPemFromKeyString($key, $isPrivate = false)
    {
        $keyType = ($isPrivate) ? 'PRIVATE' : 'PUBLIC';
        return '-----BEGIN ' . $keyType . ' KEY-----' . PHP_EOL . $key . PHP_EOL . '-----END ' . $keyType . ' KEY-----' .PHP_EOL;
    }

    protected function is_base64($s)
    {
        return (bool) preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $s);
    }
}
