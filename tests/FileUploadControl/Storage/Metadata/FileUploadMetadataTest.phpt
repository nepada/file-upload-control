<?php
declare(strict_types = 1);

namespace NepadaTests\FileUploadControl\Storage\Metadata;

use Nepada\FileUploadControl\Storage\ContentRange;
use Nepada\FileUploadControl\Storage\FileUploadChunk;
use Nepada\FileUploadControl\Storage\Metadata\FileUploadMetadata;
use NepadaTests\FileUploadControl\FileUploadFactory;
use NepadaTests\TestCase;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';


/**
 * @testCase
 */
class FileUploadMetadataTest extends TestCase
{

    public function testArray(): void
    {
        $name = 'foo';
        $size = 42;
        $metadata = FileUploadMetadata::fromArray(['name' => $name, 'size' => $size, 'lorem' => 'ipsum']);
        Assert::same($name, $metadata->getName());
        Assert::same($size, $metadata->getSize());
        Assert::same(['name' => $name, 'size' => $size], $metadata->toArray());
    }

    public function testFromFileUploadAndContentRange(): void
    {
        $name = 'foo';
        $size = 42;
        $fileUpload = FileUploadFactory::createWithSize(2, $name);
        $contentRange = ContentRange::fromHttpHeaderValue('bytes 0-1/' . $size);
        $metadata = FileUploadMetadata::fromFileUploadChunk(FileUploadChunk::partialUpload($fileUpload, $contentRange));
        Assert::same($name, $metadata->getName());
        Assert::same($size, $metadata->getSize());
    }

    public function testEquals(): void
    {
        Assert::true(
            FileUploadMetadata::fromArray(['name' => 'foo', 'size' => 1])
                ->equals(FileUploadMetadata::fromArray(['name' => 'foo', 'size' => 1])),
        );
        Assert::false(
            FileUploadMetadata::fromArray(['name' => 'foo', 'size' => 1])
                ->equals(FileUploadMetadata::fromArray(['name' => 'foo', 'size' => 2])),
        );
        Assert::false(
            FileUploadMetadata::fromArray(['name' => 'foo', 'size' => 1])
                ->equals(FileUploadMetadata::fromArray(['name' => 'bar', 'size' => 1])),
        );
    }

    public function testCreateFileUploadId(): void
    {
        $id = FileUploadMetadata::fromArray(['name' => 'foo', 'size' => 1])->createFileUploadId();
        Assert::same('myrRdBHgHZfmhpEsR_xfk5y4Uus', $id->toString());
    }

    public function testInvalidSize(): void
    {
        Assert::exception(
            function (): void {
                FileUploadMetadata::fromArray(['name' => 'foo', 'size' => -1]);
            },
            \InvalidArgumentException::class,
            'File upload size cannot be negative.',
        );
    }

}


(new FileUploadMetadataTest())->run();
