<?php

namespace Oxatoc\StreamCrypto;

use Psr\Http\Message\StreamInterface;

class DecoratorDecryptionClass extends DecoratorBaseClass{

    private $macBuffer = '';

    public function __construct(StreamInterface $underlyingStream, OpenSSLStreamInterface $cryptoObj){
        $this->underlyingStream = $underlyingStream;
        $this->cryptoObj = $cryptoObj;
    }

    public function read($length) 
    { 
        parent::read($length);

        $readBuffer = $this->underlyingStream->read($length);
        return $this->getDecryptedStream($readBuffer);
    }

    public function getContents() { 
        return $this->getDecryptedStream(parent::getContents());
    }

    public function __toString() {
        return $this->getDecryptedStream(parent::__toString());
    }

    //Извлечение и сохранение mac
    private function pickAndStoreMac($chunk){
        $macPartLength = 10 - ($this->getSize() - $this->tell());

        if ($macPartLength > 0){
            $this->macBuffer .= substr($chunk, - $macPartLength);
            return substr($chunk, 0, strlen($chunk) - $macPartLength);
        }
        return $chunk;
    }

    private function getDecryptedStream($data){
        $data = $this->pickAndStoreMac($data);
        $dec = $this->cryptoObj->getOpensslData($data);

        if ($this->eof()){
            //Валидация данных
            $hmac = $this->cryptoObj->getHmac();

            if ($this->macBuffer !== $hmac){
                throw new \RuntimeException("\n"."media data is not valid\n");
            }
        }

        //Удаление padding
        if ($this->eof()){
            $paddings = (int)ord(substr($dec, -1));
            if ($paddings <= 16){
                $dec = substr($dec, 0, strlen($dec) - $paddings);
            }
        }

        return $dec;

    }
}