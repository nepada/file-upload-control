<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Utils;

interface Finder
{

    /**
     * @param string $path
     * @param string $mask
     * @return iterable<\SplFileInfo>
     */
    public function findFilesInDirectory(string $path, string $mask = '*'): iterable;

    /**
     * @param string $path
     * @param string $mask
     * @return iterable<\SplFileInfo>
     */
    public function findDirectoriesInDirectory(string $path, string $mask = '*'): iterable;

}
