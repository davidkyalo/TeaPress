<?php
namespace TeaPress\Tests\Signals;

use TeaPress\Signals\Hub;
use TeaPress\Signals\Traits\Online;
use TeaPress\Tests\Base\TestKernel;
use TeaPress\Tests\Signals\Mocks\HookableService;

class Kernel extends TestKernel
{

	protected function serviceAliases()
	{
		return [
			'signals' => [
				'events',
				'TeaPress\Signals\Hub',
				'TeaPress\Contracts\Signals\Hub',
				'Illuminate\Contracts\Events\Dispatcher'
			],
			'signals.hookable_mock' => [
				'TeaPress\Tests\Signals\Mocks\HookableService'
			]
		];
	}

	public function register()
	{
		$this->registerFactory();
		$this->registerHub();
		$this->registerHookableService();

		$this->aliasServices($this->serviceAliases());
	}


	public function registerHub()
	{
		$this->app->singleton('signals', function($app) {
			return $app->make('signals.factory');
		});
	}



	protected function registerHookableService()
	{
		$this->app->bind('signals.hookable_mock', function($app){
			return new HookableService;
		});
	}

	protected function registerFactory()
	{
		$this->app->bind('signals.factory', function($app, $args){

			$container = isset($args['app']) ? $args['app'] : $app;

			$hub = new Hub( $container );

			Online::setSignals($hub);

			return $hub;
		});

	}

	protected function getTearDownExtension()
	{
		return function(){

		};
	}

	public function boot()
	{

	}
}