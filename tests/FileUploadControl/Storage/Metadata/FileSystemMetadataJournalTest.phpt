<?php
declare(strict_types = 1);

namespace NepadaTests\FileUploadControl\Storage\Metadata;

use Nepada\FileUploadControl\Storage\FileUploadId;
use Nepada\FileUploadControl\Storage\Metadata\FileSystemMetadataJournal;
use Nepada\FileUploadControl\Storage\Metadata\FileUploadMetadata;
use Nepada\FileUploadControl\Storage\Metadata\FileUploadMetadataAlreadyExistsException;
use Nepada\FileUploadControl\Storage\Metadata\FileUploadMetadataNotFoundException;
use Nepada\FileUploadControl\Utils\NetteFileSystem;
use Nepada\FileUploadControl\Utils\NetteFinder;
use NepadaTests\Environment;
use NepadaTests\TestCase;
use Nette;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';


/**
 * @testCase
 */
class FileSystemMetadataJournalTest extends TestCase
{

    public function testJournal(): void
    {
        $journal = $this->createJournal();

        $id1 = FileUploadId::fromString('1');
        $id2 = FileUploadId::fromString('2');
        $id3 = FileUploadId::fromString('3');
        $id4 = FileUploadId::fromString('4');
        $metadata1 = FileUploadMetadata::fromArray(['size' => 1, 'name' => 'foo']);
        $metadata23 = FileUploadMetadata::fromArray(['size' => 2, 'name' => 'foo']);

        Assert::same([], $journal->list(), 'journal is initially empty');

        $journal->save($id1, $metadata1);

        // write with conflicting ID fails
        Assert::exception(
            function () use ($journal, $id1, $metadata1): void {
                $journal->save($id1, $metadata1);
            },
            FileUploadMetadataAlreadyExistsException::class,
            "File upload metadata '1' already exists.",
        );

        sleep(1);
        $journal->save($id3, $metadata23);
        // write with the same metadata under different id succeeds
        sleep(1);
        $journal->save($id2, $metadata23);

        Assert::equal([$id1, $id3, $id2], $journal->list(), 'listing stored items');

        // delete non-existent id succeeds
        $journal->delete($id4);

        // reading non-existent id fails
        Assert::exception(
            function () use ($journal, $id4): void {
                $journal->load($id4);
            },
            FileUploadMetadataNotFoundException::class,
            'File upload metadata \'4\' not found.',
        );

        $journal->delete($id2);
        Assert::equal([$id1, $id3], $journal->list(), 'listing stored items');
        Assert::equal($metadata1, $journal->load($id1));
        Assert::equal($metadata23, $journal->load($id3));

        $journal->destroy();

        // accessing journal after it was destroyed ends with error
        Assert::exception(
            function () use ($journal): void {
                $journal->list();
            },
            \Throwable::class,
        );
    }

    private function createJournal(): FileSystemMetadataJournal
    {
        $directory = Environment::getTempDir() . '/' . uniqid();
        Nette\Utils\FileSystem::createDir($directory);

        return new FileSystemMetadataJournal(new NetteFileSystem(), new NetteFinder(), $directory);
    }

}


(new FileSystemMetadataJournalTest())->run();
