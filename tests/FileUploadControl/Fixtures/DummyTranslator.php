<?php
declare(strict_types = 1);

namespace NepadaTests\FileUploadControl\Fixtures;

use Nette;

final class DummyTranslator implements Nette\Localization\Translator
{

    public function translate(string|\Stringable $message, mixed ...$parameters): string
    {
        return 'translated:' . (string) $message;
    }

}
