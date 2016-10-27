<?php
namespace TeaPress\Signals;

use TeaPress\Core\Kernel;
use TeaPress\Signals\Traits\Online;

class SignalsKernel extends Kernel
{
	protected $registered = false;

	public function register()
	{
		// if(!$this->app->bound('signals') || !($this->app->make('signals') instanceof "TeaPress\Contracts\Signals\Hub")){
		// 	$this->app->instance('signals', $hub = new Hub($this->app));
		// }

		Online::setSignals($this->app->signals);
	}

	protected function signalsIsBound()
	{

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