<?php
namespace TeaPress\Shortcode;

use TeaPress\Core\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
	public function register()
	{
		$this->app->singleton('shortcode', function ($app){
			return new Registrar($app);
		});
	}

	public function registerAliases()
	{
		$this->app->alias('shortcode', [
			'TeaPress\Shortcode\Registrar',
			'TeaPress\Contracts\Shortcode\Registrar'
		]);
	}

}