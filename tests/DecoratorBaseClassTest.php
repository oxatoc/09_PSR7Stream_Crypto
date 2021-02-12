<?php

namespace Oxatoc\StreamCrypto;

use Oxatoc\StreamCryptoTests\UnderlyingStreamClass;

use PHPUnit\Framework\TestCase;

class DecoratorBaseClassTest extends TestCase{

    private const MINIMAL_READ_BLOCK_SIZE = 8*1024;

    private static $cryptoObj;
    private static $referenceSample;
    private static $mediaDataFile;
    private $underlyingStream;
    private $encryptorObj;

    public static function setUpBeforeClass(): void
    {
        $mediaKey = file("samples/VIDEO.key")[0];
        self::$cryptoObj = new OpenSSLStreamingEncryptClass($mediaKey, "WhatsApp Video Keys");
        self::$referenceSample = "samples/VIDEO.encrypted";
        self::$mediaDataFile = "samples/VIDEO.original";

    }

    protected function setUp() : void
    {
        //Поток сразу открывается - нужно создават перед каждым тестом заново
        $this->underlyingStream = new UnderlyingStreamClass(self::$mediaDataFile);

        $this->encryptorObj = new DecoratorEncryptionClass($this->underlyingStream, self::$cryptoObj);
    }


    public function testToString(){
        // public function __toString();
        $outputFile = "outputs/encrypted.bin";
        file_put_contents($outputFile, $this->encryptorObj->__toString());
        $this->assertFileEquals(self::$referenceSample, $outputFile, 'содержание файлов отличается');
    }

    public function testClose(){
        // public function close();
        $this->encryptorObj->close();
        $this->assertFalse(isset($this->underlyingStream->handle));
    }

    public function testDetach(){
        // public function detach();
        $this->assertSame($this->underlyingStream->handle, $this->encryptorObj->detach());
        $this->assertFalse(isset($this->underlyingStream->handle));
        $this->assertNull($this->encryptorObj->detach());
    }

    public function testGetSize(){
        // public function getSize();
        $this->encryptorObj->read($this->underlyingStream->getSize());
        $this->assertEquals($this->underlyingStream->getSize(), $this->encryptorObj->getSize());

    }

    public function testTell(){
        // public function tell();
        $this->encryptorObj->read(self::MINIMAL_READ_BLOCK_SIZE);
        $this->assertEquals(self::MINIMAL_READ_BLOCK_SIZE, $this->encryptorObj->tell());
    }

    public function testEof(){
        // public function eof();
        $this->encryptorObj->read($this->underlyingStream->getSize() + self::MINIMAL_READ_BLOCK_SIZE);
        $this->assertTrue($this->encryptorObj->eof());
    }

    public function testIsSeekable(){
        // public function isSeekable();
        $this->assertTrue($this->encryptorObj->isSeekable());
    }

    public function testSeek(){
        // public function seek($offset, $whence = SEEK_SET);
        $this->encryptorObj->seek(self::MINIMAL_READ_BLOCK_SIZE);
        $this->assertEquals(self::MINIMAL_READ_BLOCK_SIZE, $this->encryptorObj->tell());
    }

    public function testRewind(){
        // public function rewind();
        $this->encryptorObj->seek(self::MINIMAL_READ_BLOCK_SIZE);
        $this->assertEquals(self::MINIMAL_READ_BLOCK_SIZE, $this->encryptorObj->tell());
        $this->encryptorObj->rewind();
        $this->assertEquals(0, $this->encryptorObj->tell());

    }

    public function testIsWritable(){
        // public function isWritable();
        $this->assertFalse($this->encryptorObj->isWritable());
    }

    // //Закомментирован0 - выдает исключение, блокирует выполнение теста
    // public function testWrite(){
    //     // public function write($string);
    //     // $this->expectException($this->encryptorObj->write(99));
    // }

    public function testIsReadable(){
        // public function isReadable();
        $this->assertTrue($this->encryptorObj->isReadable());
    }

    //Закомментировано - метод read() тестируется в классах-наследниках
    // public function testRead(){
    //     // public function read($length);
    // }

    //Закомментировано - метод getContents() тестируется в классах-наследниках
    // public function testGetContents(){
    //     // public function getContents();
    // }

    public function testGetMetadata(){
        // public function getMetadata($key = null);
        $this->assertEquals(self::$mediaDataFile, $this->encryptorObj->getMetaData("uri"));
        $this->encryptorObj->detach();
        $this->assertIsArray($this->encryptorObj->getMetaData());
        $this->assertNull($this->encryptorObj->getMetaData("uri"));        
    }
}