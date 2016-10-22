<?php
namespace TeaPress\Tests\Core\Application;


class AppEventsTest extends BaseTestCase
{
	protected $serviceName='app.new';


	protected function methodTag($method)
	{
		return str_replace('::', ':', $method);
	}


	public function testBindCallback()
	{
		$app = $this->app;

		$event = $this->methodTag(__METHOD__);

		$callback = function(){
			//
		};

		$app->bindTestAppCallback($event, $callback);

		$this->assertTrue( $app->signals->isBound($callback, [ $app, $event ]) );
	}


	public function testFireCallbacks()
	{
		$app = $this->app;

		$event = $this->methodTag(__METHOD__);

		$callback = function($app) {
			$app->instance('testFireCallbacks.passed', true);
		};

		$app->bindTestAppCallback($event, $callback);

		$app->fireTestAppCallbacks($event);

		$this->assertTrue( $app['testFireCallbacks.passed'] );
	}



	public function testBindCallbackOnce()
	{
		$app = $this->app;

		$event = $this->methodTag(__METHOD__);

		$count = 0;

		$callback = function($app) use(&$count) {
			$count += 1;
		};

		$app->bindTestAppCallback($event, $callback, 10, true);

		$app->fireTestAppCallbacks($event);

		$this->assertEquals(1,$count);
	}



}