<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Storage;

use Nette;

final class FileUploadChunk
{

    use Nette\SmartObject;

    public readonly Nette\Http\FileUpload $fileUpload;

    public readonly ContentRange $contentRange;

    private function __construct(Nette\Http\FileUpload $fileUpload, ContentRange $contentRange)
    {
        if (! $fileUpload->isOk()) {
            throw new \InvalidArgumentException("Expected successful file upload, but upload of '{$fileUpload->getUntrustedName()}' has failed with error {$fileUpload->getError()}.");
        }

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

    public static function completeUpload(Nette\Http\FileUpload $fileUpload): self
    {
        return new self($fileUpload, ContentRange::ofSize($fileUpload->getSize()));
    }

    public static function partialUpload(Nette\Http\FileUpload $fileUpload, ContentRange $contentRange): self
    {
        return new self($fileUpload, $contentRange);
    }

    /**
     * @deprecated read the property directly instead
     */
    public function getFileUpload(): Nette\Http\FileUpload
    {
        return $this->fileUpload;
    }

    /**
     * @deprecated read the property directly instead
     */
    public function getContentRange(): ContentRange
    {
        return $this->contentRange;
    }

}
