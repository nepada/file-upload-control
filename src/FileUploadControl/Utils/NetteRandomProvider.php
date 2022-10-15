<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Utils;

use Nette;
use Nette\Utils\Random;

final class NetteRandomProvider implements RandomProvider
{

    use Nette\SmartObject;

    /**
     * @param int<1, max> $length
     * @return non-empty-string
     */
    public function generateAlphanumeric(int $length): string
    {
        return Random::generate($length, '0-9a-zA-Z');
    }

}
