<?php
declare(strict_types = 1);

namespace NepadaTests\FileUploadControl\Storage;

use Nepada\FileUploadControl\Storage\ContentRange;
use Nepada\FileUploadControl\Storage\FileUploadChunk;
use NepadaTests\FileUploadControl\FileUploadFactory;
use NepadaTests\TestCase;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';


/**
 * @testCase
 */
class FileUploadChunkTest extends TestCase
{

    public function testCompleteUpload(): void
    {
        $size = 42;
        $fileUpload = FileUploadFactory::createWithSize($size);

        $chunk = FileUploadChunk::completeUpload($fileUpload);
        Assert::same($fileUpload, $chunk->getFileUpload());
        Assert::same($size, $chunk->getContentRange()->getSize());
        Assert::true($chunk->getContentRange()->containsFirstByte());
        Assert::true($chunk->getContentRange()->containsLastByte());
    }

    public function testPartialUpload(): void
    {
        $size = 10;
        $fileUpload = FileUploadFactory::createWithSize($size);
        $contentRange = ContentRange::fromHttpHeaderValue('bytes 0-9/42');

        $chunk = FileUploadChunk::partialUpload($fileUpload, $contentRange);
        Assert::same($fileUpload, $chunk->getFileUpload());
        Assert::same($contentRange, $chunk->getContentRange());
    }

    public function testContentRangeMismatch(): void
    {
        $fileUpload = FileUploadFactory::createWithSize(666);
        $contentRange = ContentRange::fromHttpHeaderValue('bytes 0-9/42');

        Assert::exception(
            function () use ($fileUpload, $contentRange): void {
                FileUploadChunk::partialUpload($fileUpload, $contentRange);
            },
            \InvalidArgumentException::class,
            'Content range size of 10 does not match file upload size 666.',
        );
    }

}


(new FileUploadChunkTest())->run();
