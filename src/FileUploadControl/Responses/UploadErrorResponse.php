<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Responses;

use Nette;

final class UploadErrorResponse implements Response
{

    use Nette\SmartObject;

    public function __construct(
        private string $name,
        private int $size,
        private string $error,
    )
    {
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
