<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Storage\Metadata;

use Nepada\FileUploadControl\Storage\UploadNamespace;
use Nepada\FileUploadControl\Utils\FileSystem;
use Nepada\FileUploadControl\Utils\Finder;
use Nette;

final class FileSystemMetadataJournalProvider implements MetadataJournalProvider
{

    use Nette\SmartObject;

    private const NAMESPACE_DIRECTORY_SUFFIX = '-meta';

    private FileSystem $fileSystem;

    private Finder $finder;

    private string $baseDirectory;

    public function __construct(FileSystem $fileSystem, Finder $finder, string $directory)
    {
        $this->fileSystem = $fileSystem;
        $this->finder = $finder;
        $this->baseDirectory = $directory;
    }

    public function get(UploadNamespace $namespace): MetadataJournal
    {
        $directory = $this->baseDirectory . DIRECTORY_SEPARATOR . $namespace->toString() . self::NAMESPACE_DIRECTORY_SUFFIX;
        $this->fileSystem->createDirectory($directory);
        return new FileSystemMetadataJournal($this->fileSystem, $this->finder, $directory);
    }

}
