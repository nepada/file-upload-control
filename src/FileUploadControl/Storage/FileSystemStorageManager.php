<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Storage;

use Nepada\FileUploadControl\Storage\Metadata\MetadataJournalProvider;
use Nepada\FileUploadControl\Utils\DateTimeProvider;
use Nepada\FileUploadControl\Utils\FileSystem;
use Nepada\FileUploadControl\Utils\Finder;
use Nepada\FileUploadControl\Utils\RandomProvider;
use Nette;

final class FileSystemStorageManager implements StorageManager
{

    use Nette\SmartObject;

    public const DEFAULT_TTL = Nette\Utils\DateTime::DAY;

    private const NAMESPACE_DIRECTORY_SUFFIX = '-files';

    private MetadataJournalProvider $metadataJournalProvider;

    private FileSystem $fileSystem;

    private Finder $finder;

    private DateTimeProvider $dateTimeProvider;

    private RandomProvider $randomProvider;

    private string $directory;

    private int $namespaceTtl;

    private bool $garbageCollected = false;

    public function __construct(
        MetadataJournalProvider $metadataJournalProvider,
        FileSystem $fileSystem,
        Finder $finder,
        DateTimeProvider $dateTimeProvider,
        RandomProvider $randomProvider,
        string $directory,
        int $namespaceTtl = self::DEFAULT_TTL,
    )
    {
        $this->metadataJournalProvider = $metadataJournalProvider;
        $this->fileSystem = $fileSystem;
        $this->finder = $finder;
        $this->dateTimeProvider = $dateTimeProvider;
        $this->randomProvider = $randomProvider;
        $this->directory = $directory;
        $this->namespaceTtl = $namespaceTtl;
    }

    public function createNewNamespace(): UploadNamespace
    {
        $this->collectGarbage();
        $namespace = UploadNamespace::generate($this->randomProvider);
        $directory = $this->getUploadNamespaceDirectory($namespace);
        $this->fileSystem->createDirectory($directory);
        return $namespace;
    }

    /**
     * @throws StorageDoesNotExistException
     */
    public function getStorage(UploadNamespace $namespace): Storage
    {
        $this->collectGarbage();
        $directory = $this->getUploadNamespaceDirectory($namespace);
        if (! $this->fileSystem->directoryExists($directory)) {
            throw StorageDoesNotExistException::withNamespace($namespace);
        }

        $metadataJournal = $this->metadataJournalProvider->get($namespace);
        return new FileSystemStorage($metadataJournal, $this->fileSystem, $directory);
    }

    private function collectGarbage(): void
    {
        if ($this->garbageCollected) {
            return;
        }

        $this->garbageCollected = true;

        if (! $this->fileSystem->directoryExists($this->directory)) {
            return;
        }

        $expiredNamespaces = [];
        $currentTimestamp = $this->dateTimeProvider->getNow()->getTimestamp();
        /** @var \SplFileInfo $directory */
        foreach ($this->finder->findDirectoriesInDirectory($this->directory, '*' . self::NAMESPACE_DIRECTORY_SUFFIX) as $directory) {
            $age = $currentTimestamp - $directory->getMTime();
            if ($age < $this->namespaceTtl) {
                continue;
            }
            $expiredNamespaces[] = UploadNamespace::fromString($directory->getBasename(self::NAMESPACE_DIRECTORY_SUFFIX));
        }

        foreach ($expiredNamespaces as $expiredNamespace) {
            try {
                $storage = $this->getStorage($expiredNamespace);
                $storage->destroy();
            } catch (StorageDoesNotExistException $exception) {
                // noop
            }
        }
    }

    private function getUploadNamespaceDirectory(UploadNamespace $namespace): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . $namespace->toString() . self::NAMESPACE_DIRECTORY_SUFFIX;
    }

}
