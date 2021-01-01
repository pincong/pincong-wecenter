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
    private $cipher = "AES-256-CBC";
    private $default_key;
    private $iv_len;
    public function __construct()
    {
        $this->default_key = $this->new_key();
        $this->iv_len = openssl_cipher_iv_length($this->cipher);
    }

    public function new_key($password = null)
    {
        if (!$password)
        {
            $password = G_COOKIE_HASH_KEY;
        }
        return hash('sha256', $password, true);
    }

    public function encrypt($data, $key = null)
    {
        if (!$key)
        {
            $key = $this->default_key;
        }
        $iv = openssl_random_pseudo_bytes($this->iv_len);
        $data = openssl_encrypt($data, $this->cipher, $key, OPENSSL_RAW_DATA, $iv);
        $data = $iv . $data;
        return $data;
    }

    public function decrypt($data, $key = null)
    {
        if (!$key)
        {
            $key = $this->default_key;
        }
        $iv = substr($data, 0, $this->iv_len);
        $data = substr($data, $this->iv_len);
        $data = openssl_decrypt($data, $this->cipher, $key, OPENSSL_RAW_DATA, $iv);
        return $data;
    }

    public function encode($data, $key = null)
    {
        $data = $this->encrypt($data, $key);
        $data = base64_encode($data);
        $data = strtr(rtrim($data, '='), '+/', '._');
        return $data;
    }

    public function decode($data, $key = null)
    {
        $data = strtr($data, '._', '+/');
        $data = base64_decode($data);
        $data = $this->decrypt($data, $key);
        return $data;
    }

}
