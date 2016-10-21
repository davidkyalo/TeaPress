<?php
namespace TeaPress\Core\Bootstrap;

use TeaPress\Contracts\Core\Application;

class LoadTeaPress
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
		$this->bindAliasLoader($app);
	}

	protected function bindAliasLoader($app)
	{
		$app->instance('alias', $loader = AliasLoader::getInstance() );
		$loader->register();
	}
}