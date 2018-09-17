<?php

namespace Foo;

use Tlumx\Application\Controller;

class FooController extends Controller
{
    public function alphaAction()
    {
    }

    public function betaAction()
    {
        return "beta";
    }

    public function gammaAction()
    {
        $this->enableLayout(false);
        return "gamma";
    }

    public function deltaAction()
    {
        $this->setLayout('main2');
        $this->getView()->a = 'some';
        $this->getView()->b = 123;
        return $this->render();
    }

    public function respAction()
    {
        $response = $this->getContainer()->get('response');
        $response->getBody()->write('from resp action');
        return $response;
    }
}
