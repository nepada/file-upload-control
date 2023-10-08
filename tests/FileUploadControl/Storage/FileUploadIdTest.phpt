<?php
declare(strict_types = 1);

namespace NepadaTests\FileUploadControl\Storage;

use Nepada\FileUploadControl\Storage\FileUploadId;
use NepadaTests\TestCase;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';


/**
 * @testCase
 */
class FileUploadIdTest extends TestCase
{

    public function testValid(): void
    {
        $value = 'abcd12345XYZ-_';
        Assert::true(FileUploadId::isValid($value));
        $namespace = FileUploadId::fromString($value);
        Assert::same($value, $namespace->toString());
    }

    /**
     * @dataProvider getInvalidValues
     */
    public function testInvalid(string $value): void
    {
        Assert::false(FileUploadId::isValid($value));
        Assert::exception(
            function () use ($value): void {
                FileUploadId::fromString($value);
            },
            \InvalidArgumentException::class,
            'File upload id must be a non-empty string of _, -, and alphanumeric characters.',
        );
    }

    /**
     * @return mixed[]
     */
    protected function getInvalidValues(): array
    {
        return [
            ['value' => ''],
            ['value' => 'abc/'],
            ['value' => 'abc?'],
            ['value' => 'abc!'],
            ['value' => 'abc='],
            ['value' => 'abc.'],
            ['value' => 'ščřžýáí'],
        ];
    }

}


(new FileUploadIdTest())->run();
