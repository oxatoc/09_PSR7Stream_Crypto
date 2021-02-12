<?php

namespace Oxatoc\StreamCryptoTests;

use Psr\Http\Message\StreamInterface;

class UnderlyingStreamClass implements StreamInterface{

    public $handle; //public для целей тестирования

    public function __construct($filePath) {
        $this->filePath = $filePath;
        $this->handle = fopen($this->filePath, 'r');
    }

    public function __toString() {
        
    }
    public function close() {
        fclose($this->handle);
        $this->detach();
    }
    public function detach() {
        unset($this->handle);
    }
    public function getSize() {
        $fs = filesize($this->filePath);
        if ($fs === false){
            return null;
        }
        return $fs;
    }
    public function tell() { return ftell($this->handle); }
    public function eof() { return feof($this->handle); }
    public function isSeekable() { return true; }
    public function seek($offset, $whence = SEEK_SET) { fseek($this->handle, $offset, $whence); }
    public function rewind() { }
    public function isWritable() { return false; }
    public function write($string) { throw new \RuntimeException("\nwriting is not allowed\n");}
    public function isReadable() { return true; }
    public function read($length) {
        $chunk = stream_get_line($this->handle, $length);
        if ($chunk === false){
            if ($this->eof()){
                return '';
            } else {
                throw new \RuntimeException("\nerror while reading from underlaying stream\n");
            }
        }
        return $chunk;
    }
    public function getContents() {
        return stream_get_contents($this->handle);
    }
    public function getMetadata($key = null) {}

}

