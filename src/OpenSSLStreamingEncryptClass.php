<?php

namespace Oxatoc\StreamCrypto;

class OpenSSLStreamingEncryptClass implements OpenSSLStreamInterface {

    public $iv;
    public $cipherKey;
    public $macKey;
    public $hashContext;
    public $enc;

    private const BLOCK_SIZE = 16;

    public function __construct($mediaKey, $hkdfInfo){
        $mediaKeyExpanded = hash_hkdf("sha256", $mediaKey, 112, $hkdfInfo);

        $this->iv = substr($mediaKeyExpanded, 0, 16);
        $this->cipherKey = substr($mediaKeyExpanded, 16, 32);
        $this->macKey = substr($mediaKeyExpanded, 48, 32);
    }

    public function getOpensslData($data){
        $options = OPENSSL_RAW_DATA;
        if (strlen($data) % 16 == 0){
            $options |= OPENSSL_ZERO_PADDING;
        }

        $this->enc = openssl_encrypt($data, "aes-256-cbc", $this->cipherKey,  $options, $this->iv);
        if ($this->enc === false){
            throw new \RuntimeException("\ncan't encrypt media data\n");
        }

        return $this->enc;
    }

    public function getHmac(){
        $hmac = substr(hash_hmac("sha256", $this->iv.$this->enc,  $this->macKey, true), 0, 10);
        $this->iv = substr($this->enc, self::BLOCK_SIZE * -1);
        return $hmac;
    }
}