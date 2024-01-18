<?php
declare(strict_types = 1);

namespace NepadaTests\FileUploadControl\Storage;

use Nepada\FileUploadControl\Storage\ContentRange;
use Nepada\FileUploadControl\Storage\FailedUpload;
use NepadaTests\FileUploadControl\FileUploadFactory;
use NepadaTests\TestCase;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';


/**
 * @testCase
 */
class FailedUploadTest extends TestCase
{

    public function testCreateWithKnownContentRange(): void
    {
        $fileUpload = FileUploadFactory::create('name', 0, 'tmp', UPLOAD_ERR_NO_TMP_DIR);
        $contentRange = ContentRange::fromHttpHeaderValue('bytes 0-9/42');
        $failedUpload = FailedUpload::of($fileUpload, $contentRange);
        Assert::same($fileUpload, $failedUpload->fileUpload);
        Assert::same($contentRange, $failedUpload->contentRange);
    }

    public function testCreateWithContentRangeDerivedFromSize(): void
    {
        $fileUpload = FileUploadFactory::create('name', 42, 'tmp', UPLOAD_ERR_PARTIAL);
        $failedUpload = FailedUpload::of($fileUpload);
        Assert::same($fileUpload, $failedUpload->fileUpload);
        Assert::notNull($failedUpload->contentRange);
        Assert::same(42, $failedUpload->contentRange->getSize());
    }

    public function testCreateWithoutContent(): void
    {
        $fileUpload = FileUploadFactory::create('name', 0, 'tmp', UPLOAD_ERR_NO_TMP_DIR);
        $failedUpload = FailedUpload::of($fileUpload);
        Assert::same($fileUpload, $failedUpload->fileUpload);
        Assert::null($failedUpload->contentRange);
    }

    public function testOkFileUploadIsRejected(): void
    {
        $fileUpload = FileUploadFactory::create('name', 42, 'tmp', UPLOAD_ERR_OK);
        Assert::exception(
            function () use ($fileUpload): void {
                FailedUpload::of($fileUpload);
            },
            \InvalidArgumentException::class,
            "Expected failed file upload, but upload of 'name' has been successful.",
        );
    }

}


(new FailedUploadTest())->run();
