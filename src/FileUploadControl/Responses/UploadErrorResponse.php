<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Responses;

use Nette;

final class UploadErrorResponse implements Response
{

    use Nette\SmartObject;

    private string $name;

    private int $size;

    private string $error;

    public function __construct(string $name, int $size, string $error)
    {
        $this->name = $name;
        $this->size = $size;
        $this->error = $error;
    }

    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'size' => $this->size,
            'error' => $this->error,
        ];
    }

}
