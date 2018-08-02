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
        echo "beta";
    }

    public function gammaAction()
    {
        $this->enableLayout(false);
        echo "gamma";
    }

    public function deltaAction()
    {
        $this->setLayout('main2');
        $this->getView()->a = 'some';
        $this->getView()->b = 123;
        //$this->getView()->display('delta.phtml');
        echo $this->render();
    }
}
