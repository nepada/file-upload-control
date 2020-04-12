<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Thumbnail;

use Nette;
use Nette\Http\FileUpload;

final class NullThumbnailProvider implements ThumbnailProvider
{

    use Nette\SmartObject;

    public function isSupported(FileUpload $fileUpload): bool
    {
        return false;
    }

    public function createThumbnail(FileUpload $fileUpload): ThumbnailResponse
    {
        throw new \InvalidArgumentException('Thumbnails are not supported.');
    }

}
