<?php
declare(strict_types = 1);

namespace NepadaTests\FileUploadControl\Storage;

use Nepada\FileUploadControl\Storage\ContentRange;
use Nepada\FileUploadControl\Storage\FileUploadChunk;
use NepadaTests\TestCase;
use Nette\Http\FileUpload;
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
        $fileUpload = new FileUpload([
            'tmp_name' => 'tmp_name',
            'name' => 'name',
            'size' => $size,
            'error' => UPLOAD_ERR_OK,
        ]);

        $chunk = FileUploadChunk::completeUpload($fileUpload);
        Assert::same($fileUpload, $chunk->getFileUpload());
        Assert::same($size, $chunk->getContentRange()->getSize());
        Assert::true($chunk->getContentRange()->containsFirstByte());
        Assert::true($chunk->getContentRange()->containsLastByte());
    }

    public function testPartialUpload(): void
    {
        $size = 10;
        $fileUpload = new FileUpload([
            'tmp_name' => 'tmp_name',
            'name' => 'name',
            'size' => $size,
            'error' => UPLOAD_ERR_OK,
        ]);
        $contentRange = ContentRange::fromHttpHeaderValue('bytes 0-9/42');

        $chunk = FileUploadChunk::partialUpload($fileUpload, $contentRange);
        Assert::same($fileUpload, $chunk->getFileUpload());
        Assert::same($contentRange, $chunk->getContentRange());
    }

    public function testContentRangeMismatch(): void
    {
        $fileUpload = new FileUpload([
            'tmp_name' => 'tmp_name',
            'name' => 'name',
            'size' => 666,
            'error' => UPLOAD_ERR_OK,
        ]);
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
