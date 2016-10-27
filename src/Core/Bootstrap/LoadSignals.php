<?php
namespace TeaPress\Core\Bootstrap;

use TeaPress\Contracts\Core\Application;

class LoadSignals
{
	/**
	* Bootstrap the given application.
	*
	* @param  \TeaPress\Contracts\Core\Application  $app
	*
	* @return void
	*/
	public function bootstrap(Application $app)
	{
		$app->instance('signals', $signals = new \TeaPress\Signals\Hub($app) );

		$app->register('TeaPress\Signals\SignalsKernel');

		$signals = $app->make('signals');

		foreach ($app->kernels() as $kernel) {
			$kernel->setSignals( $signals );
		}

	}
}