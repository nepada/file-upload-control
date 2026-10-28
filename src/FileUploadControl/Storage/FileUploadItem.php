<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Storage;

use Nette;

final class FileUploadItem
{

    use Nette\SmartObject;

    public function __construct(
        public readonly FileUploadId $id,
        public readonly Nette\Http\FileUpload $fileUpload,
    )
    {
    }

    /**
     * @deprecated read the property directly instead
     */
    public function getId(): FileUploadId
    {
        return $this->id;
    }

    /**
     * @deprecated read the property directly instead
     */
    public function getFileUpload(): Nette\Http\FileUpload
    {
        return $this->fileUpload;
    }

}
