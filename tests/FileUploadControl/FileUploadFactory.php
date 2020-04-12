<?php
declare(strict_types = 1);

namespace NepadaTests\FileUploadControl;

use Nette;
use Nette\Http\FileUpload;
use Tester\FileMock;

final class FileUploadFactory
{

    use Nette\StaticClass;

    public static function create(string $name, int $size, string $tmpFile, int $error): FileUpload
    {
        return new FileUpload([
            'name' => $name,
            'size' => $size,
            'tmp_name' => $tmpFile,
            'error' => $error,
        ]);
    }

    public static function createFromFile(string $tmpFile, ?string $name = null): FileUpload
    {
        $name ??= basename($tmpFile);
        $size = filesize($tmpFile);
        assert(is_int($size));
        return self::create($name, $size, $tmpFile, UPLOAD_ERR_OK);
    }

    public static function createWithContents(string $contents, string $name): FileUpload
    {
        return self::create($name, strlen($contents), FileMock::create($contents), UPLOAD_ERR_OK);
    }

    public static function createWithSize(int $size, ?string $name = null): FileUpload
    {
        $name ??= 'name';
        return self::create($name, $size, 'tmp_name', UPLOAD_ERR_OK);
    }

}
