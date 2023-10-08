<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl;

use Nette\Utils\Html;

interface FileUploadControlFactory
{

    public function create(string|Html|null $caption): FileUploadControl;

}
