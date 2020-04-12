<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Thumbnail;

use Nette;
use Nette\Http\FileUpload;
use Nette\Utils\Image;
use Nette\Utils\Strings;

final class ImageThumbnailProvider implements ThumbnailProvider
{

    use Nette\SmartObject;

    public const DEFAULT_WIDTH = 200;
    public const DEFAULT_HEIGHT = 150;

    private ImageLoader $imageLoader;

    private int $width;

    private int $height;

    public function __construct(ImageLoader $imageLoader, int $width = self::DEFAULT_WIDTH, int $height = self::DEFAULT_HEIGHT)
    {
        $this->imageLoader = $imageLoader;
        $this->width = $width;
        $this->height = $height;
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
        $image->resize($this->width, $this->height, Image::SHRINK_ONLY);

        $name = $fileUpload->getName();
        if (in_array($fileUpload->getContentType(), ['image/gif', 'image/png'], true)) {
            $contents = $image->toString(Image::PNG);
            $contentType = 'image/png';
            if (! Strings::endsWith($name, '.png')) {
                $name .= '.png';
            }

        } else {
            $contents = $image->toString(Image::JPEG);
            $contentType = 'image/jpeg';
            if (! Strings::endsWith($name, '.jpg')) {
                $name .= '.jpg';
            }
        }

        return new ThumbnailResponse($contents, $name, $contentType);
    }

}
