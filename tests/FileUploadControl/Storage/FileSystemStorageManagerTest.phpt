<?php
declare(strict_types = 1);

namespace NepadaTests\FileUploadControl\Storage;

use Nepada\FileUploadControl\Storage\FileSystemStorageManager;
use Nepada\FileUploadControl\Storage\Metadata\FileSystemMetadataJournalProvider;
use Nepada\FileUploadControl\Storage\StorageDoesNotExistException;
use Nepada\FileUploadControl\Storage\UploadNamespace;
use Nepada\FileUploadControl\Utils\DefaultDateTimeProvider;
use Nepada\FileUploadControl\Utils\NetteFileSystem;
use Nepada\FileUploadControl\Utils\NetteFinder;
use Nepada\FileUploadControl\Utils\NetteRandomProvider;
use NepadaTests\Environment;
use NepadaTests\TestCase;
use Nette\Utils\Random;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';


/**
 * @testCase
 */
class FileSystemStorageManagerTest extends TestCase
{

    public function testStorageNotFound(): void
    {
        Assert::exception(
            function (): void {
                $this->createStorageManager()->getStorage(UploadNamespace::fromString('doesNotExist'));
            },
            StorageDoesNotExistException::class,
            "Storage for namespace 'doesNotExist' does not exist.",
        );
    }

    public function testGarbageCollection(): void
    {
        $directory = Environment::getTempDir() . '/' . Random::generate();
        $ttl = 2;

        $oldNamespace = $this->createStorageManager($directory, $ttl)->createNewNamespace();

        $storageManager = $this->createStorageManager($directory, $ttl);
        Assert::noError(function () use ($storageManager, $oldNamespace): void {
            $storageManager->getStorage($oldNamespace);
        });

        sleep($ttl + 1);

        // garbage collection was already triggered
        Assert::noError(function () use ($storageManager, $oldNamespace): void {
            $storageManager->getStorage($oldNamespace);
        });

        // new storage manager triggers garbage collection
        Assert::exception(
            function () use ($directory, $ttl, $oldNamespace): void {
                $this->createStorageManager($directory, $ttl)->getStorage($oldNamespace);
            },
            StorageDoesNotExistException::class,
            "Storage for namespace '%S%' does not exist.",
        );
    }

    private function createStorageManager(?string $directory = null, int $ttl = FileSystemStorageManager::DEFAULT_TTL): FileSystemStorageManager
    {
        $directory ??= Environment::getTempDir() . '/' . Random::generate();
        $fileSystem = new NetteFileSystem();
        $finder = new NetteFinder();
        $dateTimeProvider = new DefaultDateTimeProvider();
        $randomProvider = new NetteRandomProvider();
        return new FileSystemStorageManager(
            new FileSystemMetadataJournalProvider($fileSystem, $finder, $directory),
            $fileSystem,
            $finder,
            $dateTimeProvider,
            $randomProvider,
            $directory,
            $ttl,
        );
    }

}


(new FileSystemStorageManagerTest())->run();
