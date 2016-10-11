<?php
namespace TeaPress\Tests\Signals;

use TeaPress\Signals\Hub;
use TeaPress\Signals\Traits\Emitter;
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
		$this->app->instance('signals', new Hub( $this->app ));
		$this->app->bind('signals.hookable_mock', function($app){
			return new HookableService;
		});
		$this->aliasServices($this->serviceAliases());
	}

	public function registerHandlers()
	{

	}

	public function boot()
	{
		Emitter::setSignalsHub($this->app['signals']);
	}
}