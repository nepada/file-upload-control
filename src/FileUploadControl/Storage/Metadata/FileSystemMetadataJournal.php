<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Storage\Metadata;

use Nepada\FileUploadControl\Storage\FileUploadId;
use Nepada\FileUploadControl\Utils\FileSystem;
use Nepada\FileUploadControl\Utils\Finder;
use Nette;
use Nette\Utils\Json;

final class FileSystemMetadataJournal implements MetadataJournal
{

    use Nette\SmartObject;

    private const METADATA_FILE_SUFFIX = '.json';

    private FileSystem $fileSystem;

    private string $directory;

    private Finder $finder;

    public function __construct(FileSystem $fileSystem, Finder $finder, string $directory)
    {
        $this->fileSystem = $fileSystem;
        $this->finder = $finder;
        if (! $this->fileSystem->directoryExists($directory)) {
            throw new \InvalidArgumentException("Directory '$directory' does not exist.");
        }
        if (! $this->fileSystem->isWritable($directory)) {
            throw new \InvalidArgumentException("Directory '$directory' is not writable.");
        }
        $this->directory = $directory;
    }

    /**
     * @return FileUploadId[]
     */
    public function list(): array
    {
        /** @var \SplFileInfo[] $metadataFiles */
        $metadataFiles = [];
        foreach ($this->finder->findFilesInDirectory($this->directory, '*' . self::METADATA_FILE_SUFFIX) as $file) {
            $metadataFiles[] = $file;
        }
        usort(
            $metadataFiles,
            fn (\SplFileInfo $a, \SplFileInfo $b): int => $a->getMTime() <=> $b->getMTime(),
        );
        return array_map(
            fn (\SplFileInfo $file): FileUploadId => FileUploadId::fromString($file->getBasename(self::METADATA_FILE_SUFFIX)),
            $metadataFiles,
        );
    }

    /**
     * @throws FileUploadMetadataNotFoundException
     */
    public function load(FileUploadId $id): FileUploadMetadata
    {
        $file = $this->getFilePath($id);
        if (! $this->fileSystem->fileExists($file)) {
            throw FileUploadMetadataNotFoundException::withId($id);
        }
        $data = Json::decode($this->fileSystem->read($file), Json::FORCE_ARRAY);
        return FileUploadMetadata::fromArray($data);
    }

    /**
     * @throws FileUploadMetadataAlreadyExistsException
     */
    public function save(FileUploadId $id, FileUploadMetadata $metadata): void
    {
        $file = $this->getFilePath($id);
        if ($this->fileSystem->fileExists($file)) {
            throw FileUploadMetadataAlreadyExistsException::withId($id);
        }
        $data = $metadata->toArray();
        $this->fileSystem->write($file, Json::encode($data));
    }

    public function delete(FileUploadId $id): void
    {
        $this->fileSystem->delete($this->getFilePath($id));
    }

    public function destroy(): void
    {
        $this->fileSystem->delete($this->directory);
    }

    private function getFilePath(FileUploadId $id): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . $id->toString() . self::METADATA_FILE_SUFFIX;
    }

}
