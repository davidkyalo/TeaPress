<?php
namespace TeaPress\Config;

use TeaPress\Core\Kernel;
use TeaPress\Filesystem\Filesystem;

class ConfigKernel extends Kernel
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

	public function registerAliases()
	{
		$this->app->alias('config', [
			'TeaPress\Config\Manager',
			'TeaPress\Contracts\Config\Manager',
			'TeaPress\Contracts\Config\Repository'
		]);
	}

}