<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Storage;

use Nette;
use Nette\Utils\Strings;

final class ContentRange
{

    use Nette\SmartObject;

    /**
     * @var int<0, max>
     */
    private int $start;

    /**
     * @var int<0, max>
     */
    private int $end;

    /**
     * @var int<0, max>
     */
    private int $size;

    private function __construct(int $start, int $end, int $size)
    {
        if ($start < 0) {
            throw new \InvalidArgumentException("Start ($start) cannot be negative.");
        }
        if ($end < $start) {
            throw new \InvalidArgumentException("Start ($start) cannot be larger than end ($end).");
        }
        if ($size < $end) {
            throw new \InvalidArgumentException("End ($end) cannot be larger than size ($size).");
        }
        if ($size > 0 && $size === $end) {
            throw new \InvalidArgumentException("End ($end) cannot be equal to size ($size).");
        }
        $this->start = $start;
        $this->end = $end;
        $this->size = $size;
    }

    /**
     * @param int<0, max> $size
     */
    public static function ofSize(int $size): self
    {
        return new self(0, $size === 0 ? 0 : $size - 1, $size);
    }

    public static function fromHttpHeaderValue(string $header): self
    {
        $match = Strings::match($header, '~^\s*bytes\s+(\d+)-(\d+)/(\d+)\s*$~i');
        if ($match === null) {
            throw new \InvalidArgumentException("Malformed content-range header '$header'.");
        }
        return new self((int) $match[1], (int) $match[2], (int) $match[3]);
    }

    /**
     * @return int<0, max>
     */
    public function getStart(): int
    {
        return $this->start;
    }

    /**
     * @return int<0, max>
     */
    public function getEnd(): int
    {
        return $this->end;
    }

    /**
     * @return int<0, max>
     */
    public function getRangeSize(): int
    {
        return $this->size === 0 ? 0 : $this->end - $this->start + 1;
    }

    /**
     * @return int<0, max>
     */
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
        return $this->end === max(0, ($this->size - 1));
    }

}
