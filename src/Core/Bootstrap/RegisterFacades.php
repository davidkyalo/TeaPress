<?php
namespace TeaPress\Core\Bootstrap;

use TeaPress\Core\Facade;
use TeaPress\Contracts\Core\Application;

class RegisterFacades
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
		Facade::clearResolvedInstances();

		Facade::setFacadeApplication($app);

		$app->make('alias')->set( $app->config->get('app.aliases', []) );
	}
}