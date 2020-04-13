<?php
declare(strict_types = 1);

namespace NepadaTests\Bridges\FileUploadControlDI\Fixtures;

use Nette\Application\UI\Form;

interface FormFactory
{

    public function create(): Form;

}
