<?php
declare(strict_types = 1);

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;

$config = ['parameters' => ['ignoreErrors' => []]];

if (InstalledVersions::satisfies(new VersionParser(), 'nette/component-model', '<3.1.4')) {
    $config['parameters']['ignoreErrors'][] = [
        'message' => '#^Method Nepada\\\\FileUploadControl\\\\BaseControl::getForm\\(\\) should return Nette\\\\Forms\\\\Form\\|null but returns Nette\\\\ComponentModel\\\\IComponent\\|null\\.$#',
        'path' => __DIR__ . '/../../src/FileUploadControl/BaseControl.php',
        'count' => 1,
    ];
}

if (! InstalledVersions::satisfies(new VersionParser(), 'nette/forms', '<=3.2.8')) {
    $config['parameters']['ignoreErrors'][] = [
        'message' => '~^Parameter \\#2 \\$callback of static method Nette\\\\Forms\\\\Container\\:\\:extensionMethod\\(\\) expects callable\\(Nette\\\\Forms\\\\Container\\)\\: mixed, Closure\\(Nette\\\\Forms\\\\Container, int\\|string, Nette\\\\Utils\\\\Html\\|string\\|null\\=\\)\\: Nepada\\\\FileUploadControl\\\\FileUploadControl given\\.$~',
        'path' => __DIR__ . '/../../src/Bridges/FileUploadControlForms/ExtensionMethodRegistrator.php',
        'count' => 1,
    ];

    $config['parameters']['ignoreErrors'][] = [
        'message' => '#^Parameter \\#1 \\$class \\(class\\-string\\<T of Nette\\\\Bridges\\\\ApplicationLatte\\\\Template\\>\\|null\\) of method Nepada\\\\FileUploadControl\\\\FileUploadControl\\:\\:createTemplate\\(\\) should be contravariant with parameter \\$class \\(class\\-string\\<Nette\\\\Application\\\\UI\\\\Template\\>\\|null\\) of method Nette\\\\Application\\\\UI\\\\Control\\:\\:createTemplate\\(\\)$#',
        'path' => __DIR__ . '/../../src/FileUploadControl/FileUploadControl.php',
        'count' => 1,
    ];

    $config['parameters']['ignoreErrors'][] = [
        'message' => '#^Parameter \\#1 \\$validator of method Nette\\\\Forms\\\\Rules\\:\\:addRule\\(\\) expects \\(callable\\(Nette\\\\Forms\\\\Control.*\\)\\: bool\\)\\|string, Closure\\(Nepada\\\\FileUploadControl\\\\Validation\\\\FakeUploadControl\\)\\: bool given\\.$#',
        'path' => __DIR__ . '/../../src/FileUploadControl/FileUploadControl.php',
        'count' => 1,
    ];
}

if (InstalledVersions::satisfies(new VersionParser(), 'nette/application', '>=3.2.0, <=3.2.9')) {
    $config['parameters']['ignoreErrors'][] = [
        'message' => '#^Parameter \\#1 \\$class \\(class\\-string\\<T of Nette\\\\Bridges\\\\ApplicationLatte\\\\Template\\>\\|null\\) of method Nepada\\\\FileUploadControl\\\\FileUploadControl\\:\\:createTemplate\\(\\) should be contravariant with parameter \\$class \\(string\\|null\\) of method Nette\\\\Application\\\\UI\\\\Control\\:\\:createTemplate\\(\\)$#',
        'path' => __DIR__ . '/../../src/FileUploadControl/FileUploadControl.php',
        'count' => 1,
    ];
}

return $config;
