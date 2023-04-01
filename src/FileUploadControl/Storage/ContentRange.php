<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Storage;

use Nette;
use Nette\Utils\Strings;

final class ContentRange
{

    use Nette\SmartObject;

    private int $start;

    private int $end;

    private int $size;

    private function __construct(int $start, int $end, int $size)
    {
        if ($start < 0) {
            throw new \InvalidArgumentException("Start ($start) cannot be negative.");
        }
        if ($end < $start) {
            throw new \InvalidArgumentException("Start ($start) cannot be larger than end ($end).");
        }
        if ($size <= $end) {
            throw new \InvalidArgumentException("End ($end) cannot be larger or equal to size ($size).");
        }
        $this->start = $start;
        $this->end = $end;
        $this->size = $size;
    }

    public static function ofSize(int $size): self
    {
        return new self(0, $size - 1, $size);
    }

    public static function fromHttpHeaderValue(string $header): self
    {
        $match = Strings::match($header, '~^\s*bytes\s+(\d+)-(\d+)/(\d+)\s*$~i');
        if ($match === null) {
            throw new \InvalidArgumentException("Malformed content-range header '$header'.");
        }
        return new self((int) $match[1], (int) $match[2], (int) $match[3]);
    }

    public function getStart(): int
    {
        return $this->start;
    }

    public function getEnd(): int
    {
        return $this->end;
    }

    public function getRangeSize(): int
    {
        return $this->end - $this->start + 1;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function containsFirstByte(): bool
    {
        return $this->start === 0;
    }

    public function containsLastByte(): bool
    {
        return $this->end === ($this->size - 1);
    }

}
