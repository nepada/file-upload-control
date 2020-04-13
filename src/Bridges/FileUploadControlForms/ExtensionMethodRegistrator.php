<?php
declare(strict_types = 1);

namespace Nepada\Bridges\FileUploadControlForms;

use Nepada\FileUploadControl\FileUploadControl;
use Nepada\FileUploadControl\FileUploadControlFactory;
use Nette;
use Nette\Forms\Container;

class ExtensionMethodRegistrator
{

    use Nette\StaticClass;

    public static function register(FileUploadControlFactory $factory): void
    {
        Container::extensionMethod(
            'addFileUpload',
            fn (Container $container, $name, $label = null): FileUploadControl => $container[$name] = $factory->create($label),
        );
    }

}
