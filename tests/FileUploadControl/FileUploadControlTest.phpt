<?php
declare(strict_types = 1);

namespace NepadaTests\FileUploadControl;

use Nepada\FileUploadControl\FileUploadControl;
use NepadaTests\FileUploadControl\Fixtures\TestPresenter;
use NepadaTests\FileUploadControl\Storage\InMemoryStorage;
use NepadaTests\FileUploadControl\Storage\InMemoryStorageManager;
use NepadaTests\TestCase;
use Nette;
use Nette\Application\Responses\FileResponse;
use Nette\Forms\Controls\BaseControl;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';


/**
 * @testCase
 */
class FileUploadControlTest extends TestCase
{

    public function testValueIsEmptyWhenDisabled(): void
    {
        $control = $this->createFileUploadControl();
        Assert::count(1, $control->getValue());

        $control->setDisabled();
        Assert::count(0, $control->getValue());
    }

    public function testDownload(): void
    {
        $control = $this->createFileUploadControl();
        try {
            $control->handleDownload(InMemoryStorageManager::TEST_NAMESPACE, 'image-png');
        } catch (Nette\Application\AbortException $exception) {
            // noop
        }
        $presenter = $control->getPresenter();
        Assert::type(TestPresenter::class, $presenter);
        $response = $presenter->response;
        Assert::type(FileResponse::class, $response);
        Assert::same(__DIR__ . '/Fixtures/image.png', $response->getFile());
        Assert::same('image.png', $response->getName());
        Assert::same('application/octet-stream', $response->getContentType());
    }

    private function createFileUploadControl(): FileUploadControl
    {
        $storage = InMemoryStorage::createWithFiles(__DIR__ . '/Fixtures/image.png');
        $storageManager = InMemoryStorageManager::createWithTestNamespace($storage);

        $form = TestPresenter::create()->getForm();
        $control = new FileUploadControl($storageManager, 'Test control');
        $form['fileUpload'] = $control;
        assert($control['namespace'] instanceof BaseControl);
        $control['namespace']->setValue(InMemoryStorageManager::TEST_NAMESPACE);

        return $control;
    }

}


(new FileUploadControlTest())->run();
