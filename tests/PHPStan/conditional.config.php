<?php
declare(strict_types = 1);

use Composer\InstalledVersions;
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

if (InstalledVersions::satisfies(new VersionParser(), 'nette/forms', '<3.2')) {
    $config['parameters']['ignoreErrors'][] = [
        'message' => '~contains generic type Nette\\\\Forms\\\\Control<mixed> but interface Nette\\\\Forms\\\\Control is not generic~',
    ];
    $config['parameters']['ignoreErrors'][] = [
        'message' => '#^Cannot cast mixed to string\\.$#',
        'path' => '../../src/FileUploadControl/FileUploadControl.php',
        'count' => 1,
    ];
    $config['parameters']['ignoreErrors'][] = [
        'message' => '#^Return type \\(string\\|Stringable\\|null\\) of method Nepada\\\\FileUploadControl\\\\Validation\\\\FakeUploadControl\\:\\:getCaption\\(\\) should be covariant with return type \\(object\\|string\\) of method Nette\\\\Forms\\\\Controls\\\\BaseControl\\:\\:getCaption\\(\\)$#',
        'path' => '../../src/FileUploadControl/Validation/FakeUploadControl.php',
        'count' => 1,
    ];
    $config['parameters']['ignoreErrors'][] = [
        'message' => '#^Parameter \\#1 \\$message \\(string\\|Stringable\\) of method Nepada\\\\FileUploadControl\\\\Validation\\\\FakeUploadControl\\:\\:addError\\(\\) should be contravariant with parameter \\$message \\(object\\|string\\) of method Nette\\\\Forms\\\\Controls\\\\BaseControl\\:\\:addError\\(\\)$#',
        'path' => '../../src/FileUploadControl/Validation/FakeUploadControl.php',
        'count' => 1,
    ];
    $config['parameters']['ignoreErrors'][] = [
        'message' => '#^Parameter \\#1 \\$control \\(Nette\\\\Forms\\\\Control\\<mixed\\>\\) of method Nepada\\\\FileUploadControl\\\\FileUploadControl\\:\\:addConditionOn\\(\\) should be contravariant with parameter \\$control \\(Nette\\\\Forms\\\\IControl\\) of method Nextras\\\\FormComponents\\\\Fragments\\\\UIControl\\\\BaseControl\\:\\:addConditionOn\\(\\)$#s',
        'path' => '../../src/FileUploadControl/FileUploadControl.php',
        'count' => 1,
    ];
    $config['parameters']['ignoreErrors'][] = [
        'message' => '#^Parameter \\#1 \\$value of method Nette\\\\Forms\\\\Rules\\:\\:setRequired\\(\\) expects bool\\|string, bool\\|string\\|Stringable given\\.$#',
        'path' => '../../src/FileUploadControl/FileUploadControl.php',
        'count' => 1,
    ];
}

return $config;
