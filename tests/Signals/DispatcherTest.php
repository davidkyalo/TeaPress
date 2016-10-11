<?php
namespace TeaPress\Tests\Signals;

use TeaPress\Signals\Hub;
use TeaPress\Tests\Base\ServiceTestCase;

use TeaPress\Tests\Signals\Mocks\Handler;

class DispatcherTest extends ServiceTestCase
{

	/**
	* @var \TeaPress\Signals\Hub
	*/
	protected $signals;

	protected $serviceName = 'signals';

	protected $serviceClass = Hub::class;

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
		$this->assertTrue($this->signals->isBound($callback, $tag));
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

		$value = 0;

		$this->signals->listen($tag, function($value){
			return $value+1;
		});

		$this->signals->listen($tag, function($value){
			return $value+2;
		});

		$this->signals->listen($tag, function($value){
			return $value+3;
		});

		$this->assertEquals([1, 2, 3], $this->signals->fire($tag, $value));
	}


	public function testFlushesResponses()
	{
		$tag = $this->methodTag(__METHOD__);

		$value = 0;

		$this->signals->listen($tag, function($value){
			return $value+1;
		});

		$this->signals->listen($tag, function($value){
			return $value+2;
		});

		$this->signals->listen($tag, function($value){
			return $value+3;
		});

		$this->signals->fire($tag, $value);

		$this->assertNull( $this->signals->responses($tag, null) );

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


	public function testFlushesHaltables()
	{
		$tag = $this->methodTag(__METHOD__);

		$response = time();

		$this->signals->listen($tag, function() {
			return;
		});

		$this->signals->listen($tag, function() use($tag) {
			return 2;
		});

		$this->signals->listen($tag, function() {
			return 3;
		});

		$this->signals->until($tag);

		$this->assertFalse($this->signals->halting($tag));
	}

}