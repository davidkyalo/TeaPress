<?php
namespace TeaPress\Core\Bootstrap;

// use TeaPress\Config\ConfigKernel;
use TeaPress\Contracts\Core\Application;

class LoadConfiguration
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
		$app->register('TeaPress\Config\ConfigKernel');
	}
}