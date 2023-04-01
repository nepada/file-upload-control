<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Storage\Metadata;

use Nepada\FileUploadControl\Storage\FileUploadChunk;
use Nepada\FileUploadControl\Storage\FileUploadId;
use Nette;
use Nette\Utils\Json;

final class FileUploadMetadata
{

    use Nette\SmartObject;

    private string $name;

    private int $size;

    private function __construct(string $name, int $size)
    {
        if ($size < 0) {
            throw new \InvalidArgumentException('File upload size cannot be negative.');
        }
        $this->name = $name;
        $this->size = $size;
    }

    public static function fromFileUploadChunk(FileUploadChunk $fileUploadChunk): self
    {
        return new self($fileUploadChunk->getFileUpload()->getUntrustedName(), $fileUploadChunk->getContentRange()->getSize());
    }

    /**
     * @param mixed[] $data
     * @return FileUploadMetadata
     */
    public static function fromArray(array $data): self
    {
        return new self($data['name'], $data['size']);
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'size' => $this->size,
        ];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function equals(self $other): bool
    {
        return $this->name === $other->name && $this->size === $other->size;
    }

    public function createFileUploadId(): FileUploadId
    {
        $data = $this->toArray();
        ksort($data);
        $serialized = Json::encode($data);
        $hash = sha1($serialized, true);
        $id = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($hash));
        return FileUploadId::fromString($id);
    }

}
