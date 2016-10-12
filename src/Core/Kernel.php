<?php
namespace TeaPress\Core;

use Closure;
use TeaPress\Contracts\Core\Container;
use TeaPress\Contracts\Signals\Hub as Signals;

abstract class Kernel
{

	/**
	 * @var \TeaPress\Contracts\Core\Container
	 */
	protected $app;

	/**
	 * @var \TeaPress\Contracts\Signals\Hub
	 */
	protected $signals;


	/**
	 * Creates the kernel instance.
	 *
	 * @param \TeaPress\Contracts\Core\Container $app
	 * @param \TeaPress\Contracts\Signals\Hub $signals
	 *
	 * @return void
	 */
	public function __construct(Container $app, Signals $signals = null)
	{
		$this->app = $app;
		$this->signals = $signals;
	}

	/**
	 * Called to register the services this kernel provides.
	 *
	 * @return void
	 */
	public function register()
	{

	}

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