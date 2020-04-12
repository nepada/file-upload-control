<?php
declare(strict_types = 1);

namespace NepadaTests\FileUploadControl\Thumbnail;

use Nepada\FileUploadControl\Thumbnail\ThumbnailResponse;
use NepadaTests\TestCase;
use Nette;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';


/**
 * @testCase
 */
class ThumbnailResponseTest extends TestCase
{

    public function testResponse(): void
    {
        $contents = '1234567890';
        $contentType = 'image/png';
        $name = 'žluťoučký kůň.png';
        $response = new ThumbnailResponse($contents, $name, $contentType);

        $httpRequest = \Mockery::mock(Nette\Http\IRequest::class);
        $httResponse = \Mockery::mock(Nette\Http\IResponse::class);
        $httResponse->shouldReceive('setContentType')->withArgs([$contentType])->once();
        $httResponse->shouldReceive('setHeader')
            ->withArgs(function (string $name, string $value): bool {
                Assert::same('Content-Disposition', $name);
                Assert::same('inline; filename="žluťoučký kůň.png"; filename*=utf-8\'\'%C5%BElu%C5%A5ou%C4%8Dk%C3%BD%20k%C5%AF%C5%88.png', $value);
                return true;
            })->once();
        $httResponse->shouldReceive('setHeader')
            ->withArgs(function (string $name, string $value): bool {
                Assert::same('Content-Length', $name);
                Assert::same('10', $value);
                return true;
            })->once();

        Assert::same(
            $contents,
            Nette\Utils\Helpers::capture(
                function () use ($response, $httpRequest, $httResponse): void {
                    $response->send($httpRequest, $httResponse);
                },
            ),
        );
    }

}


(new ThumbnailResponseTest())->run();
