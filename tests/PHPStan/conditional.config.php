<?php
declare(strict_types = 1);

$config = [];

if (PHP_VERSION_ID < 8_00_00) {
    // Change of signature in PHP 8.0
    $config['parameters']['ignoreErrors'][] = [
        'message' => '~Casting to int something that\'s already int~',
        'path' => '../../src/FileUploadControl/Storage/FileSystemStorageManager.php',
        'count' => 1,
    ];
}

return $config;
