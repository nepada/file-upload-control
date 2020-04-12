<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Storage;

use Nette;

final class FileUploadChunk
{

    use Nette\SmartObject;

    private Nette\Http\FileUpload $fileUpload;

    private ContentRange $contentRange;

    private function __construct(Nette\Http\FileUpload $fileUpload, ContentRange $contentRange)
    {
        if ($fileUpload->getSize() !== $contentRange->getRangeSize()) {
            throw new \InvalidArgumentException(sprintf(
                'Content range size of %d does not match file upload size %d.',
                $contentRange->getRangeSize(),
                $fileUpload->getSize(),
            ));
        }

        $this->fileUpload = $fileUpload;
        $this->contentRange = $contentRange;
    }

    public static function completeUpload(Nette\Http\FileUpload $fileUpload): FileUploadChunk
    {
        return new self($fileUpload, ContentRange::ofSize($fileUpload->getSize()));
    }

    public static function partialUpload(Nette\Http\FileUpload $fileUpload, ContentRange $contentRange): FileUploadChunk
    {
        return new self($fileUpload, $contentRange);
    }

    public function getFileUpload(): Nette\Http\FileUpload
    {
        return $this->fileUpload;
    }

    public function getContentRange(): ContentRange
    {
        return $this->contentRange;
    }

}
