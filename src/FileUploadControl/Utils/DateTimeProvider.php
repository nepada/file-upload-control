<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Utils;

interface DateTimeProvider
{

    public function getNow(): \DateTimeImmutable;

}
