<?php
namespace TeaPress\Tests\Signals;

use TeaPress\Signals\Signals;
use TeaPress\Signals\TagResolver;
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
				'TeaPress\Signals\Signals',
				'TeaPress\Contracts\Signals\Hub',
				'TeaPress\Contracts\Signals\Signals',
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
		$this->registerTagResolver();
		$this->aliasServices($this->serviceAliases());
	}


	public function registerHub()
	{
		$this->app->singleton('signals', function($app) {
			return $app->make('signals.factory');
		});
	}

	public function registerTagResolver()
	{
		$this->app->singleton('signals.tag_resolver', function($app) {
			return new TagResolver($app);
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

			$hub = new Signals( $container, $app['signals.tag_resolver'] );

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