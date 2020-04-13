<?php
declare(strict_types = 1);

namespace NepadaTests\Bridges\FileUploadControlForms;

use Nepada\Bridges\FileUploadControlForms\FileUploadControlMixin;
use Nette;

class TestForm extends Nette\Forms\Form
{

    use FileUploadControlMixin;

}
