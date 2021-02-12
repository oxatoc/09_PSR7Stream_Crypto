<?php declare(strict_types=1);

namespace Oxatoc\StreamCrypto;

use Oxatoc\StreamCryptoTests\UnderlyingStreamClass;

use PHPUnit\Framework\TestCase;

class StreamingTest extends TestCase{

    private const MINIMAL_READ_BLOCK_SIZE = 64*1024;

    private static $cryptoObj;
    private static $referenceSample;
    private $underlyingStream;
    private $streamingObj;



    public static function setUpBeforeClass(): void
    {
        self::$referenceSample = "samples/VIDEO.original";
        $mediaKey = file("samples/VIDEO.key")[0];
        self::$cryptoObj = new OpenSSLStreamingEncryptClass($mediaKey, "WhatsApp Video Keys");
    }

    protected function setUp() : void
    {
        //Поток сразу открывается - нужно создавать перед каждым тестом заново
        $this->underlyingStream = new UnderlyingStreamClass(self::$referenceSample);
        $this->streamingObj = new DecoratorEncryptionStreamingClass($this->underlyingStream, self::$cryptoObj);
    }

    public function testDetach(){
        $this->assertSame($this->underlyingStream->handle, $this->streamingObj->detach());
        $this->assertFalse(isset($this->streamingObj->underlyingStream->handle));
        $this->assertFalse(isset($this->streamingObj->tempStreamHandle));
        $this->assertNull($this->streamingObj->detach());
    }

    public function testClose(){
        $this->streamingObj->close();
        $this->assertFalse(isset($this->underlyingStream->handle));
    }

    public function testEof(){
        $this->streamingObj->read(self::MINIMAL_READ_BLOCK_SIZE * 10);
        $this->assertTrue($this->streamingObj->eof());
    }

    public function testGetSize(){
        $this->streamingObj->read(self::MINIMAL_READ_BLOCK_SIZE);
        $this->assertEquals($this->underlyingStream->getSize(), $this->streamingObj->getSize());
        $this->streamingObj->read(self::MINIMAL_READ_BLOCK_SIZE*10);
        $this->assertEquals($this->underlyingStream->getSize(), $this->streamingObj->getSize());
    }

    public function testTell(){
        $this->streamingObj->read(self::MINIMAL_READ_BLOCK_SIZE);
        $this->assertEquals(self::MINIMAL_READ_BLOCK_SIZE, $this->streamingObj->tell());
    }


    public function testSeek(){
        //Тест сценариев использования seek потока temp
        //1. Поставить курсор в позицию в пределах данных исходного потока
        $this->streamingObj->seek(self::MINIMAL_READ_BLOCK_SIZE * 2);
        $this->assertEquals(self::MINIMAL_READ_BLOCK_SIZE * 2, $this->streamingObj->tell());
        //2. Переставить курсор в позицию имеющихся данных в потоке temp
        $this->streamingObj->seek(self::MINIMAL_READ_BLOCK_SIZE);
        $this->assertEquals(self::MINIMAL_READ_BLOCK_SIZE, $this->streamingObj->tell());
        //3. Переставить курсор за границы файла исходного потока и перевести исходный поток в eof
        $this->streamingObj->seek(self::MINIMAL_READ_BLOCK_SIZE * 10);
        $this->assertEquals($this->underlyingStream->getSize(), $this->streamingObj->tell());

        //Тесты whence
        $this->streamingObj->seek(self::MINIMAL_READ_BLOCK_SIZE);
        $this->streamingObj->seek(self::MINIMAL_READ_BLOCK_SIZE, SEEK_CUR);
        $this->assertEquals(2*self::MINIMAL_READ_BLOCK_SIZE, $this->streamingObj->tell());
        
        $this->streamingObj->seek($this->underlyingStream->getSize());
        $this->streamingObj->seek(-self::MINIMAL_READ_BLOCK_SIZE, SEEK_END);
        $this->assertEquals($this->underlyingStream->getSize() - self::MINIMAL_READ_BLOCK_SIZE, $this->streamingObj->tell());
    }

    public function testRewind(){
        $this->streamingObj->seek(self::MINIMAL_READ_BLOCK_SIZE);
        $this->assertEquals(self::MINIMAL_READ_BLOCK_SIZE, $this->streamingObj->tell());
        $this->streamingObj->rewind();
        $this->assertEquals(0, $this->streamingObj->tell());
    }

    public function testGetContents(){
        $getContentsData = $this->streamingObj->seek(self::MINIMAL_READ_BLOCK_SIZE);

        $getContentsData = $this->streamingObj->GetContents();

        $targetLength = (floor($this->underlyingStream->getSize() / self::MINIMAL_READ_BLOCK_SIZE) - 1) * (self::MINIMAL_READ_BLOCK_SIZE + 10);
        $reminder = ($this->underlyingStream->getSize() % self::MINIMAL_READ_BLOCK_SIZE);
        if ($reminder > 0){
            $targetLength += $reminder; //прибавляем остаток
            if ($reminder % 16 != 0){
                $targetLength += 16 - $reminder % 16; //прибавляем padding
            }
            $targetLength += 10; //прибавляем hmac
        }
        $targetLength = (int)$targetLength;

        $this->assertEquals($targetLength, strlen($getContentsData));
    }

    public function testToString(){
        $getContentsData = $this->streamingObj->__toString();

        $targetLength = floor($this->underlyingStream->getSize() / self::MINIMAL_READ_BLOCK_SIZE) * (self::MINIMAL_READ_BLOCK_SIZE + 10);
        $reminder = ($this->underlyingStream->getSize() % self::MINIMAL_READ_BLOCK_SIZE);
        if ($reminder > 0){
            $targetLength += $reminder; //прибавляем остаток
            if ($reminder % 16 != 0){
                $targetLength += 16 - $reminder % 16; //прибавляем padding
            }
            $targetLength += 10; //прибавляем hmac
        }
        $targetLength = (int)$targetLength;

        $this->assertEquals($targetLength, strlen($getContentsData));
    }

    public function testStreaming(){

        $sidecarOutputFile = "outputs/sidecar.bin";
        file_put_contents($sidecarOutputFile, "");

        while (!$this->streamingObj->eof()){
            $data = $this->streamingObj->read(self::MINIMAL_READ_BLOCK_SIZE);
            file_put_contents($sidecarOutputFile, substr($data, -10), FILE_APPEND);
        }

        //Тест размера файла Temp после стриминга всех данных
        $this->assertEquals($this->underlyingStream->getSize(), $this->streamingObj->getTempFileSize());

        //Тест sidecar
        // $this->assertFileEquals("samples/VIDEO.sidecar", $sidecarOutputFile);
    }

    public function testConstantFields(){
        $this->assertTrue($this->streamingObj->isSeekable());
        $this->assertTrue($this->streamingObj->isReadable());
        $this->assertFalse($this->streamingObj->isWritable());
    }

    public function testGetMetaData(){
        $this->assertEquals(self::$referenceSample, $this->streamingObj->getMetaData("uri"));
        $this->streamingObj->detach();
        $this->assertIsArray($this->streamingObj->getMetaData());
        $this->assertNull($this->streamingObj->getMetaData("uri"));
    }

}
