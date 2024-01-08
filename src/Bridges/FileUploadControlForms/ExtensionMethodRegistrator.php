<?php
declare(strict_types = 1);

namespace Nepada\Bridges\FileUploadControlForms;

use Nepada\FileUploadControl\FileUploadControl;
use Nepada\FileUploadControl\FileUploadControlFactory;
use Nette;
use Nette\Forms\Container;
use Nette\Utils\Html;

class ExtensionMethodRegistrator
{

    use Nette\StaticClass;

    public static function register(FileUploadControlFactory $factory): void
    {
        Container::extensionMethod(
            'addFileUpload',
            function (Container $container, string|int $name, string|Html|null $label = null) use ($factory): FileUploadControl {
                $control = $factory->create($label);
                $container->addComponent($control, (string) $name);
                return $control;
            },
        );
    }

}
