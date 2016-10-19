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
	}

	public function registerAliases()
	{
		$this->app->alias('files', [
			'TeaPress\Filesystem\Filesystem',
			'TeaPress\Contracts\Filesystem\Filesystem',
			'Illuminate\Filesystem\Filesystem',
			'Illuminate\Contracts\Filesystem\Filesystem'
		]);
	}

}