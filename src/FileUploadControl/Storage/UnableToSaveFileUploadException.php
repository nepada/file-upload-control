<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Storage;

class UnableToSaveFileUploadException extends \RuntimeException
{

    public static function withUploadError(): UnableToSaveFileUploadException
    {
        return new self('Unable to save file upload, because of pre-existing upload error.');
    }

    public static function withConflict(FileUploadId $id): UnableToSaveFileUploadException
    {
        return new self("Unable to save file upload '{$id->toString()}', because of conflict with existing data.");
    }

    public static function withFailedChunk(FileUploadId $id, string $reason): UnableToSaveFileUploadException
    {
        return new self("Unable to continue in file upload '{$id->toString()}': $reason");
    }

}
