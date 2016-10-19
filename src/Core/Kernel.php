<?php
namespace TeaPress\Core;

use Closure;
use TeaPress\Contracts\Signals\Hookable;
use TeaPress\Signals\Traits\Hookable as HookableTrait;
use TeaPress\Contracts\Core\Container as ContainerContract;

abstract class Kernel implements Hookable
{
	use HookableTrait;
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
	public function __construct(ContainerContract $app)
	{
		$this->app = $app;

		$this->signals = static::getSignalsHub();

		$this->initialize();
	}

	/**
	 * Initialize the kernel Called from the constructor.
	 *
	 * @return void
	 */
	protected function initialize()
	{

	}

	/**
	 * Merge the given configuration with the existing configuration.
	 *
	 * @param  string  $path
	 * @param  string  $key
	 * @return void
	 */
	protected function mergeConfigFrom($path, $key)
	{
		$config = $this->app['config']->get($key, []);

		$this->app['config']->set($key, array_merge(require $path, $config));
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
	 * Called to register the services this kernel provides.
	 *
	 * @return void
	 */
	public function registerKernel()
	{
		$this->fireRegisteringCallbacks();

		$this->register();

		$this->registerAliases();

		$this->fireRegisteredCallbacks();
	}

	/**
	 * Called to boot the kernel.
	 *
	 * @return void
	 */
	public function bootKernel()
	{
		$this->fireBootingCallbacks();

		$this->boot();

		$this->fireBootedCallbacks();
	}

	/**
	 * Called to boot the kernel.
	 *
	 * @return void
	 */
	public function runKernel()
	{
		$this->fireRunningCallbacks();

		$this->run();
	}


	/**
	 * Register the services this kernel provides.
	 *
	 * @return void
	 */
	protected function register()
	{

	}

	/**
	 * Called to register service aliases. Called after the register method.
	 *
	 * @return void
	 */
	protected function registerAliases()
	{

	}

	/**
	 * Boot the kernel. Called immediately after all kernels have been registered.
	 *
	 * @return void
	 */
	protected function boot()
	{

	}

	/**
	 * Runs the kernel. Called after all kernels have booted and wordpress is initialized.
	 *
	 * @return void
	 */
	protected function run()
	{
		//
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return [];
	}

	/**
	 * Get the events that trigger this kernel to run.
	 *
	 * @return array
	 */
	public function when()
	{
		return [];
	}

	/**
	 * Determine if the provider is deferred.
	 *
	 * @return bool
	 */
	public function isDeferred()
	{
		return $this->defer;
	}

	/**
	 * Get a list of files that should be compiled for the package.
	 *
	 * @return array
	 */
	public static function compiles()
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

	/**
	 * Register a new boot listener.
	 *
	 * @param  mixed  $callback
	 * @param  int  $priority
	 * @return bool
	 */
	public static function booting($callback, $priority = null)
	{
		return static::bindCallback('boot', $callback, $priority);
	}

	/**
	 * Register a new "booted" listener.
	 *
	 * @param  mixed  $callback
	 * @param  int  $priority
	 * @return bool
	 */
	public static function booted($callback, $priority = null)
	{
		return static::bindCallback('booted', $callback, $priority);
	}

	/**
	 * Register a new boot listener.
	 *
	 * @param  mixed  $callback
	 * @param  int  $priority
	 * @return bool
	 */
	public static function registering($callback, $priority = null)
	{
		return static::bindCallback('register', $callback, $priority);
	}

	/**
	 * Register a new running listener.
	 *
	 * @param  mixed  $callback
	 * @param  int  $priority
	 * @return bool
	 */
	public static function running($callback, $priority = null)
	{
		return static::bindCallback('run', $callback, $priority);
	}


	/**
	 * Register a new "booted" listener.
	 *
	 * @param  mixed  $callback
	 * @param  int  $priority
	 * @return bool
	 */
	public static function registered($callback, $priority = null)
	{
		return static::bindCallback('registered', $callback, $priority);
	}

	protected function fireKenelEvent($event, ...$payload)
	{
		if(empty($payload))
			$payload = [ $this, $this->app];

		return $this->emitSignal($event, ...$payload);
	}

	public function fireBootingCallbacks()
	{
		$this->fireKenelEvent('boot');
	}

	public function fireBootedCallbacks()
	{
		$this->fireKenelEvent('booted');
	}

	public function fireRegisteringCallbacks()
	{
		$this->fireKenelEvent('register');
	}

	public function fireRegisteredCallbacks()
	{
		$this->fireKenelEvent('registered');
	}

	public function fireRunningCallbacks()
	{
		$this->fireKenelEvent('run');
	}

}