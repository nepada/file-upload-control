<?php
declare(strict_types = 1);

namespace NepadaTests\FileUploadControl\Fixtures;

use Nette;

class DummyTranslator implements Nette\Localization\Translator
{

    use Nette\SmartObject;

    public function translate(mixed $message, mixed ...$parameters): string
    {
        return 'translated:' . (string) $message;
    }

}
