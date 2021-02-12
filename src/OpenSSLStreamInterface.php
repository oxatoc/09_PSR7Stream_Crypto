<?php

namespace Oxatoc\StreamCrypto;

interface OpenSSLStreamInterface{

    /**
     * Общий интерфейс для шифроваия/дешифрования функциями openssl потоков целиком (без стриминга)
     * Реалиация вариабельности - применение различных алгоритмов фшифрования в потоках PSR-7
     */

     /**
     * Возврат результата выполнения функции openssl
     *
     * @return string
     */
    public function getOpensslData($data);


     /**
     * Возврат hmac
     *
     * @return string
     */

    public function getHmac();
}