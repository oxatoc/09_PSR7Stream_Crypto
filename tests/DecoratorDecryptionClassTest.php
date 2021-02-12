<?php

namespace Oxatoc\StreamCrypto;

use Oxatoc\StreamCryptoTests\UnderlyingStreamClass;

use PHPUnit\Framework\TestCase;

class DecryptionTest extends TestCase{

    private static $OUTPUT_FILES_FOLDER = "outputs";

    private static $testDataArray;

    public static function setUpBeforeClass(): void
    {
        if (!(file_exists(self::$OUTPUT_FILES_FOLDER) && is_dir(self::$OUTPUT_FILES_FOLDER))){
            mkdir(self::$OUTPUT_FILES_FOLDER);
        }

        self::$testDataArray = [
            'Audio' => [
                'mediaKeyFile' => 'samples/AUDIO.key'
                , 'mediaDataFile' => 'samples/AUDIO.encrypted'
                , 'referenceSample' => 'samples/AUDIO.original'
                , 'hkdfInfo' => 'WhatsApp Audio Keys'
            ]
            , 'Video' => [
                'mediaKeyFile' => 'samples/VIDEO.key'
                , 'mediaDataFile' => 'samples/VIDEO.encrypted'
                , 'referenceSample' => 'samples/VIDEO.original'
                , 'hkdfInfo' => 'WhatsApp Video Keys'
                ]
            , 'Image' => [
                'mediaKeyFile' => 'samples/IMAGE.key'
                , 'mediaDataFile' => 'samples/IMAGE.encrypted'
                , 'referenceSample' => 'samples/IMAGE.original'
                , 'hkdfInfo' => 'WhatsApp Image Keys'
                ]
        ];
    }

    public function testDecryption(){

        foreach (self::$testDataArray as $mediaName => $testData){

            // var_dump("Исходный файл: ".$testData['mediaDataFile']);
            // var_dump("Выходной файл: ".$outputFile);
            // var_dump("Эталонный файл: ".$testData['referenceSample']);

            $outputFile = self::$OUTPUT_FILES_FOLDER."/".$mediaName."_decrypted.bin";
            $mediaKey = file($testData['mediaKeyFile'])[0];

            //Тест метода read()
            $underlyingStream = new UnderlyingStreamClass($testData['mediaDataFile']);
            $cryptoObj = new OpenSSLStreamDecryptClass($mediaKey, $testData['hkdfInfo']);
            $decryptorObj = new DecoratorDecryptionClass($underlyingStream, $cryptoObj);

            file_put_contents($outputFile, "");
            while (!$decryptorObj->eof()){
                file_put_contents($outputFile, $decryptorObj->read(8*1024), FILE_APPEND);
            }
            $decryptorObj->close();
            $this->assertFileEquals($testData['referenceSample'], $outputFile, 'содержание файлов отличается');

            //Тест метода GetContents()

            $underlyingStream = new UnderlyingStreamClass($testData['mediaDataFile']);
            $cryptoObj = new OpenSSLStreamDecryptClass($mediaKey, $testData['hkdfInfo']);
            $decryptorObj = new DecoratorDecryptionClass($underlyingStream, $cryptoObj);

            file_put_contents($outputFile, $decryptorObj->GetContents());
            $decryptorObj->close();
            $this->assertFileEquals($testData['referenceSample'], $outputFile, 'содержание файлов отличается');


            //Тест метода __toString()

            $underlyingStream = new UnderlyingStreamClass($testData['mediaDataFile']);
            $cryptoObj = new OpenSSLStreamDecryptClass($mediaKey, $testData['hkdfInfo']);
            $decryptorObj = new DecoratorDecryptionClass($underlyingStream, $cryptoObj);

            file_put_contents($outputFile, $decryptorObj->__toString());
            $decryptorObj->close();
            $this->assertFileEquals($testData['referenceSample'], $outputFile, 'содержание файлов отличается');
        }
    }



}