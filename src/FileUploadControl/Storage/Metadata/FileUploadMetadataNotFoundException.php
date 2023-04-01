<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Storage\Metadata;

use Nepada\FileUploadControl\Storage\FileUploadId;

class FileUploadMetadataNotFoundException extends \RuntimeException
{

    public static function withId(FileUploadId $id): self
    {
        return new self("File upload metadata '{$id->toString()}' not found.");
    }

}
