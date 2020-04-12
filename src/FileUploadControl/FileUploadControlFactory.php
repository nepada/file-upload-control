<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl;

use Nette\Utils\Html;

interface FileUploadControlFactory
{

    /**
     * @param string|Html|null $caption
     * @return FileUploadControl
     */
    public function create($caption): FileUploadControl;

}
