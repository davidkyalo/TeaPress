<?php
namespace TeaPress\Filesystem;

use TeaPress\Core\Kernel;

class FilesystemKernel extends Kernel
{
	public function register()
	{
		$this->app->singleton('files', function () {
			return new Filesystem;
		});

		$this->app->bind('finder', function(){
			return Finder::create();
		});
	}

	public function registerAliases()
	{
		$this->app->alias('files', [
			'TeaPress\Filesystem\Filesystem',
			'TeaPress\Contracts\Filesystem\Filesystem',
			'Illuminate\Filesystem\Filesystem',
			'Illuminate\Contracts\Filesystem\Filesystem'
		]);

		$this->app->alias('finder', [
			'TeaPress\Filesystem\Finder',
			'Symfony\Component\Finder\Finder'
		]);
	}

}