<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Thumbnail;

use Nette;
use Nette\Utils\Image;

final class ImageLoader
{

    use Nette\SmartObject;

    private bool $autoRotate;

    public function __construct(bool $autoRotate = true)
    {
        $this->autoRotate = $autoRotate;
    }

    public function load(string $path): Image
    {
        $image = Image::fromFile($path);
        if (! $this->autoRotate) {
            return $image;
        }

        $exifData = @exif_read_data($path);
        if ($exifData === false) {
            return $image;
        }

        $orientation = $exifData['Orientation'] ?? 1;
        switch ($orientation) {
            case 2:
                $image->resize('-100%', null);
                break;
            case 3:
                $image->resize('-100%', '-100%');
                break;
            case 4:
                $image->resize(null, '-100%');
                break;
            /** @noinspection PhpMissingBreakStatementInspection */
            case 5:
                $image->resize(null, '-100%');
                // intentionally no break
            case 6:
                $image->rotate(-90, Image::rgb(0, 0, 0, 127));
                break;
            /** @noinspection PhpMissingBreakStatementInspection */
            case 7:
                $image->resize(null, '-100%');
                // intentionally no break
            case 8:
                $image->rotate(90, Image::rgb(0, 0, 0, 127));
                break;
        }

        return $image;
    }

}
