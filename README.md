## File Storage Client

1. Установка 

```bash

composer require consultnn/file-storage-client:1.0.x-dev

```

2. Настройка

```php

$client = new \consultnn\filestorage\client\Client(
    'projectName',
    'someUploadToken',
    'downloadSignKey',
    'http://filestorageserver.com'
);

// Загрузка изображения

$result = $client->upload('/tmp/imagefile.jpg');

// Получение ссылки на изображение

$fileName = current($result);

$fileAbsoluteUrl = $client->makeUrl($fileName, ['w' => 250]);

echo '<img src="{$fileAbsoluteUrl}" width="250"/>';
```