<?php
declare(strict_types = 1);

namespace NepadaTests\Bridges\FileUploadControlForms;

use Nepada\FileUploadControl\FileUploadControl;
use Nepada\FileUploadControl\FileUploadControlFactory;
use NepadaTests\FileUploadControl\Storage\InMemoryStorageManager;
use Nette;
use Nette\Utils\Html;

final class TestFileUploadControlFactory implements FileUploadControlFactory
{

    use Nette\SmartObject;

    public function create(Html|string|null $caption): FileUploadControl
    {
        $storageManager = new InMemoryStorageManager();
        return new FileUploadControl($storageManager, $caption);
    }

}
