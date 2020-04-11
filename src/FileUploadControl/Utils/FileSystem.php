<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Utils;

interface FileSystem
{

    public function fileExists(string $path): bool;

    public function fileSize(string $path): int;

    public function read(string $path): string;

    public function write(string $path, string $data): void;

    public function append(string $path, string $data): void;

    public function delete(string $path): void;

    public function directoryExists(string $path): bool;

    public function createDirectory(string $path): void;

    public function isWritable(string $path): bool;

}
