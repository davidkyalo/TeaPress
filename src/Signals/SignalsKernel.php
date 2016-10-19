<?php
namespace TeaPress\Signals;

use TeaPress\Core\Kernel;

class SignalsKernel extends Kernel
{
	protected $registered = false;

	public function register()
	{
		$this->app->singleton('signals', function($app){
			return new Hub($app);
		});
	}


	public function registerAliases()
	{
		$this->app->alias('signals', [
			'events',
			'TeaPress\Signals\Hub',
			'TeaPress\Contracts\Signals\Hub',
			'Illuminate\Contracts\Events\Dispatcher'
		]);
	}


}