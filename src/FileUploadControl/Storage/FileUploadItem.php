<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Storage;

use Nette;

final class FileUploadItem
{

    use Nette\SmartObject;

    private FileUploadId $id;

    private Nette\Http\FileUpload $fileUpload;

    public function __construct(FileUploadId $id, Nette\Http\FileUpload $fileUpload)
    {
        $this->id = $id;
        $this->fileUpload = $fileUpload;
    }

    public function getId(): FileUploadId
    {
        return $this->id;
    }

    public function getFileUpload(): Nette\Http\FileUpload
    {
        return $this->fileUpload;
    }

}
