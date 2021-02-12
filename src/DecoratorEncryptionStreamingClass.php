<?php

namespace Oxatoc\StreamCrypto;

use Psr\Http\Message\StreamInterface;

class DecoratorEncryptionStreamingClass implements StreamInterface{

    private const TEMP_STREAM = "php://temp";
    private const MINIMAL_READ_BLOCK_SIZE = 64*1024;

    public $tempStreamHandle; //public для целей тестирования
    public $underlyingStream; //public для целей тестирования

    public function __construct(StreamInterface $underlyingStream, OpenSSLStreamInterface $cryptoObj){

        $this->underlyingStream = $underlyingStream;
        $this->cryptoObj = $cryptoObj;

        $this->tempStreamHandle = fopen(self::TEMP_STREAM, 'rw');
    }

    public function __toString(){
        $this->seek(0);
        return $this->getContents();
    }

    public function close(){

        $this->underlyingStream->close();

        if (is_resource($this->tempStreamHandle)){
            fclose($this->tempStreamHandle);
        }
        $this->detach();
    }

    public function detach(){
        if (!isset($this->underlyingStream->handle)){
            return null;
        }

        $result = $this->underlyingStream->handle;
        $this->underlyingStream->detach();

        unset($this->tempStreamHandle);

        return $result;
    }

    public function getSize(){
        return  max($this->getTempFileSize(), $this->underlyingStream->getSize());
    }

    public function tell() {
        if (!isset($this->tempStreamHandle)) {
            throw new \RuntimeException('stream is detached');
        }

        $result = ftell($this->tempStreamHandle);
        
        if ($result === false) {
            throw new \RuntimeException('Unable to determine stream position');
        }

        return $result;
    }

    public function eof() {

        if (!isset($this->tempStreamHandle)) {
            throw new \RuntimeException('stream is detached');
        }

        return feof($this->tempStreamHandle) && $this->underlyingStream->eof();
    }
    public function isSeekable() { return true; }
    public function seek($offset, $whence = SEEK_SET) { 

        if (!isset($this->tempStreamHandle)) {
            throw new \RuntimeException('stream is detached');
        }

        switch ($whence){
            case SEEK_SET: $cursorPosition = $offset; break;
            case SEEK_CUR: $cursorPosition = $this->tell() + $offset; break;
            case SEEK_END: $cursorPosition = $this->underlyingStream->getSize() + $offset; break;
            default: throw new \RuntimeException("\nwhence value '".$whence."' is not implemented\n");
        }

        if ($cursorPosition <= $this->getTempFileSize() ){
            //Если позиция курсора задана в пределах файла temp, то пересталяем курсор
            if (fseek($this->tempStreamHandle, $cursorPosition) === -1) {
                throw new \RuntimeException("\nunable to seek to stream position ". $offset." with whence ". var_export($whence, true)."\n");
            }
            return;
        }

        //Если позиция курсора задана в переделах исходного потока, то дописываем данные в поток temp
        if ($cursorPosition < $this->underlyingStream->getSize()){
            $data = $this->underlyingStream->read($cursorPosition - $this->tell());
            fwrite($this->tempStreamHandle, $data);
            return;
        }

        //Если позиция курсора задана за границами исходного потока
        //- если исходный поток находится в eof, то читаем оставшиеся данных до конца потоке
        if ($this->underlyingStream->eof()){
            $data = $this->underlyingStream->read($this->underlyingStream->getSize() - $this->getTempFileSize());
            $this->appendToTempFile($data);
            return;
        }

        //- если исходный поток не находится в eof, то читаем данные из исходного потока
        $bytesToReadFromUnderlyingStream = $cursorPosition - $this->underlyingStream->tell();
        $data = $this->underlyingStream->read($bytesToReadFromUnderlyingStream);
        $this->appendToTempFile($data);
    }

    public function rewind() {
        $this->seek(0);
    }

    public function isWritable() { return false; }

    public function write($string) {
        throw new \RuntimeException('cannot write to a non-writable stream');
    }
    public function isReadable() { return true; }

    public function read($length){

        if ($length < 0) {
            throw new \RuntimeException('length parameter cannot be negative');
        }

        if (0 === $length) {
            return '';
        }

        if (!isset($this->tempStreamHandle)) {
            throw new \RuntimeException('stream is detached');
        }

        if ($length % (self::MINIMAL_READ_BLOCK_SIZE) != 0){
            throw new \RuntimeException('length to read must multiply 64к+16 for sidecar calculation');
        }

        $data = fread($this->tempStreamHandle, $length);
        if (false === $data) {
            throw new \RuntimeException('unable to read from temp stream');
        }

        $remaining = $length - strlen($data);

        if ($remaining){
            $remoteData = $this->underlyingStream->read($remaining);
            if ($remoteData === false) {
                throw new \RuntimeException('unable to read from underlying stream');
            }
            fwrite($this->tempStreamHandle, $remoteData);
            $data.= $remoteData;
        }

        $enc = $this->cryptoObj->getOpensslData($data);
        $hmac = $this->cryptoObj->getHmac();
        return $enc.$hmac;
    }
    public function getContents() {

        if (!isset($this->tempStreamHandle)) {
            throw new \RuntimeException('stream is detached');
        }

        $contents = "";
        while(!$this->eof()){
            $contents .= $this->read(self::MINIMAL_READ_BLOCK_SIZE);
        }

        return $contents;
    }
    public function getMetadata($key = null) {
        if (!isset($this->underlyingStream->handle)) {
            return $key ? null : [];
        } 

        $meta = stream_get_meta_data($this->underlyingStream->handle);

        return $meta[$key] ?? null;
    }

    //функции вне интерфейса

    public function getTempFileSize(){
        return fstat($this->tempStreamHandle)['size'];
    }

    //Приватные функции

    private function appendToTempFile($data){
        fseek($this->tempStreamHandle, $this->getTempFileSize());
        fwrite($this->tempStreamHandle, $data);
    }

}