<?php
namespace TeaPress\Tests\Signals;

use TeaPress\Tests\Base\TestCase;
use TeaPress\Tests\Signals\Mocks\Hookable;

/**
*
*/
class HookableTest extends TestCase
{
	protected $signals;
	protected $hookable;

	protected function getHookable($boot = false)
	{
		if(!$this->hookable)
			$this->hookable = new Hookable;

		if($boot)
			$this->hookable->boot();

		return $this->hookable;
	}

	protected function setUp()
	{
		$this->signals = $this->app('signals');
		$this->getHookable();

	}

	public function testHookToInstance()
	{
		$hookable = $this->getHookable();

		$hookable->booting( function($arg){
			$this->assertInstanceOf(Hookable::class, $arg);
		});

		$hookable->boot();
	}

	public function testStaticHooking()
	{
		$hookable = $this->getHookable();

		Hookable::on('starting', function($arg) use($hookable){
			$this->assertEquals($hookable, $arg);
		});

		$hookable->start();
	}

	public function testMappers()
	{
		$hookable = $this->getHookable();

		$response = __METHOD__;

		Hookable::on('mappers', function($arg) use($response){
			return $response;
		});

		$this->assertEquals($response, $hookable->runMappers());
	}

}