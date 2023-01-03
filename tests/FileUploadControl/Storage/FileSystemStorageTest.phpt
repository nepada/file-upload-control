<?php
declare(strict_types = 1);

namespace NepadaTests\FileUploadControl\Storage;

use Nepada\FileUploadControl\Storage\ContentRange;
use Nepada\FileUploadControl\Storage\FileSystemStorage;
use Nepada\FileUploadControl\Storage\FileUploadChunk;
use Nepada\FileUploadControl\Storage\FileUploadNotFoundException;
use Nepada\FileUploadControl\Storage\Metadata\FileUploadMetadata;
use Nepada\FileUploadControl\Storage\UnableToSaveFileUploadException;
use Nepada\FileUploadControl\Utils\NetteFileSystem;
use NepadaTests\Environment;
use NepadaTests\FileUploadControl\FileUploadFactory;
use NepadaTests\FileUploadControl\Storage\Metadata\InMemoryMetadataJournal;
use NepadaTests\TestCase;
use Nette;
use Nette\Utils\Random;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';


/**
 * @testCase
 */
class FileSystemStorageTest extends TestCase
{

    public function testListIgnoresMissingFiles(): void
    {
        $metadata = FileUploadMetadata::fromArray(['name' => 'orphaned.txt', 'size' => 1]);
        $storage = $this->createStorage([$metadata->createFileUploadId()->toString() => $metadata]);
        Assert::same([], $storage->list());
    }

    public function testNotOkFileUploadIsRejected(): void
    {
        $storage = $this->createStorage();
        $chunk = FileUploadChunk::completeUpload(FileUploadFactory::create('name', 42, 'tmp', UPLOAD_ERR_PARTIAL));
        Assert::exception(
            function () use ($storage, $chunk): void {
                $storage->save($chunk);
            },
            UnableToSaveFileUploadException::class,
            'Unable to save file upload, because of pre-existing upload error.',
        );
    }

    public function testAttemptToResumeUploadOfOrphanedMetadataFails(): void
    {
        $metadata = FileUploadMetadata::fromArray(['name' => 'orphaned.txt', 'size' => 42]);
        $storage = $this->createStorage([$metadata->createFileUploadId()->toString() => $metadata]);

        $chunk = FileUploadChunk::partialUpload(FileUploadFactory::createWithContents('Bar', 'orphaned.txt'), ContentRange::fromHttpHeaderValue('bytes 3-5/42'));
        Assert::exception(
            function () use ($storage, $chunk): void {
                $storage->save($chunk);
            },
            UnableToSaveFileUploadException::class,
            "Unable to continue in file upload 'amZD9mYK5ctbvHQ8LW5_f9oydFc': missing previously uploaded file part",
        );
    }

    public function testAttemptToResumeNonExistentUploadFails(): void
    {
        $storage = $this->createStorage();

        $chunk = FileUploadChunk::partialUpload(FileUploadFactory::createWithContents('Bar', 'doesNotExist.txt'), ContentRange::fromHttpHeaderValue('bytes 3-5/42'));
        Assert::exception(
            function () use ($storage, $chunk): void {
                $storage->save($chunk);
            },
            UnableToSaveFileUploadException::class,
            "Unable to continue in file upload 'cxQdOwzyQ6VFS2NSrryizaXuT80': failed to load metadata",
        );
    }

    public function testCompleteUpload(): void
    {
        $storage = $this->createStorage();
        $idValue = 'bh6Y1srroiEubcXek_F9zheSYgM';
        $name = 'foobar.txt';
        $contents = 'FooBar';
        $chunk = FileUploadChunk::completeUpload(FileUploadFactory::createWithContents($contents, $name));

        $savedFileUploadItem = $storage->save($chunk);
        $id = $savedFileUploadItem->getId();
        Assert::same($idValue, $id->toString());
        $fileUpload = $savedFileUploadItem->getFileUpload();
        Assert::same($name, $fileUpload->getUntrustedName());
        Assert::same($contents, $fileUpload->getContents());
        Assert::same(strlen($contents), $fileUpload->getSize());
        Assert::true($fileUpload->isOk());

        // file upload can be loaded after saved
        Assert::equal($savedFileUploadItem, $storage->load($id));
        Assert::equal([$savedFileUploadItem], $storage->list());

        // attempt to save the same file upload fails
        Assert::exception(
            function () use ($storage, $chunk): void {
                $storage->save($chunk);
            },
            UnableToSaveFileUploadException::class,
            "Unable to save file upload '$idValue', because of conflict with existing data.",
        );

        $storage->delete($id);

        // file upload can no longer be loaded after deleted
        Assert::exception(
            function () use ($storage, $id): void {
                $storage->load($id);
            },
            FileUploadNotFoundException::class,
            "File upload metadata '$idValue' not found.",
        );
        Assert::equal([], $storage->list());
    }

    public function testChunkedUpload(): void
    {
        $storage = $this->createStorage();
        $idValue = 'bh6Y1srroiEubcXek_F9zheSYgM';
        $name = 'foobar.txt';

        $chunk1 = FileUploadChunk::partialUpload(FileUploadFactory::createWithContents('Foo', $name), ContentRange::fromHttpHeaderValue('bytes 0-2/6'));
        $savedUploadItemChunk1 = $storage->save($chunk1);
        $id = $savedUploadItemChunk1->getId();
        Assert::same($idValue, $id->toString());
        $partialFileUpload = $savedUploadItemChunk1->getFileUpload();
        Assert::same($name, $partialFileUpload->getUntrustedName());
        Assert::same(6, $partialFileUpload->getSize());
        Assert::false($partialFileUpload->isOk());
        Assert::same(UPLOAD_ERR_PARTIAL, $partialFileUpload->getError());

        // file upload chunk can be loaded after saved
        Assert::equal($savedUploadItemChunk1, $storage->load($id));
        Assert::equal([$savedUploadItemChunk1], $storage->list());

        // attempt to save the same file upload fails
        Assert::exception(
            function () use ($storage, $chunk1): void {
                $storage->save($chunk1);
            },
            UnableToSaveFileUploadException::class,
            "Unable to save file upload '$idValue', because of conflict with existing data.",
        );

        // attempt to resume upload at wrong offset fails
        $chunkInvalid = FileUploadChunk::partialUpload(FileUploadFactory::createWithContents('r', $name), ContentRange::fromHttpHeaderValue('bytes 5-5/6'));
        Assert::exception(
            function () use ($storage, $chunkInvalid): void {
                $storage->save($chunkInvalid);
            },
            UnableToSaveFileUploadException::class,
            "Unable to continue in file upload '$idValue': previously uploaded file part size does not match given content-range value",
        );

        // successfully complete upload
        $chunk2 = FileUploadChunk::partialUpload(FileUploadFactory::createWithContents('Bar', $name), ContentRange::fromHttpHeaderValue('bytes 3-5/6'));
        $savedUploadItemChunk2 = $storage->save($chunk2);
        Assert::equal($id, $savedUploadItemChunk2->getId());
        $completedFileUpload = $savedUploadItemChunk2->getFileUpload();
        Assert::same($name, $completedFileUpload->getUntrustedName());
        Assert::same(6, $completedFileUpload->getSize());
        Assert::true($completedFileUpload->isOk());
        Assert::same('FooBar', $completedFileUpload->getContents());

        $storage->destroy();

        // file upload can no longer be loaded after storage was destroyed
        Assert::exception(
            function () use ($storage, $id): void {
                $storage->load($id);
            },
            FileUploadNotFoundException::class,
            "File upload metadata '$idValue' not found.",
        );
        Assert::equal([], $storage->list());
    }

    /**
     * @param array<string, FileUploadMetadata> $journalData
     * @return FileSystemStorage
     */
    private function createStorage(array $journalData = []): FileSystemStorage
    {
        $directory = Environment::getTempDir() . '/' . Random::generate();
        Nette\Utils\FileSystem::createDir($directory);

        $journal = new InMemoryMetadataJournal($journalData);

        return new FileSystemStorage($journal, new NetteFileSystem(), $directory);
    }

}


(new FileSystemStorageTest())->run();
