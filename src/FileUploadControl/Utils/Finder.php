<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Utils;

interface Finder
{

    /**
     * @return iterable<\SplFileInfo>
     */
    public function findFilesInDirectory(string $path, string $mask = '*'): iterable;

    /**
     * @return iterable<\SplFileInfo>
     */
    public function findDirectoriesInDirectory(string $path, string $mask = '*'): iterable;

}
