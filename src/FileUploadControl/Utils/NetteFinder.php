<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Utils;

use Nette;

final class NetteFinder implements Finder
{

    use Nette\SmartObject;

    /**
     * @return iterable<\SplFileInfo>
     */
    public function findFilesInDirectory(string $path, string $mask = '*'): iterable
    {
        return Nette\Utils\Finder::findFiles($mask)->in($path);
    }

    /**
     * @return iterable<\SplFileInfo>
     */
    public function findDirectoriesInDirectory(string $path, string $mask = '*'): iterable
    {
        return Nette\Utils\Finder::findDirectories($mask)->in($path);
    }

}
