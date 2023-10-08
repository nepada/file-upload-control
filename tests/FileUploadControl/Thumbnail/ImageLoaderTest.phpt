<?php
declare(strict_types = 1);

namespace NepadaTests\FileUploadControl\Thumbnail;

use Nepada\FileUploadControl\Thumbnail\ImageLoader;
use NepadaTests\TestCase;
use Nette\Utils\Image;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';


/**
 * @testCase
 */
class ImageLoaderTest extends TestCase
{

    /**
     * @dataProvider getImageData
     */
    public function testLoad(string $file, string $expectedColors, bool $autoRotate): void
    {
        $imageLoader = new ImageLoader($autoRotate);
        $image = $imageLoader->load($file);
        Assert::same($expectedColors, $this->toString($image));
    }

    /**
     * @return mixed[]
     */
    protected function getImageData(): array
    {
        return [
            [
                'file' => __DIR__ . '/Fixtures/1.png',
                'expectedColors' => "00BB\n00BB\nGGRR\nGGRR",
                'autoRotate' => true,
            ],
            [
                'file' => __DIR__ . '/Fixtures/1.jpg',
                'expectedColors' => "00BB\n00BB\nGGRR\nGGRR",
                'autoRotate' => true,
            ],
            [
                'file' => __DIR__ . '/Fixtures/2.jpg',
                'expectedColors' => "00BB\n00BB\nGGRR\nGGRR",
                'autoRotate' => true,
            ],
            [
                'file' => __DIR__ . '/Fixtures/3.jpg',
                'expectedColors' => "00BB\n00BB\nGGRR\nGGRR",
                'autoRotate' => true,
            ],
            [
                'file' => __DIR__ . '/Fixtures/4.jpg',
                'expectedColors' => "00BB\n00BB\nGGRR\nGGRR",
                'autoRotate' => true,
            ],
            [
                'file' => __DIR__ . '/Fixtures/5.jpg',
                'expectedColors' => "00BB\n00BB\nGGRR\nGGRR",
                'autoRotate' => true,
            ],
            [
                'file' => __DIR__ . '/Fixtures/6.jpg',
                'expectedColors' => "00BB\n00BB\nGGRR\nGGRR",
                'autoRotate' => true,
            ],
            [
                'file' => __DIR__ . '/Fixtures/7.jpg',
                'expectedColors' => "00BB\n00BB\nGGRR\nGGRR",
                'autoRotate' => true,
            ],
            [
                'file' => __DIR__ . '/Fixtures/8.jpg',
                'expectedColors' => "00BB\n00BB\nGGRR\nGGRR",
                'autoRotate' => true,
            ],
            [
                'file' => __DIR__ . '/Fixtures/1.jpg',
                'expectedColors' => "00BB\n00BB\nGGRR\nGGRR",
                'autoRotate' => false,
            ],
            [
                'file' => __DIR__ . '/Fixtures/2.jpg',
                'expectedColors' => "BB00\nBB00\nRRGG\nRRGG",
                'autoRotate' => false,
            ],
            [
                'file' => __DIR__ . '/Fixtures/3.jpg',
                'expectedColors' => "RRGG\nRRGG\nBB00\nBB00",
                'autoRotate' => false,
            ],
            [
                'file' => __DIR__ . '/Fixtures/4.jpg',
                'expectedColors' => "GGRR\nGGRR\n00BB\n00BB",
                'autoRotate' => false,
            ],
            [
                'file' => __DIR__ . '/Fixtures/5.jpg',
                'expectedColors' => "00GG\n00GG\nBBRR\nBBRR",
                'autoRotate' => false,
            ],
            [
                'file' => __DIR__ . '/Fixtures/6.jpg',
                'expectedColors' => "BBRR\nBBRR\n00GG\n00GG",
                'autoRotate' => false,
            ],
            [
                'file' => __DIR__ . '/Fixtures/7.jpg',
                'expectedColors' => "RRBB\nRRBB\nGG00\nGG00",
                'autoRotate' => false,
            ],
            [
                'file' => __DIR__ . '/Fixtures/8.jpg',
                'expectedColors' => "GG00\nGG00\nRRBB\nRRBB",
                'autoRotate' => false,
            ],
        ];
    }

    private function toString(Image $image): string
    {
        $rows = [];
        for ($y = 0; $y < $image->getHeight(); $y++) {
            $row = '';
            for ($x = 0; $x < $image->getWidth(); $x++) {
                $row .= $this->nameColor($image->colorAt($x, $y));
            }
            $rows[] = $row;
        }
        return implode("\n", $rows);
    }

    /**
     * @return string R, G, B, 0
     */
    private function nameColor(int $color): string
    {
        $hex = sprintf('%06s', dechex($color));

        $red = hexdec(substr($hex, 0, 2));
        if ($red > 127) {
            return 'R';
        }

        $green = hexdec(substr($hex, 2, 2));
        if ($green > 127) {
            return 'G';
        }

        $blue = hexdec(substr($hex, 4, 2));
        if ($blue > 127) {
            return 'B';
        }

        return '0';
    }

}


(new ImageLoaderTest())->run();
