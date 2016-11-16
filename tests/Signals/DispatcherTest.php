<?php
namespace TeaPress\Tests\Signals;

use TeaPress\Utils\Arr;
use TeaPress\Utils\Str;
use TeaPress\Signals\Signals;
use TeaPress\Tests\Base\ServiceTestCase;

use TeaPress\Tests\Signals\Mocks\Handler;

class DispatcherTest extends ServiceTestCase
{

	/**
	* @var \TeaPress\Signals\Hub
	*/
	protected $signals;

	protected $serviceName = 'signals';

	protected $serviceClass = Signals::class;

	protected function methodTag($method)
	{
		return str_replace('::', ':', $method);
	}

	public function testListen()
	{
		$tag = $this->methodTag(__METHOD__);
		$callback = function(){
			return 'callback';
		};
		$this->signals->listen($tag, $callback);
		$this->assertTrue($this->signals->has($tag, $callback));
	}

	public function testHasListeners()
	{
		$tag = $this->methodTag(__METHOD__);
		$callback = function(){
			return 'callback';
		};

		$before = $this->signals->hasListeners($tag);

		$this->signals->listen($tag, $callback);

		$after = $this->signals->hasListeners($tag);

		$this->assertTrue( (!$before && $after) );
	}


	public function testFire()
	{
		$tag = $this->methodTag(__METHOD__);

		$wild = "*".__FUNCTION__;

		$value = 3;

		$expected = [];

		$meths = ['listen'];
		foreach ($meths as $meth) {
			for ($i=1; $i<=5; $i++) {
				// $inc = $i * (1;
				$expected[] = $value*$i;

				$this->signals->{$meth}($tag, function($value) use($i){
					return $value*$i;
				});

				$this->signals->{$meth}($wild, function($value) use($i){
					return $value*$i;
				});
			}
		}

		$this->signals->listen($wild, function(){
			return;
		});

		$response = $this->signals->fire($tag, $value);
		$expected = array_merge($expected, $expected);

		$this->assertEquals($expected, $response);
	}

	public function testUntil()
	{
		$tag = $this->methodTag(__METHOD__);

		$this->signals->listen($tag, function() {
			return;
		}, 0);

		$this->signals->listen($tag, function() use($tag) {
			return 2;
		});

		$this->signals->listen($tag, function() {
			return 5;
		}, 5);

		$this->assertEquals(5, $this->signals->until($tag));
	}

}