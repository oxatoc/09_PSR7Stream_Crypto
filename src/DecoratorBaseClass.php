<?php

namespace Oxatoc\StreamCrypto;

use Psr\Http\Message\StreamInterface;

class DecoratorBaseClass implements StreamInterface{

    protected $underlyingStream;

    public function __toString() { 
        try {
            if ($this->underlyingStream->isSeekable()) {
                $this->seek(0);
            }
            return $this->underlyingStream->getContents();
        } catch (\Throwable $e) {
            if (\PHP_VERSION_ID >= 70400) {
                throw $e;
            }
            trigger_error(sprintf('%s::__toString exception: %s', self::class, (string) $e), E_USER_ERROR);
            return '';
        }
    }
    public function close() { 
        $this->underlyingStream->close();
        $this->detach();
    }
    public function detach() { 
        if (!isset($this->underlyingStream->handle)){
            return null;
        }

        $result = $this->underlyingStream->handle;
        $this->underlyingStream->detach();
        return $result;
    }
    public function getSize() {

        if (!isset($this->underlyingStream)) {
            return null;
        }

        return $this->underlyingStream->getSize();
    }
    public function tell() 
    { 
        if (!isset($this->underlyingStream->handle)) {
            throw new \RuntimeException('Stream is detached');
        }

        $result = $this->underlyingStream->tell();

        if ($result === false) {
            throw new \RuntimeException('Unable to determine stream position');
        }

        return $result;
    }
    public function eof() 
    { 
        if (!isset($this->underlyingStream->handle)) {
            throw new \RuntimeException('Stream is detached');
        }

        return $this->underlyingStream->eof();
    }
    public function isSeekable() { 
        return $this->underlyingStream->isSeekable();
    }
    public function seek($offset, $whence = SEEK_SET) { 
        $whence = (int) $whence;

        if (!isset($this->underlyingStream->handle)) {
            throw new \RuntimeException('Stream is detached');
        }
        if (!$this->underlyingStream->isSeekable()) {
            throw new \RuntimeException('Stream is not seekable');
        }
        $this->underlyingStream->seek($offset, $whence);
    }
    public function rewind() { 
        return $this->seek(0); 
    }
    public function isWritable() { 
        return $this->underlyingStream->isWritable(); 
    }
    public function write($string) { 
        if (!isset($this->underlyingStream->handle)) {
            throw new \RuntimeException('Stream is detached');
        }
        if (!$this->underlyingStream->isWritable()) {
            throw new \RuntimeException('Cannot write to a non-writable stream');
        }

        $result = $this->underlyingStream->write($string);

        if ($result === false) {
            throw new \RuntimeException('Unable to write to stream');
        }

        return $result;
    }
    public function isReadable() { 
        return $this->underlyingStream->isReadable(); 
    }
    public function read($length) { 
        if (!isset($this->underlyingStream->handle)) {
            throw new \RuntimeException('Stream is detached');
        }
        if (!$this->underlyingStream->IsReadable()) {
            throw new \RuntimeException('Cannot read from non-readable stream');
        }
        if ($length < 0) {
            throw new \RuntimeException('Length parameter cannot be negative');
        }

        if (0 === $length) {
            return '';
        }
    }
    public function getContents() {
        if (!isset($this->underlyingStream->handle)) {
            throw new \RuntimeException('Stream is detached');
        }
        return $this->underlyingStream->getContents();

    }
    public function getMetadata($key = null) { 
        if (!isset($this->underlyingStream->handle)) {
            return $key ? null : [];
        } 

        $meta = stream_get_meta_data($this->underlyingStream->handle);

        return $meta[$key] ?? null;
    }
}