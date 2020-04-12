<?php
declare(strict_types = 1);

namespace NepadaTests\FileUploadControl\Thumbnail;

use Nepada\FileUploadControl\Thumbnail\ImageLoader;
use Nepada\FileUploadControl\Thumbnail\ImageThumbnailProvider;
use NepadaTests\FileUploadControl\FileUploadFactory;
use NepadaTests\TestCase;
use Nette\Utils\Image;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';


/**
 * @testCase
 */
class ImageThumbnailProviderTest extends TestCase
{

    public function testNotSupported(): void
    {
        $fileUpload = FileUploadFactory::createFromFile(__FILE__);
        $imageThumbnailProvider = new ImageThumbnailProvider(new ImageLoader());

        Assert::false($imageThumbnailProvider->isSupported($fileUpload));
        Assert::exception(
            function () use ($imageThumbnailProvider, $fileUpload): void {
                $imageThumbnailProvider->createThumbnail($fileUpload);
            },
            \InvalidArgumentException::class,
        );
    }

    /**
     * @dataProvider getImageData
     * @param string $file
     * @param string $expectedName
     * @param string $expectedContentType
     * @param int $expectedWidth
     * @param int $expectedHeight
     */
    public function testImages(string $file, string $expectedName, string $expectedContentType, int $expectedWidth, int $expectedHeight): void
    {
        $fileUpload = FileUploadFactory::createFromFile($file);
        $imageThumbnailProvider = new ImageThumbnailProvider(new ImageLoader());

        Assert::true($imageThumbnailProvider->isSupported($fileUpload));
        $response = $imageThumbnailProvider->createThumbnail($fileUpload);
        Assert::same($expectedName, $response->getName());
        Assert::same($expectedContentType, $response->getContentType());
        $image = Image::fromString($response->getContents());
        Assert::same($expectedWidth, $image->getWidth());
        Assert::same($expectedHeight, $image->getHeight());
    }

    /**
     * @return mixed[]
     */
    protected function getImageData(): array
    {
        return [
            [
                'file' => __DIR__ . '/Fixtures/1.jpg',
                'expectedName' => '1.jpg',
                'expectedContentType' => 'image/jpeg',
                'expectedWidth' => 4,
                'expectedHeight' => 4,
            ],
            [
                'file' => __DIR__ . '/Fixtures/landscape.gif',
                'expectedName' => 'landscape.gif.png',
                'expectedContentType' => 'image/png',
                'expectedWidth' => 200,
                'expectedHeight' => 100,
            ],
            [
                'file' => __DIR__ . '/Fixtures/portrait.png',
                'expectedName' => 'portrait.png',
                'expectedContentType' => 'image/png',
                'expectedWidth' => 75,
                'expectedHeight' => 150,
            ],
        ];
    }

}


(new ImageThumbnailProviderTest())->run();
