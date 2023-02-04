<?php
declare(strict_types = 1);

$config = [];

if (PHP_VERSION_ID >= 8_00_00) {
    $config['parameters']['ignoreErrors'][] = [
        'message' => '~^Missing native return typehint \\\\Nette\\\\Utils\\\\Html\\|string\\|null$~',
        'path' => '../../src/FileUploadControl/Validation/FakeUploadControl.php',
        'count' => 1,
    ];
}

return $config;
