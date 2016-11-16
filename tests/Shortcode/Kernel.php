<?php
namespace TeaPress\Tests\Shortcode;

use TeaPress\Shortcode\Registrar;
use TeaPress\Tests\Base\TestKernel;

class Kernel extends TestKernel
{
	public function register()
	{
		$this->app->singleton('shortcode', function ($app){
			return new Registrar($app);
		});

		$this->aliasServices( $this->serviceAliases() );
	}


	protected function serviceAliases()
	{
		return [
			'shortcode' => [
				'TeaPress\Shortcode\Registrar',
				'TeaPress\Contracts\Shortcode\Registrar'
			]
		];
	}
}