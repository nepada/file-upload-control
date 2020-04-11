<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Utils;

use Nette;

final class NetteFileSystem implements FileSystem
{

    use Nette\SmartObject;

    public function fileExists(string $path): bool
    {
        return is_file($path);
    }

    public function fileSize(string $path): int
    {
        $size = @filesize($path);
        if ($size === false) {
            throw new \Exception("Unable to get file size '$path'. " . Nette\Utils\Helpers::getLastError());
        }
        return $size;
    }

    public function read(string $path): string
    {
        return Nette\Utils\FileSystem::read($path);
    }

    public function write(string $path, string $data): void
    {
        Nette\Utils\FileSystem::write($path, $data);
    }

    public function append(string $path, string $data): void
    {
        $this->createDirectory(dirname($path));

        if (@file_put_contents($path, $data, FILE_APPEND) === false) { // @ is escalated to exception
            throw new \Exception("Unable to write file '$path'. " . Nette\Utils\Helpers::getLastError());
        }
    }

    public function delete(string $path): void
    {
        Nette\Utils\FileSystem::delete($path);
    }

    public function directoryExists(string $path): bool
    {
        return is_dir($path);
    }

    public function createDirectory(string $path): void
    {
        Nette\Utils\FileSystem::createDir($path);
    }

    public function isWritable(string $path): bool
    {
        return is_writable($path);
    }

}
