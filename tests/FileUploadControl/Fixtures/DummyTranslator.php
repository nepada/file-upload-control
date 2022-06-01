<?php
declare(strict_types = 1);

namespace NepadaTests\FileUploadControl\Fixtures;

use Nette;

class DummyTranslator implements Nette\Localization\Translator
{

    use Nette\SmartObject;

    /**
     * @param mixed $message
     * @param mixed ...$parameters
     * @return string
     */
    public function translate($message, ...$parameters): string
    {
        return 'translated:' . (string) $message;
    }

}
