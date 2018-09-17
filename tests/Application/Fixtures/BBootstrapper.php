<?php
/**
 * Tlumx (https://tlumx.com/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2018 Yaroslav Kharitonchuk
 * @license   https://github.com/tlumx/framework/blob/master/LICENSE.md  (MIT License)
 */
namespace Tlumx\Tests\Application\Fixtures;

use Tlumx\Application\Bootstrapper;
use Psr\Container\ContainerInterface;
use Tlumx\Application\ConfigureContainerInterface;
use Tlumx\Application\ApplicationEvent as AppEvent;

class BBootstrapper extends Bootstrapper
{
	public function init()
	{
		$this->getContainer()->set('init_service', 'init_service_value');
	}

	public function getConfig()
	{
		return [
			'a' => 'a_from_b',
			'c' => [
                'c2' => 'value_c2_from_b',
                'c3' => [
                    'c3_1' => 'value_c3_1_fom_b',
                    'c3_2' => 'value_c3_2_from_b',
                    'c3_3' => 'value_c3_3_from_b',
                ]
			],
			'd' => 'd_value'
		];
	}

	public function getServiceConfig()
	{
		return [
            'service_container' => [
                'services' => [
                    'service1' => 'value1'
                ]
            ]
		];
	}

	public function postBootstrap()
	{
		return 'postBootstrap';
	}

	public function preRouting()
	{
		return 'preRouting';
	}				

	public function postRouting()
	{
		return 'postRouting';
	}

	public function preDispatch()
	{
		return 'preDispatch';
	}

	public function postDispatch()
	{
		return 'postDispatch';
	}			
}
