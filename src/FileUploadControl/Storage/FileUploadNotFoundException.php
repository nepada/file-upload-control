<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Storage;

class FileUploadNotFoundException extends \RuntimeException
{

    public static function withId(FileUploadId $id): self
    {
        return new self("File upload '{$id->toString()}' not found.");
    }

}
