<?php
declare(strict_types = 1);

use Nette\Bridges\ApplicationLatte\LatteFactory;

$config = [];

if (interface_exists(LatteFactory::class)) {
    // Interface renamed in nette/application 3.1
    $config['parameters']['ignoreErrors'][] = [
        'message' => '~Method NepadaTests\\\\FileUploadControl\\\\Fixtures\\\\TestPresenter::createLatteFactory\\(\\) should return Nette\\\\Bridges\\\\ApplicationLatte\\\\LatteFactory but returns class@anonymous.*~',
        'path' => '../../tests/FileUploadControl/Fixtures/TestPresenter.php',
        'count' => 1,
    ];
}

return $config;
