<?php
declare(strict_types = 1);

namespace NepadaTests\FileUploadControl\Storage;

use Nepada\FileUploadControl\Storage\ContentRange;
use NepadaTests\TestCase;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';


/**
 * @testCase
 */
class ContentRangeTest extends TestCase
{

    public function testOfZeroSize(): void
    {
        $contentRange = ContentRange::ofSize(0);
        Assert::same(0, $contentRange->getSize());
        Assert::same(0, $contentRange->getStart());
        Assert::same(0, $contentRange->getEnd());
        Assert::same(0, $contentRange->getRangeSize());
        Assert::true($contentRange->containsFirstByte());
        Assert::true($contentRange->containsLastByte());
    }

    public function testOfOneByteSize(): void
    {
        $contentRange = ContentRange::ofSize(1);
        Assert::same(1, $contentRange->getSize());
        Assert::same(0, $contentRange->getStart());
        Assert::same(0, $contentRange->getEnd());
        Assert::same(1, $contentRange->getRangeSize());
        Assert::true($contentRange->containsFirstByte());
        Assert::true($contentRange->containsLastByte());
    }

    public function testOfSize(): void
    {
        $contentRange = ContentRange::ofSize(42);
        Assert::same(42, $contentRange->getSize());
        Assert::same(0, $contentRange->getStart());
        Assert::same(41, $contentRange->getEnd());
        Assert::same(42, $contentRange->getRangeSize());
        Assert::true($contentRange->containsFirstByte());
        Assert::true($contentRange->containsLastByte());
    }

    public function testFromHttpHeaderValue(): void
    {
        $contentRange = ContentRange::fromHttpHeaderValue('bytes 10-19/40');
        Assert::same(40, $contentRange->getSize());
        Assert::same(10, $contentRange->getStart());
        Assert::same(19, $contentRange->getEnd());
        Assert::same(10, $contentRange->getRangeSize());
        Assert::false($contentRange->containsFirstByte());
        Assert::false($contentRange->containsLastByte());
    }

    /**
     * @dataProvider getInvalidHttpHeaders
     */
    public function testFromHttpHeaderValueFailure(string $headerValue, string $expectedError): void
    {
        Assert::exception(
            function () use ($headerValue): void {
                ContentRange::fromHttpHeaderValue($headerValue);
            },
            \InvalidArgumentException::class,
            $expectedError,
        );
    }

    /**
     * @return list<mixed[]>
     */
    protected function getInvalidHttpHeaders(): array
    {
        return [
            [
                'headerValue' => 'bflmpsvz',
                'expectedError' => "Malformed content-range header 'bflmpsvz'.",
            ],
            [
                'headerValue' => 'bytes 0-10/5',
                'expectedError' => 'End (10) cannot be larger than size (5).',
            ],
            [
                'headerValue' => 'bytes 0-10/10',
                'expectedError' => 'End (10) cannot be equal to size (10).',
            ],
            [
                'headerValue' => 'bytes 10-5/10',
                'expectedError' => 'Start (10) cannot be larger than end (5).',
            ],
        ];
    }

}


(new ContentRangeTest())->run();
