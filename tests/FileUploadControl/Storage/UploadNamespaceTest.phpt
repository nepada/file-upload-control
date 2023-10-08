<?php
declare(strict_types = 1);

namespace NepadaTests\FileUploadControl\Storage;

use Nepada\FileUploadControl\Storage\UploadNamespace;
use NepadaTests\TestCase;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';


/**
 * @testCase
 */
class UploadNamespaceTest extends TestCase
{

    public function testValid(): void
    {
        $value = 'abcd12345XYZ';
        Assert::true(UploadNamespace::isValid($value));
        $namespace = UploadNamespace::fromString($value);
        Assert::same($value, $namespace->toString());
    }

    /**
     * @dataProvider getInvalidValues
     */
    public function testInvalid(string $value): void
    {
        Assert::false(UploadNamespace::isValid($value));
        Assert::exception(
            function () use ($value): void {
                UploadNamespace::fromString($value);
            },
            \InvalidArgumentException::class,
            'Storage namespace must be a non-empty string of alphanumeric characters.',
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
            ['value' => 'abc-'],
            ['value' => 'abc_'],
            ['value' => 'abc='],
            ['value' => 'abc.'],
            ['value' => 'ščřžýáí'],
        ];
    }

}


(new UploadNamespaceTest())->run();
