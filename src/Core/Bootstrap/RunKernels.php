<?php
namespace TeaPress\Core\Bootstrap;

use TeaPress\Contracts\Core\Application;

class RunKernels
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
		$app->run();
	}
}