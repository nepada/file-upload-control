<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Storage;

use Nepada\FileUploadControl\Utils\RandomProvider;
use Nette;
use Nette\Utils\Validators;

final class UploadNamespace
{

    use Nette\SmartObject;

    private const DEFAULT_LENGTH = 24;

    private string $id;

    private function __construct(string $id)
    {
        if (! self::isValid($id)) {
            throw new \InvalidArgumentException('Storage namespace must be a non-empty string of alphanumeric characters.');
        }
        $this->id = $id;
    }

    public static function isValid(string $id): bool
    {
        return Validators::is($id, 'pattern:[A-Za-z0-9]+');
    }

    public static function generate(RandomProvider $randomProvider): self
    {
        return new self($randomProvider->generateAlphanumeric(self::DEFAULT_LENGTH));
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
