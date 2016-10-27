<?php
namespace TeaPress\Tests\Core\Bootstrap;


use TeaPress\Tests\Base\ServiceTestCase;
use TeaPress\Tests\Core\Mocks\Bootstrap\Factory;
/**
*
*/
class FactoryTest extends ServiceTestCase
{

	public function testAppClass()
	{
		$factory = new Factory;
		$klass = get_class(
			$factory->app()
		);
		$factory->useConfigPath(dirname(__DIR__).'/data/config');
		$factory->bootstrap();
		// pprint("App {$klass}");
		// pprint("Kernels", array_keys($factory->app()->kernels()));
	}
}