<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2014 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|
+---------------------------------------------------------------------------
*/

class core_crypt
{
    private $cipher ="AES-256-CBC";
    private $key = G_COOKIE_HASH_KEY;
    private $ivLen;
    public function __construct()
    {
        $this->key = hash('sha256', $this->key, true);
        $this->ivLen = openssl_cipher_iv_length($this->cipher);
    }

    public function encode($data, $key = null)
    {
        $iv = openssl_random_pseudo_bytes($this->ivLen);
        $data = openssl_encrypt($data, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);
        $data = base64_encode($iv . $data);
        return $data;
    }

    public function decode($data, $key = null)
    {
        $data = base64_decode($data);
        $iv = substr($data, 0, $this->ivLen);
        $data = substr($data, $this->ivLen);
        $decrypted = openssl_decrypt($data, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);
        
        return $decrypted;
    }

    private function get_key($mcrypt, $key = null)
    {
        if (!$key)
        {
            $key = G_COOKIE_HASH_KEY;
        }

        return substr($key, 0, mcrypt_enc_get_key_size($mcrypt));
    }

    private function get_algorithms()
    {
        $algorithms = mcrypt_list_algorithms();

        foreach ($algorithms AS $algorithm)
        {
            if (strstr($algorithm, '-256'))
            {
                return $algorithm;
            }
        }

        foreach ($algorithms AS $algorithm)
        {
            if (strstr($algorithm, '-128'))
            {
                return $algorithm;
            }
        }

        return end($algorithms);
    }

    private function str_to_hex($string)
    {
        for ($i = 0; $i < strlen($string); $i++)
        {
            $ord = ord($string[$i]);
            $hexCode = dechex($ord);
            $hex .= substr('0' . $hexCode, -2);
        }

        return strtoupper($hex);
    }

    private function hex_to_str($hex)
    {
        for ($i = 0; $i < strlen($hex)-1; $i += 2)
        {
            $string .= chr(hexdec($hex[$i] . $hex[$i + 1]));
        }

        return $string;
    }
}
