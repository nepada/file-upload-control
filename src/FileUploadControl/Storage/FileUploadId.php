<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Storage;

use Nette;
use Nette\Utils\Validators;

final class FileUploadId
{

    use Nette\SmartObject;

    private string $id;

    private function __construct(string $id)
    {
        if (! self::isValid($id)) {
            throw new \InvalidArgumentException('File upload id must be a non-empty string of _, -, and alphanumeric characters.');
        }
        $this->id = $id;
    }

    public static function isValid(string $id): bool
    {
        return Validators::is($id, 'pattern:[A-Za-z0-9_-]+');
    }

    public static function fromString(string $id): self
    {
        return new self($id);
    }

    public function toString(): string
    {
        return $this->id;
    }

}
