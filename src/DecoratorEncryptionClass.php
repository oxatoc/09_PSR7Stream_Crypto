<?php

namespace Oxatoc\StreamCrypto;

use Psr\Http\Message\StreamInterface;

class DecoratorEncryptionClass extends DecoratorBaseClass{

    private $cryptoObj;

    public function __construct(StreamInterface $underlyingStream, OpenSSLStreamInterface $cryptoObj){
        $this->underlyingStream = $underlyingStream;
        $this->cryptoObj = $cryptoObj;
    }

    public function read($length) 
    { 
        parent::read($length);

        $data = $this->underlyingStream->read($length);

        return $this->getEncryptedStream($data);
    }

    public function getContents() { 
        return $this->getEncryptedStream(parent::getContents());
    }

    public function __toString() {
        return $this->getEncryptedStream(parent::__toString());
    }

    //Приватные функции
    private function getEncryptedStream($data){
        $enc = $this->cryptoObj->getOpensslData($data);

        if ($this->eof()){
            $hmac = $this->cryptoObj->getHmac();
            $enc .= $hmac;
        }
        return $enc;
    }

}