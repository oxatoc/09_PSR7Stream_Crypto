### Цели проекта
- есть реализации интерфейсов потоков PSR-7, необходимо для них создать декораторы, осуществляющие:
    - шифрование/дешифрование содержимого потока целиком
    - шифрование потока блоками по 64 кБ для стриминга
### Сценарии использования
- зашифрованный поток данных (из файла, из сокета) расшифровывать в декораторе, валидировать после получения всех данных
- незашифрованный поток данных шифровать в декораторе, приложить блок данных для валидации
- незашифрованный поток данных кэшировать и зашифровывать в декораторе блоками по 64 кБ, к каждому блоку прикладывать данные для валидации; переставлять курсор на любую позицию данных в кэше, выдавать зашифрованные блоки начиная с этой позиции, без повторного чтения исходного потока
### Результаты проекта
- криптография:
    - алгоритм - AES-256-CBC
    - параметры алгоритма - аналогичны параметрам шифрования Whatsapp
    - padding - по PKCS7
- исполняемые модули:
    - размещение - в каталоге "src/"
    - реализация методов интерфейса потоков PSR-7 - на базе модулей [Guzzle](https://github.com/guzzle/psr7)
    - архитектура:
        - `DecoratorBaseClass` - базовый класс декораторов интерфейса потока PSR-7, содержит реализацию общих методов
        - `DecoratorEncryptionClass` - декоратор шифрования потока целиком, переопределяющий методы базового класса
        - `DecoratorDecryptionClass`- декоратор дешифрования потока целиком, переопределяющий методы базового класса
        - `DecoratorEncryptionStreamingClass` - декоратор шифрования для стриминга
        - `OpenSSLStreamInterface` - интерфейс алгоритмов шифрования/дешифрования потоков
        - `OpenSSLStreamEncryptClass` - реализация интерфейса - алгоритм шифрования потока целиком
        - `OpenSSLStreamDecryptClass` - реализация интерфейса - алгоритм дешифрования потока целиком
        - `OpenSSLStreamingEncryptClass` - реализация интерфейса - алгоритм шифрования блоков по 64 кБ для стриминга
        - цель создания общего интерфейса алгоритмов шифрования/дешифрования - иметь возможность создавать реализации любых других алгоритмов
    - выходная структура данных:
        - данные, прошедшие алгоритм шифрования/дешифрования
        - информация для валидации - первые 10 байт хэша HMAC SHA-256
- допущения при разработке:
    - длина блока данных, читаемого из исходного потока методом `read()`, задается кратно 16 байтам, иначе функция библиотеки `OpenSSL` автоматически будет добавлять байты padding-а во все промежуточные блоки данных
- пригодность для оформления в виде пакета `Composer`:
    - автозагрузка модулей - из структуры каталогов исходного кода по PSR-4
    - модули PSR-7 и PHPUnit добавлены в виде зависимостей от внешних библиотек в файл конфигурации
- функциональные тесты:
    - базовые классы тестов - из библиотеки `PHPUnit`
    - размещение - в каталоге "tests/"
    - `DecoratorBaseClassTest` - тесты базового класса декораторов
    - `DecoratorEncryptionClassTest` - тесты декоратора шифрования
    - `DecoratorDecryptionClassTest - тесты декоратора дешифрования
    - `DecoratorEncryptionStreamingClassTest` - тесты декоратора стриминга
    - `UnderlayingStreamClass` - декоратор интерфейса PSR-7 для проведения функциональных тестов; имитирует исходные зашифрованные и незашифрованные потоки данных путем чтения из эталонных файлов 
- остаточный риск:
    - результат расчета модулем `OpenSSLStreamingEncryptClass` последнего, 7-го, десятка байтов sidecar файла `VIDEO.original` - отличается от 7-го десятка байтов в эталонном файле `VISEO.sidecar`. Все остальные байты совпадают.
    - потенциальная причина - алгоритм шифрования последнего, неполного блока для стриминга, имеет неявные особенности
    - влияние риска - минимально, т.к. алгоритм шифрования в целом подтвержден сравнением с эталонными файлами
### Входные данные
- эталонные файлы в каталоге "samples/":
    - *.original - незашифрованные файлы
    - *.key - ключи для шифрования
    - *.encryptes - зашифрованные файлы
    - значения строк контекста приложения функций HKDF - сохранены в качестве элементов массивов в файлах функциональных тестов
    - *.sidecar - последовательность sidecar-ов файла `VIDEO.original`, разделенного на блоки по 64 кБ

