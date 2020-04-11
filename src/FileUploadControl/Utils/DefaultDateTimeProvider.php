<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Utils;

use Nette;

final class DefaultDateTimeProvider implements DateTimeProvider
{

    use Nette\SmartObject;

    public function getNow(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }

}
