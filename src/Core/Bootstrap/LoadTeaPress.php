<?php
namespace TeaPress\Core\Bootstrap;

use TeaPress\Core\AliasLoader;
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
		$this->registerTheFilesystem($app);
		$this->setRequestHomePath($app);
	}

	protected function bindAliasLoader($app)
	{
		$app->instance('alias', $loader = AliasLoader::getInstance() );
		$loader->register();
	}

	protected function registerTheFilesystem($app)
	{
		$app->register('TeaPress\Filesystem\FilesystemKernel');
	}

	protected function setRequestHomePath($app)
	{
		$app->make('request')->setHomePath( $app->baseUrl() );
	}
}