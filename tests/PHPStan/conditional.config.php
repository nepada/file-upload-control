<?php
declare(strict_types = 1);

use Composer\Semver\VersionParser;

$config = [];

// Bypass standard composer API because of collision with libraries bundled inside phpstan.phar
$installed = require __DIR__ . '/../../vendor/composer/installed.php';
$versionParser = new VersionParser();
$isInstalled = function (string $packageName, string $versionConstraint) use ($versionParser, $installed): bool {
    $constraint = $versionParser->parseConstraints($versionConstraint);
    $provided = $versionParser->parseConstraints($installed['versions'][$packageName]['pretty_version']);
    return $provided->matches($constraint);
};

if ($isInstalled('nette/utils', '>=4.0.3') || $isInstalled('nette/utils', 'dev-master')) {
    $config['parameters']['ignoreErrors'][] = [
        'message' => '~^Parameter \\#2 \\$backgroundColor of method Nette\\\\Utils\\\\Image\\:\\:rotate\\(\\) expects .*, array given\\.$~',
        'path' => '../../src/FileUploadControl/Thumbnail/ImageLoader.php',
        'count' => 2,
    ];
}

return $config;
