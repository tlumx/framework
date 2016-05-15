<?php
/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Test\Router;

use Tlumx\Router\Router;
use Tlumx\Router\Result;

class RouterTest extends \PHPUnit_Framework_TestCase
{
    public function testImplements()
    {
        $router = new Router();
        $this->assertInstanceOf('Tlumx\Router\RouterInterface', $router);
    }     
    
    /**
     * @dataProvider invalidRoutesProvider
     */
    public function testSetInvalidRoutes(array $routes)
    {
        $router = new Router();
        $this->setExpectedException('InvalidArgumentException');
        $router->setRoutes($routes);
    }
    
    public function invalidRoutesProvider()
    {
        return [
            [
                ['name' => 'value']
            ],
            [
                ['name' => []]
            ],
            [
                ['name' => [
                    'methods' => ''
                ]]
            ],
            [
                ['name' => [
                    'methods' => '',
                    'route' => '',
                ]]
            ],
            [
                ['name' => [
                    'methods' => '',
                    'route' => '',
                    'handler' => [],
                    'filters' => [],
                    'child_routes' => ''
                ]]
            ], 
            [
                ['my' => [
                    'methods' => '',
                    'route' => '',
                    'handler' => [],
                    'filters' => [],
                    'middlewares' => [],
                    'child_routes' => [
                        'my' => [
                            'methods' => '',
                            'pattern' => '',
                            'handler' => [],
                            'filters' => [],                            
                        ]
                    ]
                ]]
            ],            
        ];
    }
    
    /**
     * @dataProvider invalidRouteProvider
     */
    public function testAddInvalidRoute($name, $methods, $route, $handler, $filters, $middlewares, $parent)
    {
        $router = new Router();
        $router->setRoutes([
            'my' => [
                'methods' => ['GET'],
                'route' => '/my',
                'handler' => []
            ]
        ]);
        $this->setExpectedException('InvalidArgumentException');
        $router->setRoute($name, $methods, $route, $handler, $filters, $middlewares, $parent);
    }    
    
    public function invalidRouteProvider()
    {
        return [
            ['my', [], '/my', [], [], [], null],
            ['new', [], false, [], [], [], null],
            ['new', [], '/new', [], [], [], 'not_isset_route'],
            ['new', [], '/new', [], [], [], 'new']
        ];
    }
        
    public function testInvalidAddMiddlewares()
    {
        $router = new Router();
        $this->setExpectedException('InvalidArgumentException');
        $router->addMiddleware('not_isset_route_name', []);
    }
    
    public function testCreatePath()
    {
        $router = new Router();
        $router->setRoutes([
            'my' => [
                'methods' => ['GET'],
                'route' => '/my/{id}',
                'handler' => [],
                'filters' => ['id' => '([\d-]+)'],
                'child_routes' => [
                    'my-sub' => [
                        'methods' => ['GET', 'POST'],
                        'route' => '/sub/{p}',
                        'handler' => [],
                        'filters' => ['p'=>'(\d+)']
                    ]
                ]
            ]            
        ]);        
        
        $this->assertEquals('/my/2', $router->createPath('my', ['id'=>2]));
        $this->assertEquals('/my/2/sub/3', $router->createPath('my-sub', ['id'=>2, 'p'=>3]));
        $this->assertEquals('/my/2/sub/3?a=b&x=y', $router->createPath('my-sub', ['id'=>2, 'p'=>'3'], ['a'=>'b','x'=>'y']));
    }
        
    public function testCreatePathWithBasePath()
    {
        $router = new Router();
        $router->setBasePath('/some');
        $router->setRoutes([
            'my' => [
                'methods' => ['GET'],
                'route' => '/my/{id}',
                'handler' => [],
                'filters' => ['id' => '([\d-]+)'],
                'child_routes' => [
                    'my-sub' => [
                        'methods' => ['GET', 'POST'],
                        'route' => '/sub/{p}',
                        'handler' => [],
                        'filters' => ['p'=>'(\d+)']
                    ]
                ]
            ]            
        ]);        
        
        $this->assertEquals('/some/my/2', $router->createPath('my', ['id'=>2]));
        $this->assertEquals('/some/my/2/sub/3', $router->createPath('my-sub', ['id'=>2, 'p'=>3]));
        $this->assertEquals('/some/my/2/sub/3?a=b&x=y', $router->createPath('my-sub', ['id'=>2, 'p'=>'3'], ['a'=>'b','x'=>'y']));
    }
        
    /**
     * @dataProvider routerMatchProvider
     */
    public function testMatch($urlPath, $method, $status, $options)
    {
        $router = new Router();
        $router->setRoutes([
            'my' => [
                'methods' => ['GET'],
                'route' => '/my/{id}',
                'handler' => [],
                'filters' => ['id' => '([\d-]+)'],
                'child_routes' => [
                    'my-sub' => [
                        'methods' => ['GET', 'POST'],
                        'route' => '/sub/{p}',
                        'handler' => [],
                        'filters' => ['p'=>'(\d+)']
                    ]
                ]
            ]            
        ]);
        
        $result = $router->match($method, $urlPath);        
        
        $this->assertEquals('Tlumx\Router\Result', get_class($result));
        
        switch ($status) {
            case Result::FOUND:
                $this->assertTrue($result->isFound());                                
                $this->assertEquals($options['name'], $result->getName());
                $this->assertEquals($options['handler'], $result->getHandler());
                $this->assertEquals($options['params'], $result->getParams());
                $this->assertEquals($options['methods'], $result->getAllowedMethods());
                break;
            case Result::NOT_FOUND:
                $this->assertTrue($result->isNotFound());
                $this->assertEquals($options['name'], $result->getName());
                $this->assertEquals($options['handler'], $result->getHandler());
                $this->assertEquals($options['params'], $result->getParams());                
                $this->assertEquals($options['methods'], $result->getAllowedMethods());
                break;
            case Result::METHOD_NOT_ALLOWED:
                $this->assertTrue($result->isMethodNotAllowed());
                $this->assertEquals($options['name'], $result->getName());
                $this->assertEquals($options['handler'], $result->getHandler());
                $this->assertEquals($options['params'], $result->getParams());
                $this->assertEquals($options['methods'], $result->getAllowedMethods());
                break;
        }                
    }
    
    public function routerMatchProvider()
    {
        return [
            ['/some', 'POST', Result::NOT_FOUND, ['name'=>'', 'handler'=>[],'params'=>[], 'middlewares' => [], 'methods'=>[]]],
            ['/my/30/sub/70', 'GET', Result::FOUND, ['name'=>'my-sub', 'handler'=>[],'params'=>['id'=>30,'p'=>70], 'middlewares' => [], 'methods'=>[]]],
            ['/my/55', 'POST', Result::METHOD_NOT_ALLOWED, ['name'=>'my', 'handler'=>[],'params'=>[], 'middlewares' => [], 'methods'=>['GET']]],
        ];        
    }
    
    public function testMatchWithBasePathAndMiddlewares()
    {
        $router = new Router();
        $router->setBasePath('my-site');
        $router->setRoutes([
            'my' => [
                'methods' => ['GET'],
                'route' => '/my/{id}',
                'handler' => [],
                'filters' => ['id' => '([\d-]+)'],
                'middlewares' => [
                    'midd1',
                    'midd2'
                ],
                'child_routes' => [
                    'my-sub' => [
                        'methods' => ['GET', 'POST'],
                        'route' => '/sub/{p}',
                        'handler' => [],
                        'filters' => ['p'=>'(\d+)'],
                        'middlewares' => [
                            'midd2.1',
                            'midd2.2'
                        ]                        
                    ]
                ]
            ]            
        ]);
        $router->addMiddleware('my', [
            'midd3.1',
            'midd3.2'
        ]);
        $router->addMiddleware('my-sub', [
            'midd4.1',
            'midd4.2'
        ]);
        
        $result = $router->match('GET', '/my-site/my/10');
        $this->assertTrue($result->isFound());                                
        $this->assertEquals('my', $result->getName());
        $this->assertEquals([], $result->getHandler());
        $this->assertEquals(['id'=>10], $result->getParams());
        $this->assertEquals([
            'midd1',
            'midd2',
            'midd3.1',
            'midd3.2'
        ], $result->getMiddlewares());      
        
        $result = $router->match('GET', '/my-site/my/10/sub/22');
        $this->assertTrue($result->isFound());                                
        $this->assertEquals('my-sub', $result->getName());
        $this->assertEquals([], $result->getHandler());
        $this->assertEquals(['id'=>10, 'p'=>22], $result->getParams());
        $this->assertEquals([
            'midd1',
            'midd2',
            'midd2.1',
            'midd2.2',
            'midd3.1',
            'midd3.2',
            'midd4.1',
            'midd4.2'
        ], $result->getMiddlewares());         
    }
}
