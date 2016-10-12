<?php
namespace TeaPress\Tests\Signals;

use TeaPress\Tests\Base\ServiceTestCase;
use TeaPress\Tests\Signals\Mocks\HookableService;

/**
*
*/
class HookableServiceTest extends ServiceTestCase
{
	protected $signals;
	protected $hookable;

	protected $servicePropertyName = 'hookable';
	protected $serviceName = 'signals.hookable_mock';
	protected $serviceClass = HookableService::class;

	protected function setUp()
	{
		parent::setUp();
		$this->signals = $this->app('signals');
	}


	public function testRegisteredInIocContainer()
	{
		$this->runRegisteredTest();
	}

	public function testServiceAliases()
	{
		$this->runServiceAliasesTest();
	}


	public function testHookToInstance()
	{
		$hookable = $this->hookable;

		$hookable->booting( function($arg){
			$this->assertInstanceOf(HookableService::class, $arg);
		});

		$hookable->boot();
	}

	public function testStaticHooking()
	{
		$hookable = $this->hookable;

		HookableService::on('starting', function($arg) use($hookable){
			$this->assertEquals($hookable, $arg);
		});

		$hookable->start();
	}

	public function testFilters()
	{
		$hookable = $this->hookable;

		$response = __METHOD__;

		HookableService::on('filters', function($arg) use($response){
			return $response;
		});

		$this->assertEquals($response, $hookable->runFilters());
	}


	public function testResolvesTagName()
	{
		$hookable = $this->hookable;

		$hookable->on('check_tag', function(){
			return $this->signals->current();
		});

		$this->assertEquals( $this->serviceName.':check_tag', $hookable->checkTag());

	}



}