<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Storage;

use Nette;

final class FailedUpload
{

    use Nette\SmartObject;

    public readonly Nette\Http\FileUpload $fileUpload;

    public readonly ?ContentRange $contentRange;

    private function __construct(Nette\Http\FileUpload $fileUpload, ?ContentRange $contentRange)
    {
        if ($fileUpload->isOk()) {
            throw new \InvalidArgumentException("Expected failed file upload, but upload of '{$fileUpload->getUntrustedName()}' has been successful.");
        }

        $this->fileUpload = $fileUpload;
        $this->contentRange = $contentRange;
    }

    public static function of(Nette\Http\FileUpload $fileUpload, ?ContentRange $contentRange = null): self
    {
        if ($contentRange === null && $fileUpload->getSize() > 0) {
            $contentRange = ContentRange::ofSize($fileUpload->getSize());
        }

        return new self($fileUpload, $contentRange);
    }

}
