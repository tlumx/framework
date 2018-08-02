<?php
/**
 * Tlumx (https://tlumx.com/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2018 Yaroslav Kharitonchuk
 * @license   https://github.com/tlumx/framework/blob/master/LICENSE.md  (MIT License)
 */
namespace Foo;

use Tlumx\Application\Controller;

class FooController extends Controller
{
    public function aboutAction()
    {
        echo 'about';
    }

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
