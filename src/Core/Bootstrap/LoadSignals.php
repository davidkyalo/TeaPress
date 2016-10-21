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
		$app->register('TeaPress\Config\SignalsKernel');
	}
}