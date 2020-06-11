<?php
declare(strict_types = 1);

namespace NepadaTests\FileUploadControl\Fixtures;

use Latte;
use Nette;
use Nette\Application\Routers\SimpleRouter;
use Nette\Application\UI\Form;
use Nette\Application\UI\ITemplate;

final class TestPresenter extends Nette\Application\UI\Presenter
{

    public ?Nette\Application\IResponse $response = null;

    /**
     * @var callable|null
     */
    private $formConfigurator;

    public static function create(?Nette\Http\IRequest $httpRequest = null, ?callable $formConfigurator = null): TestPresenter
    {
        $presenter = new self();
        $presenter->autoCanonicalize = false;

        $presenter->formConfigurator = $formConfigurator;

        $httpRequest ??= $presenter->createHttpRequest();
        $httpResponse = $presenter->createHttpResponse();
        $templateFactory = $presenter->createTemplateFactory();
        $router = new SimpleRouter();
        $presenter->injectPrimary(null, null, $router, $httpRequest, $httpResponse, null, null, $templateFactory);

        $presenter->setParent(null, 'Test');
        $presenter->changeAction('default');

        return $presenter;
    }

    public function getForm(): Form
    {
        return $this->getComponent('form');
    }

    protected function createComponentForm(): Form
    {
        $translator = new DummyTranslator();

        $form = new Form();
        $form->setTranslator($translator);

        if ($this->formConfigurator !== null) {
            call_user_func($this->formConfigurator, $form);
        }

        return $form;
    }

    public function actionDefault(): void
    {
    }

    public function sendTemplate(?ITemplate $template = null): void
    {
        $this->sendResponse(new Nette\Application\Responses\TextResponse(''));
    }

    public function sendResponse(Nette\Application\IResponse $response): void
    {
        $this->response ??= $response;
    }

    private function createTemplateFactory(): Nette\Bridges\ApplicationLatte\TemplateFactory
    {
        return new Nette\Bridges\ApplicationLatte\TemplateFactory($this->createLatteFactory());
    }

    private function createLatteFactory(): Nette\Bridges\ApplicationLatte\ILatteFactory
    {
        return new class () implements Nette\Bridges\ApplicationLatte\ILatteFactory {

            public function create(): Latte\Engine
            {
                return new Latte\Engine();
            }

        };
    }

    private function createHttpRequest(): Nette\Http\IRequest
    {
        return new Nette\Http\Request(new Nette\Http\UrlScript('https://example.com'));
    }

    private function createHttpResponse(): Nette\Http\IResponse
    {
        return new Nette\Http\Response();
    }

}
