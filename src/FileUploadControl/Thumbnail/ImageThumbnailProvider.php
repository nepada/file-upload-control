<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Thumbnail;

use Nette;
use Nette\Http\FileUpload;
use Nette\Utils\Image;
use function str_ends_with;

final class ImageThumbnailProvider implements ThumbnailProvider
{

    use Nette\SmartObject;

    public const DEFAULT_WIDTH = 200;
    public const DEFAULT_HEIGHT = 150;

    public function __construct(
        private ImageLoader $imageLoader,
        private int $width = self::DEFAULT_WIDTH,
        private int $height = self::DEFAULT_HEIGHT,
    )
    {
    }

    public function isSupported(FileUpload $fileUpload): bool
    {
        return $fileUpload->isImage();
    }

    public function createThumbnail(FileUpload $fileUpload): ThumbnailResponse
    {
        if (! $this->isSupported($fileUpload)) {
            throw new \InvalidArgumentException('Only image file uploads are supported.');
        }

        $image = $this->imageLoader->load($fileUpload->getTemporaryFile());
        $image->resize($this->width, $this->height, Image::ShrinkOnly);

        $name = $fileUpload->getUntrustedName();
        if (in_array($fileUpload->getContentType(), ['image/gif', 'image/png'], true)) {
            $contents = $image->toString(Image::PNG);
            $contentType = 'image/png';
            if (! str_ends_with($name, '.png')) {
                $name .= '.png';
            }

        } else {
            $contents = $image->toString(Image::JPEG);
            $contentType = 'image/jpeg';
            if (! str_ends_with($name, '.jpg')) {
                $name .= '.jpg';
            }
        }

        return new ThumbnailResponse($contents, $name, $contentType);
    }

}
