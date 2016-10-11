<?php
namespace TeaPress\Tests\Misc;

use PHPUnit_Framework_TestCase;

use TeaPress\Signals\Hub;
use TeaPress\Tests\Base\AppTrait;

use TeaPress\Tests\Misc\Mocks\Handler;

class PerformanceTest extends PHPUnit_Framework_TestCase
{
	use AppTrait;

	protected function methodTag($method)
	{
		return str_replace('::', ':', $method);
	}

	protected function setUp()
	{

	}

	public function testCreateManyInstances()
	{
		$instances = [];
		for ($i=0; $i < 10000; $i++) {
			$callback = function($value) use ($i){
				$this->assertEquals($value, $i);
				return 'Callback #'.$i+1;
			};

			$instances[$i] = new Handler( $this->app(), $callback );
		}

		foreach ($instances as $key => $handler) {
			$this->assertEquals( 'Callback #'.$key+1, $handler($key) );
		}
	}
}