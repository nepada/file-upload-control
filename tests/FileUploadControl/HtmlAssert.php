<?php
declare(strict_types = 1);

namespace NepadaTests\FileUploadControl;

use Nette;
use Nette\Utils\Strings;
use Tester\Assert;
use function htmlspecialchars;
use function str_contains;
use function str_replace;

final class HtmlAssert
{

    use Nette\StaticClass;

    public static function matchFile(string $file, string $actual, ?string $description = null): void
    {
        $expected = Nette\Utils\FileSystem::read($file);
        Assert::match(self::normalize($expected), self::normalize($actual), $description);
    }

    private static function normalize(string $content): string
    {
        $content = self::normalizeWhiteSpace($content);
        $content = self::normalizeHtmlAttributes($content);
        return $content;
    }

    private static function normalizeWhiteSpace(string $content): string
    {
        $content = Strings::unixNewLines($content);
        $content = Strings::replace($content, '~^[\t ]+|[\t ]+$~m', ''); // remove leading and trailing whitespace
        $content = Strings::replace($content, "~\n+~", "\n"); // remove empty lines
        return Strings::trim($content);
    }

    private static function normalizeHtmlAttributes(string $content): string
    {
        $content = Strings::replace(
            $content,
            '~(data-(?:template-[a-z]+|files)=)\'([^\']+)\'~m',
            function (array $matches): string {
                $value = htmlspecialchars($matches[2], ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8');
                $value = str_replace('{', '&#123;', $value);
                $quote = str_contains($value, '"') ? "'" : '"';
                return "{$matches[1]}{$quote}$value{$quote}";
            },
        );
        $content = Strings::replace(
            $content,
            '~(src=)\'([^\']+)\'~m',
            function (array $matches): string {
                $value = $matches[2];
                $quote = str_contains($value, '"') ? "'" : '"';
                return "{$matches[1]}{$quote}$value{$quote}";
            },
        );
        $content = str_replace(
            ['&amp;amp;', '&amp;apos;'],
            ['&amp;', '&apos;'],
            $content,
        );
        return $content;
    }

}
