<?php

namespace Oxatoc\StreamCrypto;

use Oxatoc\StreamCryptoTests\UnderlyingStreamClass;

use PHPUnit\Framework\TestCase;

class EncryptionTest extends TestCase{

    private static $OUTPUT_FILES_FOLDER = "outputs";

    public static function setUpBeforeClass(): void
    {
        if (!(file_exists(self::$OUTPUT_FILES_FOLDER) && is_dir(self::$OUTPUT_FILES_FOLDER))){
            mkdir(self::$OUTPUT_FILES_FOLDER);
        }

    }

    public function testEncryption(){
        $testDataArray = [
            'Audio' => [
                'mediaKeyFile' => 'samples/AUDIO.key'
                , 'mediaDataFile' => 'samples/AUDIO.original'
                , 'referenceSample' => 'samples/AUDIO.encrypted'
                , 'hkdfInfo' => 'WhatsApp Audio Keys'
            ]
            , 'Video' => [
                'mediaKeyFile' => 'samples/VIDEO.key'
                , 'mediaDataFile' => 'samples/VIDEO.original'
                , 'referenceSample' => 'samples/VIDEO.encrypted'
                , 'hkdfInfo' => 'WhatsApp Video Keys'
                ]
            , 'Image' => [
                'mediaKeyFile' => 'samples/IMAGE.key'
                , 'mediaDataFile' => 'samples/IMAGE.original'
                , 'referenceSample' => 'samples/IMAGE.encrypted'
                , 'hkdfInfo' => 'WhatsApp Image Keys'
                ]
        ];

        foreach ($testDataArray as $mediaName => $testData){

            // var_dump("Исходный файл: ".$testData['mediaDataFile']);
            // var_dump("Выходной файл: ".$outputFile);
            // var_dump("Эталонный файл: ".$testData['referenceSample']);


            $outputFile = self::$OUTPUT_FILES_FOLDER."/".$mediaName."_encrypted.bin";
            $mediaKey = file($testData['mediaKeyFile'])[0];

            //Тест метода read()
            $underlyingStream = new UnderlyingStreamClass($testData['mediaDataFile']);
            $cryptoObj = new OpenSSLStreamEncryptClass($mediaKey, $testData['hkdfInfo']);
            $encryptorObj = new DecoratorEncryptionClass($underlyingStream, $cryptoObj);

            file_put_contents($outputFile, "");
            while (!$encryptorObj->eof()){
                file_put_contents($outputFile, $encryptorObj->read(8*1024), FILE_APPEND);
            }
            $encryptorObj->close();
            $this->assertFileEquals($testData['referenceSample'], $outputFile, 'содержание файлов отличается');

            //Тест метода GetContents()

            $underlyingStream = new UnderlyingStreamClass($testData['mediaDataFile']);
            $cryptoObj = new OpenSSLStreamEncryptClass($mediaKey, $testData['hkdfInfo']);
            $encryptorObj = new DecoratorEncryptionClass($underlyingStream, $cryptoObj);

            file_put_contents($outputFile, $encryptorObj->GetContents());
            $encryptorObj->close();
            $this->assertFileEquals($testData['referenceSample'], $outputFile, 'содержание файлов отличается');


            //Тест метода __toString()

            $underlyingStream = new UnderlyingStreamClass($testData['mediaDataFile']);
            $cryptoObj = new OpenSSLStreamEncryptClass($mediaKey, $testData['hkdfInfo']);
            $encryptorObj = new DecoratorEncryptionClass($underlyingStream, $cryptoObj);

            file_put_contents($outputFile, $encryptorObj->__toString());
            $encryptorObj->close();
            $this->assertFileEquals($testData['referenceSample'], $outputFile, 'содержание файлов отличается');


        }

    }

}