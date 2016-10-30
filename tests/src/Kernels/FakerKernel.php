<?php
namespace TeaPress\Tests\Kernels;

use TeaPress\Tests\Base\TestKernel;
use Faker\Factory;
/**
*
*/
class FakerKernel extends TestKernel
{

	public function register()
	{
		$this->app->singleton(['faker' => 'Faker\Generator'], function($app){
			return Factory::create();
		});
	}
}