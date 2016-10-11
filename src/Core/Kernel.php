<?php
namespace TeaPress\Core;

use Closure;
use TeaPress\Contracts\Core\Container;

abstract class Kernel
{

	/**
	 * @var \TeaPress\Contracts\Core\Container
	 */
	protected $app;


	protected $run_priority = 10;

	/**
	 * Called to register the services this kernel provides.
	 *
	 * @return void
	 */
	public function __construct(Container $app)
	{
		$this->app = $app;
	}

	/**
	 * Called to register the services this kernel provides.
	 *
	 * @return void
	 */
	abstract public function register();

	/**
	 * Boot/Initialize the kernel. Called immediately after all kernels have been registered.
	 *
	 * @return void
	 */
	public function boot()
	{

	}

	/**
	 * Runs the kernel. Called after all kernels have booted and wordpress is initialized.
	 *
	 * @return void
	 */
	public function run()
	{
		//
	}

	/**
	 * Gets an array of deferred services provided by the kernel.
	 *
	 * @return array
	 */
	public function deferred()
	{
		return [];
	}


	/**
	 * Merge the given configuration with the existing configuration.
	 *
	 * @param  string  $path
	 * @param  string  $key
	 *
	 * @return void
	 */
	protected function mergeConfigFrom($path, $key, $recursive = false)
	{
		$config = $this->app['config']->get($key, []);

		$this->app['config']->set($key, array_merge(require $path, $config));
	}

}