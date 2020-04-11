<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Utils;

interface RandomProvider
{

    public function generateAlphanumeric(int $length): string;

}
