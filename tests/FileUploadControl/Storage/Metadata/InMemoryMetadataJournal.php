<?php
declare(strict_types = 1);

namespace NepadaTests\FileUploadControl\Storage\Metadata;

use Nepada\FileUploadControl\Storage\FileUploadId;
use Nepada\FileUploadControl\Storage\Metadata\FileUploadMetadata;
use Nepada\FileUploadControl\Storage\Metadata\FileUploadMetadataAlreadyExistsException;
use Nepada\FileUploadControl\Storage\Metadata\FileUploadMetadataNotFoundException;
use Nepada\FileUploadControl\Storage\Metadata\MetadataJournal;
use Nette;

final class InMemoryMetadataJournal implements MetadataJournal
{

    use Nette\SmartObject;

    /**
     * @var array<string, FileUploadMetadata>
     */
    private array $data;

    /**
     * @param array<string, FileUploadMetadata> $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * @return list<FileUploadId>
     */
    public function list(): array
    {
        return array_map(
            fn (string $id): FileUploadId => FileUploadId::fromString($id),
            array_keys($this->data),
        );
    }

    public function load(FileUploadId $id): FileUploadMetadata
    {
        $idValue = $id->toString();
        if (! isset($this->data[$idValue])) {
            throw FileUploadMetadataNotFoundException::withId($id);
        }
        return $this->data[$idValue];
    }

    public function save(FileUploadId $id, FileUploadMetadata $metadata): void
    {
        $idValue = $id->toString();
        if (isset($this->data[$idValue])) {
            throw FileUploadMetadataAlreadyExistsException::withId($id);
        }
        $this->data[$idValue] = $metadata;
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
