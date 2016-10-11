<?php

namespace TeaPress\Tests\Http;

use Tea\Utils\Str;

use PHPUnit_Framework_TestCase;

class RouterTest extends PHPUnit_Framework_TestCase
{
	public function _testDummy()
	{
		$this->assertEquals('a', 'a');
	}
	public function _testRoutesMemoryUsage()
	{
		$router = teapress('router');
		$num = 100000;
		for ($i=1; $i <= $num; $i++) {
			$router->get([
				'as'	=> 'endpoint_'.$i,
				'uri'	=> "/route/".Str::pad($i, -3, '0')."/{name}/",
				'uses'	=> function($name){

				}
			]);
		}

		$this->assertEquals($num, count($router->getAllRoutes('get')) );

	}


}