<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Storage;

class StorageDoesNotExistException extends \RuntimeException
{

    public static function withNamespace(UploadNamespace $namespace): self
    {
        return new self("Storage for namespace '{$namespace->toString()}' does not exist.");
    }

}
