<?php
namespace TeaPress\Config;

use TeaPress\Filesystem\Filesystem;
use TeaPress\Core\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
	public function register()
	{
		$this->registerLoader();
		$this->registerManager();
	}

	protected function registerLoader()
	{
		$this->app->bind('config.loader', function($app){
			return new FileLoader(new Filesystem);
		});
	}

	protected function registerManager()
	{
		$this->app->singleton('config', function($app)
		{
			$loader = $app['config.loader'];
			$signals = $app['signals'];
			$path = $app['path.config'];
			return new Manager($loader, $signals, $path);
		});
	}

}