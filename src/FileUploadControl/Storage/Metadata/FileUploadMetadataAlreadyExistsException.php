<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Storage\Metadata;

use Nepada\FileUploadControl\Storage\FileUploadId;

class FileUploadMetadataAlreadyExistsException extends \RuntimeException
{

    public static function withId(FileUploadId $id): FileUploadMetadataAlreadyExistsException
    {
        return new self("File upload metadata '{$id->toString()}' already exists.");
    }

}
