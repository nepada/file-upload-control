<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Thumbnail;

use Nette\Http\FileUpload;

interface ThumbnailProvider
{

    public function isSupported(FileUpload $fileUpload): bool;

    public function createThumbnail(FileUpload $fileUpload): ThumbnailResponse;

}
