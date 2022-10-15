<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Utils;

interface RandomProvider
{

    /**
     * @param int<1, max> $length
     * @return non-empty-string
     */
    public function generateAlphanumeric(int $length): string;

}
