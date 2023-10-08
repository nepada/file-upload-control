<?php
declare(strict_types = 1);

namespace Nepada\Bridges\FileUploadControlForms;

use Nepada\FileUploadControl\FileUploadControl;
use Nepada\FileUploadControl\FileUploadControlFactory;
use Nette\Utils\Html;

trait FileUploadControlMixin
{

    private FileUploadControlFactory $fileUploadControlFactory;

    public function injectFileUploadFactory(FileUploadControlFactory $factory): void
    {
        $this->fileUploadControlFactory = $factory;
    }

    public function addFileUpload(string|int $name, string|Html|null $label = null): FileUploadControl
    {
        return $this[$name] = $this->fileUploadControlFactory->create($label);
    }

}
