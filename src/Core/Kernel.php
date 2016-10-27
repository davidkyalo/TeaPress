<?php
namespace TeaPress\Core;

use Closure;
use TeaPresss\Utils\Arr;
use TeaPress\Contracts\Signals\Hub as Signals;
use TeaPress\Contracts\Core\Kernel as Contract;
use TeaPress\Contracts\Core\Container as ContainerContract;

abstract class Kernel implements Contract
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
	 * @var bool
	 */
	protected $defer = false;


	/**
	 * Creates the kernel instance.
	 *
	 * @param \TeaPress\Contracts\Core\Container $app
	 * @param \TeaPress\Contracts\Signals\Hub $signals
	 *
	 * @return void
	 */
	public function __construct(ContainerContract $app, Signals $signals = null)
	{
		$name = get_class($this);

		if($app->kernels($name))
			trigger_error("Multiple instances of kernel {$name} created.");

		$this->app = $app;
		$this->signals = $signals;
		$this->initialize();
	}

	/**
	 * Initialize the kernel. This is called from the constructor.
	 *
	 * @return void
	 */
	protected function initialize()
	{

	}

	/**
	 * Boot the kernel. Called when booting the application.
	 * This method will always be called regardless of whether the kernel is differed or not.
	 *
	 * This is a good point to register event listeners for events fired early in the application.
	 * For example some services might fire an event when started or apply filters for a config value they need.
	 *
	 * @return void
	 */
	public function boot()
	{

	}

	/**
	 * Called to register aliases for the services the kernels provides.
	 * Called after the kernel is booted.
	 *
	 * @return void
	 */
	public function registerAliases()
	{

	}

	/**
	 * Register the services this kernel provides. At this point,
	 * all kernels have booted but not all have registered so please refrain from directly using other services.
	 * You can use them inside your service builder closure functions though.
	 *
	 * @return void
	 */
	public function register()
	{

	}

	/**
	 * Runs the kernel. Called after all kernels have been registered.
	 * At this point you are free to use any services.
	 *
	 * @return void
	 */
	public function run()
	{
		//
	}

	/**
	 * Get the services provided by the kernel.
	 *
	 * @return array
	 */
	public function provides()
	{
		return [];
	}

	/**
	 * Get the events that trigger this kernel to register and run.
	 * This is only necessary if the kernel is differed.
	 *
	 * @return array
	 */
	public function when()
	{
		return [];
	}

	/**
	 * Determine if the kernel is deferred.
	 *
	 * @return bool
	 */
	public function isDeferred()
	{
		return $this->defer;
	}

	/**
	 * Set the signals hub instance.
	 *
	 * @param \TeaPress\Contracts\Signals\Hub $signals
	 *
	 * @return void
	 */
	public function setSignals(Signals $signals)
	{
		$this->signals = $signals;
	}

	/**
	 * Merge the given configuration with the existing configuration.
	 *
	 * @param  string  	$path
	 * @param  string  	$key
	 *
	 * @return void
	 */
	protected function mergeConfigFrom($path, $key)
	{
		$config = $this->app['config'];

		$merged = array_merge(
					$config->getLoader()->loadPath($path),
					(array) $config->get($key, [])
				);

		$config->set($key, $merged);
	}

	/**
	 * Register a view file namespace.
	 *
	 * @param  string  $path
	 * @param  string  $namespace
	 * @return void
	 */
	protected function loadViewsFrom($path, $namespace)
	{
		if (is_dir($appPath = $this->app->basePath().'/resources/views/vendor/'.$namespace)) {
			$this->app['view']->addNamespace($namespace, $appPath);
		}

		$this->app['view']->addNamespace($namespace, $path);
	}

	/**
	 * Register a translation file namespace.
	 *
	 * @param  string  $path
	 * @param  string  $namespace
	 * @return void
	 */
	protected function loadTranslationsFrom($path, $namespace)
	{
		$this->app['translator']->addNamespace($namespace, $path);
	}

	/**
	 * Register a callback to be run before booting the kernel.
	 *
	 * @param  mixed  $callback
	 * @param  int  $priority
	 *
	 * @return bool
	 */
	public function booting($callback, $priority = null)
	{
		return $this->app->beforeBootingKernel( get_class($this), $callback, $priority);
	}

	/**
	 * Register a callback to be run after booting the kernel.
	 *
	 * @param  mixed  $callback
	 * @param  int  $priority
	 *
	 * @return bool
	 */
	public function booted($callback, $priority = null)
	{
		return $this->app->afterBootingKernel( get_class($this), $callback, $priority);
	}

	/**
	 * Register a new 'registering' listener.
	 *
	 * @param  mixed  $callback
	 * @param  int  $priority
	 *
	 * @return bool
	 */
	public function registering($callback, $priority = null)
	{
		return $this->app->beforeRegisteringKernel( get_class($this), $callback, $priority);
	}

	/**
	 * Register a new 'registered' listener.
	 *
	 * @param  mixed  $callback
	 * @param  int  $priority
	 *
	 * @return bool
	 */
	public function registered($callback, $priority = null)
	{
		return $this->app->afterRegisteringKernel( get_class($this), $callback, $priority);
	}

	/**
	 * Register a new 'before_running' listener.
	 *
	 * @param  mixed  $callback
	 * @param  int  $priority
	 *
	 * @return bool
	 */
	public function beforeRunning($callback, $priority = null)
	{
		return $this->app->beforeRunningKernel( get_class($this), $callback, $priority);
	}

	/**
	 * Register a new running listener.
	 *
	 * @param  mixed  $callback
	 * @param  int  $priority
	 *
	 * @return bool
	 */
	public function running($callback, $priority = null)
	{
		return $this->app->afterRunningKernel(get_class($this), $callback, $priority);
	}


	/**
	 * Clone method.
	 *
	 * @return void
	 */
	private function __clone()
	{
		//
	}
}