<?php

namespace lsb\Libs;

use lsb\Config\Config;

class Encrypt
{
    private static $key = null;
    private static $cipher = null;

    private static function getKeyCipher()
    {
        if (is_null(static::$key) || is_null(static::$cipher)) {
            $conf = Config::getInstance()->getConfig('encrypt');
            static::$key = $conf['key'];
            static::$cipher = $conf['cipher'];
        }
        return [static::$key, static::$cipher];
    }

    public static function encrypt(string $plaintext)
    {
        list($key, $cipher) = static::getKeyCipher();

        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($plaintext, $cipher, $key, OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary = true);
        $ciphertext = base64_encode($iv . $hmac . $ciphertext_raw);
        return $ciphertext;
    }

    public static function decrypt(string $ciphertext)
    {
        list($key, $cipher) = static::getKeyCipher();

        $c = base64_decode($ciphertext);
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = substr($c, 0, $ivlen);
        $hmac = substr($c, $ivlen, $sha2len = 32);

        $ciphertext_raw = substr($c, $ivlen + $sha2len);
        $plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, $options = OPENSSL_RAW_DATA, $iv);
        $calcmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary = true);
        if (!hash_equals($hmac, $calcmac)) { //PHP 5.6+ timing attack safe comparison
            return false;
        }
        return $plaintext;
    }
}
