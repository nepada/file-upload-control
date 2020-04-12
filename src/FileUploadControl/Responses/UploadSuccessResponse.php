<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Responses;

use Nette;

final class UploadSuccessResponse implements Response
{

    use Nette\SmartObject;

    private string $name;

    private int $size;

    private ?string $contentType;

    private string $url;

    private string $deleteUrl;

    private ?string $thumbnailUrl;

    public function __construct(string $name, int $size, ?string $contentType, string $url, string $deleteUrl, ?string $thumbnailUrl)
    {
        $this->name = $name;
        $this->size = $size;
        $this->contentType = $contentType;
        $this->url = $url;
        $this->deleteUrl = $deleteUrl;
        $this->thumbnailUrl = $thumbnailUrl;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getDeleteUrl(): string
    {
        return $this->deleteUrl;
    }

    public function getThumbnailUrl(): ?string
    {
        return $this->thumbnailUrl;
    }

    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        $data = [
            'name' => $this->name,
            'size' => $this->size,
            'url' => $this->url,
            'type' => $this->contentType,
            'deleteType' => Nette\Http\IRequest::GET,
            'deleteUrl' => $this->deleteUrl,
        ];
        if ($this->thumbnailUrl !== null) {
            $data['thumbnailUrl'] = $this->thumbnailUrl;
        }
        return $data;
    }

}
