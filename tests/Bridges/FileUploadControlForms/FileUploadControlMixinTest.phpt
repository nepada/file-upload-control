<?php
declare(strict_types = 1);

namespace NepadaTests\Bridges\FileUploadControlForms;

use Nepada\FileUploadControl\FileUploadControl;
use NepadaTests\TestCase;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';


/**
 * @testCase
 */
class FileUploadControlMixinTest extends TestCase
{

    public function testMixin(): void
    {
        $form = new TestForm();
        $form->injectFileUploadFactory(new TestFileUploadControlFactory());
        $input = $form->addFileUpload('test', 'File upload');
        Assert::type(FileUploadControl::class, $input);
        Assert::same('File upload', $input->getCaption());
        Assert::same($input, $form['test']);
    }

}

(new FileUploadControlMixinTest())->run();
