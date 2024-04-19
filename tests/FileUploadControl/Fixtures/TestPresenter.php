<?php
declare(strict_types = 1);

namespace NepadaTests\FileUploadControl\Fixtures;

use Latte;
use Nette;
use Nette\Application\Routers\SimpleRouter;
use Nette\Application\UI\Form;
use Nette\Application\UI\Template;
use ReflectionMethod;

final class TestPresenter extends Nette\Application\UI\Presenter
{

    public ?Nette\Application\Response $response = null;

    /**
     * @var callable|null
     */
    private $formConfigurator;

    public static function create(?Nette\Http\IRequest $httpRequest = null, ?callable $formConfigurator = null): self
    {
        $presenter = new self();
        $presenter->autoCanonicalize = false;

        $presenter->formConfigurator = $formConfigurator;

        $primaryDependencies = [];
        $rc = new ReflectionMethod($presenter, 'injectPrimary');
        foreach ($rc->getParameters() as $parameter) {
            if ($parameter->isDefaultValueAvailable()) {
                continue;
            }
            $primaryDependencies[$parameter->getName()] = null;
        }
        $primaryDependencies['httpRequest'] = $httpRequest ?? $presenter->createHttpRequest();
        $primaryDependencies['httpResponse'] = $presenter->createHttpResponse();
        $primaryDependencies['router'] = new SimpleRouter();
        $primaryDependencies['templateFactory'] = $presenter->createTemplateFactory();
        $primaryDependencies['presenterFactory'] = new Nette\Application\PresenterFactory();
        $presenter->injectPrimary(...$primaryDependencies);

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

        $form->onSubmit[] = function (): void {
        };

        return $form;
    }

    public function actionDefault(): void
    {
    }

    public function sendTemplate(?Template $template = null): void
    {
        $this->sendResponse(new Nette\Application\Responses\TextResponse(''));
    }

    public function sendResponse(Nette\Application\Response $response): void
    {
        $this->response ??= $response;
        parent::sendResponse($response);
    }

    private function createTemplateFactory(): Nette\Bridges\ApplicationLatte\TemplateFactory
    {
        return new Nette\Bridges\ApplicationLatte\TemplateFactory($this->createLatteFactory());
    }

    private function createLatteFactory(): Nette\Bridges\ApplicationLatte\LatteFactory
    {
        return new class () implements Nette\Bridges\ApplicationLatte\LatteFactory {

            public function create(): Latte\Engine
            {
                $latte = new Latte\Engine();

                $latte->addExtension(new Nette\Bridges\FormsLatte\FormsExtension());

                return $latte;
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
