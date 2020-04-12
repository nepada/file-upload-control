<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Responses;

use Nette;

final class ListResponse implements Response
{

    use Nette\SmartObject;

    /** @var Response[] */
    private array $responses;

    public function __construct(Response ...$responses)
    {
        $this->responses = $responses;
    }

    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        return [
            'files' => $this->responses,
        ];
    }

}
