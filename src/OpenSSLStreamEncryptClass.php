<?php

namespace Oxatoc\StreamCrypto;

class OpenSSLStreamEncryptClass implements OpenSSLStreamInterface {

    private const BLOCK_SIZE = 16;

    public $iv;
    public $cipherKey;
    public $macKey;
    public $hashContext;


    public function __construct($mediaKey, $hkdfInfo){
        $mediaKeyExpanded = hash_hkdf("sha256", $mediaKey, 112, $hkdfInfo);

        $this->iv = substr($mediaKeyExpanded, 0, 16);
        $this->cipherKey = substr($mediaKeyExpanded, 16, 32);
        $this->macKey = substr($mediaKeyExpanded, 48, 32);

        $this->hashContext = hash_init("sha256", HASH_HMAC, $this->macKey);
        hash_update($this->hashContext, $this->iv);
    }

    public function getOpensslData($data){
        $options = OPENSSL_RAW_DATA;
        if (strlen($data) % 16 == 0){
            $options |= OPENSSL_ZERO_PADDING;
        }

        $enc = openssl_encrypt($data, "aes-256-cbc", $this->cipherKey,  $options, $this->iv);
        if ($enc === false){
            throw new \RuntimeException("\ncan't encrypt media data\n");
        }

        $this->iv = substr($enc, self::BLOCK_SIZE * -1);

        hash_update($this->hashContext, $enc);

        return $enc;
    }

    public function getHmac(){
        return substr(hash_final($this->hashContext, true), 0, 10);
    }
}