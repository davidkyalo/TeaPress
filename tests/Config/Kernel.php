<?php
namespace TeaPress\Tests\Config;

use TeaPress\Config\Manager;
use TeaPress\Config\FileLoader;
use TeaPress\Tests\Base\TestKernel;
use TeaPress\Contracts\Filesystem\Filesystem;
use TeaPress\Contracts\Config\Manager as ManagerContract;
use TeaPress\Contracts\Config\Repository as RepositoryContract;



class Kernel extends TestKernel
{

	protected function serviceAliases()
	{
		return [
			'config' => [
				'TeaPress\Config\Manager',
				'TeaPress\Contracts\Config\Manager',
				'TeaPress\Contracts\Config\Repository'
			]
		];
	}

	public function register()
	{
		$this->app->singleton('config', function($app){
			return new Manager( new FileLoader($app->make(Filesystem::class)), $app['signals'], __DIR__.'/data/default' );
		});
		$this->aliasServices($this->serviceAliases());
	}

	public function boot()
	{

	}
}