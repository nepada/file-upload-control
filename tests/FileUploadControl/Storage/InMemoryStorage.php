<?php
declare(strict_types = 1);

namespace NepadaTests\FileUploadControl\Storage;

use Nepada\FileUploadControl\Storage\FileUploadChunk;
use Nepada\FileUploadControl\Storage\FileUploadId;
use Nepada\FileUploadControl\Storage\FileUploadItem;
use Nepada\FileUploadControl\Storage\FileUploadNotFoundException;
use Nepada\FileUploadControl\Storage\Storage;
use NepadaTests\FileUploadControl\FileUploadFactory;
use Nette;
use Nette\Utils\Strings;

final class InMemoryStorage implements Storage
{

    use Nette\SmartObject;

    /**
     * @var array<string, FileUploadItem>
     */
    private array $data;

    /**
     * @param array<string, FileUploadItem> $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public static function createWithFiles(string ...$files): self
    {
        $storage = new self();
        foreach ($files as $file) {
            $storage->save(FileUploadChunk::completeUpload(FileUploadFactory::createFromFile($file)));
        }
        return $storage;
    }

    /**
     * @return list<FileUploadItem>
     */
    public function list(): array
    {
        return array_values($this->data);
    }

    public function load(FileUploadId $id): FileUploadItem
    {
        $idValue = $id->toString();
        if (! isset($this->data[$idValue])) {
            throw FileUploadNotFoundException::withId($id);
        }
        return $this->data[$idValue];
    }

    public function save(FileUploadChunk $fileUploadChunk): FileUploadItem
    {
        $fileUpload = $fileUploadChunk->fileUpload;
        $contentRange = $fileUploadChunk->contentRange;
        // assume unique enough names
        $idValue = Strings::webalize($fileUploadChunk->fileUpload->getUntrustedName());
        $fileUpload = new Nette\Http\FileUpload([
            'name' => $fileUpload->getUntrustedName(),
            'tmp_name' => $fileUpload->getTemporaryFile(),
            'size' => $contentRange->getSize(),
            'error' => $contentRange->containsLastByte() ? UPLOAD_ERR_OK : UPLOAD_ERR_PARTIAL,
        ]);
        return $this->data[$idValue] = new FileUploadItem(FileUploadId::fromString($idValue), $fileUpload);
    }

    public function delete(FileUploadId $id): void
    {
        unset($this->data[$id->toString()]);
    }

    public function destroy(): void
    {
        $this->data = [];
    }

}
