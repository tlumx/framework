<?php
/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */
namespace Foo;

use Tlumx\Controller;

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
