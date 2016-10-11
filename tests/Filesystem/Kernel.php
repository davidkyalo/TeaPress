<?php
namespace TeaPress\Tests\Filesystem;

use TeaPress\Filesystem\Filesystem;
use TeaPress\Tests\Base\TestKernel;
use TeaPress\Contracts\Filesystem\Filesystem as FilesystemContract;

class Kernel extends TestKernel
{

	protected function serviceAliases()
	{
		return [
			'files' => [
				'TeaPress\Filesystem\Filesystem',
				'TeaPress\Contracts\Filesystem\Filesystem',
				'Illuminate\Contracts\Filesystem\Filesystem'
			]
		];
	}

	public function register()
	{
		$this->app->instance('files', new Filesystem);
		$this->aliasServices($this->serviceAliases());
	}

	public function boot()
	{

	}
}