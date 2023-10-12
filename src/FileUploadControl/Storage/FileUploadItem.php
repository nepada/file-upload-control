<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Storage;

use Nette;

final class FileUploadItem
{

    use Nette\SmartObject;

    public readonly FileUploadId $id;

    public readonly Nette\Http\FileUpload $fileUpload;

    public function __construct(FileUploadId $id, Nette\Http\FileUpload $fileUpload)
    {
        $this->id = $id;
        $this->fileUpload = $fileUpload;
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
