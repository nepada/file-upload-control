<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Storage;

use Nepada\FileUploadControl\Storage\Metadata\FileUploadMetadata;
use Nepada\FileUploadControl\Storage\Metadata\FileUploadMetadataAlreadyExistsException;
use Nepada\FileUploadControl\Storage\Metadata\FileUploadMetadataNotFoundException;
use Nepada\FileUploadControl\Storage\Metadata\MetadataJournal;
use Nepada\FileUploadControl\Utils\FileSystem;
use Nette;
use Nette\Http\FileUpload;

final class FileSystemStorage implements Storage
{

    use Nette\SmartObject;

    private MetadataJournal $metadataJournal;

    private FileSystem $fileSystem;

    private string $directory;

    public function __construct(MetadataJournal $metadataJournal, FileSystem $fileSystem, string $directory)
    {
        $this->metadataJournal = $metadataJournal;
        $this->fileSystem = $fileSystem;
        if (! $this->fileSystem->directoryExists($directory)) {
            throw new \InvalidArgumentException("Directory '$directory' does not exist.");
        }
        if (! $this->fileSystem->isWritable($directory)) {
            throw new \InvalidArgumentException("Directory '$directory' is not writable.");
        }
        $this->directory = $directory;
    }

    /**
     * @return FileUploadItem[]
     */
    public function list(): array
    {
        $items = [];
        foreach ($this->metadataJournal->list() as $id) {
            try {
                $fileUploadItem = $this->load($id);
            } catch (FileUploadNotFoundException $exception) {
                continue;
            }
            $items[] = $fileUploadItem;
        }
        return $items;
    }

    /**
     * @throws FileUploadNotFoundException
     */
    public function load(FileUploadId $id): FileUploadItem
    {
        try {
            $metadata = $this->metadataJournal->load($id);
        } catch (FileUploadMetadataNotFoundException $exception) {
            throw new FileUploadNotFoundException($exception->getMessage(), 0, $exception);
        }

        $file = $this->getFilePath($id);
        if (! $this->fileSystem->fileExists($file)) {
            throw FileUploadNotFoundException::withId($id);
        }

        $error = $this->fileSystem->fileSize($file) === $metadata->getSize() ? UPLOAD_ERR_OK : UPLOAD_ERR_PARTIAL;

        $fileUpload = new FileUpload([
            'name' => $metadata->getName(),
            'size' => $metadata->getSize(),
            'tmp_name' => $file,
            'error' => $error,
        ]);
        return new FileUploadItem($id, $fileUpload);
    }

    public function delete(FileUploadId $id): void
    {
        $this->metadataJournal->delete($id);
        $this->fileSystem->delete($this->getFilePath($id));
    }

    /**
     * @throws UnableToSaveFileUploadException
     */
    public function save(FileUploadChunk $fileUploadChunk): FileUploadItem
    {
        if (! $fileUploadChunk->getFileUpload()->isOk()) {
            throw UnableToSaveFileUploadException::withUploadError();
        }

        if ($fileUploadChunk->getContentRange()->containsFirstByte()) {
            $id = $this->saveNewUpload($fileUploadChunk);
        } else {
            $id = $this->resumeExistingUpload($fileUploadChunk);
        }

        try {
            return $this->load($id);
        } catch (FileUploadNotFoundException $exception) {
            throw new UnableToSaveFileUploadException($exception->getMessage(), 0, $exception);
        }
    }

    public function destroy(): void
    {
        $this->metadataJournal->destroy();
        $this->fileSystem->delete($this->directory);
    }

    private function getFilePath(FileUploadId $id): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . $id->toString();
    }

    /**
     * @throws UnableToSaveFileUploadException
     */
    private function saveNewUpload(FileUploadChunk $fileUploadChunk): FileUploadId
    {
        $metadata = FileUploadMetadata::fromFileUploadChunk($fileUploadChunk);
        $id = $metadata->createFileUploadId();
        try {
            $this->metadataJournal->save($id, $metadata);
        } catch (FileUploadMetadataAlreadyExistsException $exception) {
            throw UnableToSaveFileUploadException::withConflict($id);
        }

        $file = $this->getFilePath($id);
        $contents = $fileUploadChunk->getFileUpload()->getContents();
        assert(is_string($contents));
        $this->fileSystem->write($file, $contents);

        return $id;
    }

    /**
     * @throws UnableToSaveFileUploadException
     */
    private function resumeExistingUpload(FileUploadChunk $fileUploadChunk): FileUploadId
    {
        $metadata = FileUploadMetadata::fromFileUploadChunk($fileUploadChunk);
        $id = $metadata->createFileUploadId();
        try {
            $storedMetadata = $this->metadataJournal->load($id);
            if (! $metadata->equals($storedMetadata)) {
                throw UnableToSaveFileUploadException::withConflict($id);
            }
        } catch (FileUploadMetadataNotFoundException $exception) {
            throw UnableToSaveFileUploadException::withFailedChunk($id, 'failed to load metadata');
        }

        $file = $this->getFilePath($id);
        if (! $this->fileSystem->fileExists($file)) {
            throw UnableToSaveFileUploadException::withFailedChunk($id, 'missing previously uploaded file part');
        }
        if ($this->fileSystem->fileSize($file) !== $fileUploadChunk->getContentRange()->getStart()) {
            throw UnableToSaveFileUploadException::withFailedChunk($id, 'previously uploaded file part size does not match given content-range value');
        }

        $contents = $fileUploadChunk->getFileUpload()->getContents();
        assert(is_string($contents));
        $this->fileSystem->append($file, $contents);

        return $id;
    }

}
