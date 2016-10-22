<?php
namespace TeaPress\Tests\Core\Application;


use TeaPress\Tests\Core\Mocks\Bootstrap\Silent;
use TeaPress\Tests\Core\Mocks\Bootstrap\ItWorks;
use TeaPress\Tests\Core\Mocks\Bootstrap\CountExecutions;

class BootstappersTest extends BaseTestCase
{
	protected $serviceName='app.new';

	public function testItsWorking()
	{
		$app = $this->newApp();
		$app->bootstrapWith(ItWorks::class);
		$this->assertTrue( ($app['it_works'] && $app->bootstrapped( ItWorks::class ) && $app->hasBeenBootstrapped()) );

	}

	public function testBeforeBootstrappingEvent()
	{
		$app = $this->newApp();
		$called = false;
		$app->beforeBootstrapping(ItWorks::class, function() use(&$called){
			$called = true;
		});

		$app->bootstrapWith(ItWorks::class);

		$this->assertTrue($called);

	}

	public function testAfterBootstrappingEvent()
	{
		$app = $this->newApp();
		$called = false;
		$app->afterBootstrapping(ItWorks::class, function() use(&$called){
			$called = true;
		});

		$app->bootstrapWith(ItWorks::class);

		$this->assertTrue($called);

	}

	public function testExecutesOnce()
	{
		$app = $this->newApp();

		$app->bootstrapWith(CountExecutions::class);
		$app->bootstrapWith(CountExecutions::class);
		$app->bootstrapWith(CountExecutions::class);

		$this->assertEquals(1, $app['num_executions']);

	}

	public function testCanBeSilenced()
	{
		$app = $this->newApp();

		$app->instance('is_silent', true);

		$app->beforeBootstrapping('*', function($app){
			$app->instance('is_silent', false);
		});

		$app->afterBootstrapping('*', function($app){
			$app->instance('is_silent', false);
		});

		$bootstrappers = [ ItWorks::class, Silent::class, CountExecutions::class ];

		$app->bootstrapWith($bootstrappers, false, true);

		$this->assertTrue( ( !$app->hasBeenBootstrapped() && $app['is_silent'] ) );
	}

	/**
	* @expectedException PHPUnit_Framework_Error_Notice
	*/
	public function testWarnsWhenReady()
	{
		$app = $this->newApp();

		$app->setAppReady();

		$app->bootstrapWith(ItWorks::class);
	}


}