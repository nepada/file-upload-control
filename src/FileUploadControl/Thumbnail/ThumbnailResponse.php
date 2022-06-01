<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Thumbnail;

use Nette;
use Nette\Application\Response;

final class ThumbnailResponse implements Response
{

    use Nette\SmartObject;

    private string $contents;

    private string $name;

    private string $contentType;

    public function __construct(string $contents, string $name, string $contentType)
    {
        $this->contents = $contents;
        $this->name = $name;
        $this->contentType = $contentType;
    }

    public function getContents(): string
    {
        return $this->contents;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function send(Nette\Http\IRequest $httpRequest, Nette\Http\IResponse $httpResponse): void
    {
        $httpResponse->setContentType($this->contentType);
        $httpResponse->setHeader('Content-Disposition', sprintf('inline; filename="%s"; filename*=utf-8\'\'%s', $this->name, rawurlencode($this->name)));
        $httpResponse->setHeader('Content-Length', (string) strlen($this->contents));
        echo $this->contents;
    }

}
