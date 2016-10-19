<?php

namespace TeaPress\Core;

use TeaPress\Utils\Arr;
use BadMethodCallException;
use UnexpectedValueException;
use TeaPress\Signals\SignalsKernel;
use TeaPress\Contracts\Core\Application as Contract;
use TeaPress\Contracts\Core\Manifest as ManifestContract;
use TeaPress\Contracts\Core\ServiceProvider as ProviderContract;

class Application extends Container implements Contract
{

	const VERSION = '0.1.0';

	/**
	 * @var \TeaPress\Core\Application
	 */
	protected static $instance;

	/**
	 * @var string
	 */
	protected $basePath;

	/**
	 * @var string
	 */
	protected $configPath;

	/**
	 * @var bool
	 */
	protected $booted = false;

	/**
	 * @var bool
	 */
	protected $running = false;

	/**
	 * @var bool
	 */
	protected $bootstraped = false;

	/**
	 * @var array
	 */
	protected $kernels = [];

	/**
	 * @var array
	 */
	protected $bootedKernels = [];

	/**
	 * @var array
	 */
	protected $registeredKernels = [];

	/**
	 * @var array
	 */
	protected $runningKernels = [];

	/**
	 * @var array
	 */
	// protected $serviceAliases = [];

	/**
	 * Creates the application instance.
	 *
	 * @param  string|null  $basePath
	 *
	 * @return static
	 */
	public function __construct($basePath = null)
	{
		if($basePath){
			$this->setBasePath($basePath);
		}

		$this->registerBaseBindings();
	}


	/**
	* Register the this instance as a service.
	*
	* @return void
	*/
	protected function registerBaseBindings()
	{
		static::setInstance($this);

		$this->instance('app', $this);

		$this->alias('app', [
			'TeaPress\Core\Application',
			'TeaPress\Contracts\Core\Container',
			'TeaPress\Contracts\Core\Application',
			'Illuminate\Contracts\Container\Container',
		]);

	}


/* Path methods */

	/**
	 * Bind all of the application paths in the container.
	 *
	 * @return void
	 */
	protected function bindPathsInContainer()
	{
		foreach (['base', 'config', 'lang', 'assets', 'storage'] as $path) {
			$this->instance('path.'.$path, $this->{$path.'Path'}());
		}
	}

	/**
	* Set the current application's base path.
	*
	* @return void
	*/
	public function setBasePath($path)
	{
		$this->basePath = $basePath;
		$this->bindPathsInContainer();
	}

	/**
	* Get the current application's base path.
	*
	* @return void
	*/
	public function basePath()
	{
		return $this->basePath;
	}


	/**
	 * Get the path to the application "app" directory.
	 *
	 * @return string
	 */
	public function path(...$fragments)
	{
		return join_paths($this->basePath, ...$fragments);
	}

	/**
	 * Get the base path of the Laravel installation.
	 *
	 * @return string
	 */
	public function basePath()
	{
		return $this->basePath;
	}

	/**
	 * Get the path to the application configuration files.
	 *
	 * @return string
	 */
	public function configPath()
	{
		return $this->path('config');
	}

	/**
	 * Get the path to the language files.
	 *
	 * @return string
	 */
	public function langPath()
	{
		return $this->path('resources', 'lang');
	}

	/**
	 * Get the path to the public / web directory.
	 *
	 * @return string
	 */
	public function assetsPath()
	{
		return $this->path('resources', 'assets');
	}

	/**
	 * Get the path to the storage directory.
	 *
	 * @return string
	 */
	public function storagePath()
	{
		return $this->path('storage');
	}

/********* Path methods ********/

	/**
	* Bootstrap the application.
	*
	* @param  array  $bootstrapers
	*
	* @return static
	*/
	public function bootstrapWith(array $bootstrapers)
	{
		$this->bootstraped = true;

		foreach ($bootstrapers as $bootstraper) {
			$this->signals->fire('bootstrapping: '.$bootstrapper, [$this]);

			$this->make($bootstrapper)->bootstrap($this);

			$this->signals->fire('bootstrapped: '.$bootstrapper, [$this]);
		}

		return $this;
	}

	public function bootstrapOn($event, $priority = -9999)
	{
		if($this->signals->emitted($event))
			$this->bootstrap();
		else
			$this->signals->bind($event, [$this, 'bootstrap'], $priority);
	}

	/**
	 * Register all of the booted kernels.
	 *
	 * @return void
	 */
	public function registerBootedKernels()
	{
		$manifestKey = $this->getCachedKernelsKey();
		(new KernelLoader($this, $manifestKey))->load( array_keys($this->bootedKernels) );
	}

	/**
	* Register a kernel with the application.
	*
	* @param  string|\TeaPress\Core\Kernel|array  $kernel
	* @param  array  $options
	* @param  bool   $force
	*
	* @return \TeaPress\Core\Kernel|array
	*/
	public function register($kernel, array $options = [], $force = false)
	{
		if(is_array($kernel)){
			$kernels = [];
			foreach ($kernel as $k) {
				$kernels[] = $this->register($k, $options, $force);
				$options = [];
			}

			return $kernels;
		}

		$instance = $this->kernel($kernel);

		if($force || !$this->kernelIsRegistered($kernel)){

			if(!$this->kernelIsBooted($kernel)){
				$this->bootKernel($kernel);
			}

			$instance->register();

			foreach ($options as $key => $value) {
				$this[$key] = $value;
			}

			$this->markAsRegistered($kernel);
		}

		if($this->isRunning()){
			$this->runKernel($kernel);
		}

		return $instance;
	}

	/**
	* Get the kernel instance.
	* If create is true, a new instance of the kernel will be created if not available.
	*
	* @param string $kernel 	The kernel class name.
	*
	* @return \TeaPress\Core\Kernel
	*/
	public function kernel($kernel)
	{
		$name = is_string($kernel) ? $kernel : get_class($kernel);

		if( !isset($this->kernels[$name]) )
			$this->kernels[$name] = is_string($kernel) ? $this->createKernel($name) : $kernel;

		return $this->kernels[$name];
	}

	/**
	* Get all kernel instances bound with the application.
	* If kernel is provided, the current instance is returned.
	* Returns null if the specified instance is not bound.
	*
	* @param null|string $kernel 	The kernel class name.
	*
	* @return array|null|\TeaPress\Core\Kernel
	*/
	public function kernels($kernel = null)
	{
		if(is_null($kernel))
			return $this->kernels;

		return isset($this->kernels[$kernel]) ? $this->kernels[$kernel] : null;
	}

	/**
	 * Determine if the given kernel has booted.
	 *
	 * @param  \TeaPress\Core\Kernel|string  $kernel
	 *
	 * @return bool
	 */
	public function kernelIsBooted($kernel)
	{
		$name = is_string($kernel) ? $kernel : get_class($kernel);

		return isset($this->bootedKernels[$name]) ? $this->bootedKernels[$name] : false;
	}


	/**
	 * Determine if the given kernel is registered.
	 *
	 * @param  \TeaPress\Core\Kernel|string  $kernel
	 *
	 * @return bool
	 */
	public function kernelIsRegistered($kernel)
	{
		$name = is_string($kernel) ? $kernel : get_class($kernel);

		return isset($this->registeredKernels[$name]) ? $this->registeredKernels[$name] : false;
	}

	/**
	 * Determine if the given kernel is running.
	 *
	 * @param  \TeaPress\Core\Kernel|string  $kernel
	 *
	 * @return bool
	 */
	public function kernelIsRunning($kernel)
	{
		$name = is_string($kernel) ? $kernel : get_class($kernel);

		return isset($this->runningKernels[$name]) ? $this->runningKernels[$name] : false;
	}


	/**
	 * Create a kernel instance
	 *
	 * @param string  $kernel
	 *
	 * @return \TeaPress\Core\Kernel
	 */
	public function createKernel($kernel)
	{
		return new $kernel($this, ($this->bound('signals') ? $this->make('signals') : null) );
	}

	/**
	 * Mark the given kernel as registered.
	 *
	 * @param  \TeaPress\Core\Kernel|string  $kernel
	 *
	 * @return void
	 */
	protected function markAsRegistered($kernel)
	{
		$name = is_string($kernel) ? $kernel : get_class($kernel);

		$this->registeredKernels[$name] = true;
	}

	/**
	 * Boot the given kernels.
	 *
	 * @param  array $kernels
	 * @param  bool  $force
	 *
	 * @return void
	 */
	public function bootKernels(array $kernels, $force = false)
	{
		foreach ($kernels as $kernel) {
			$this->bootKernel($kernel, $force);
		}
	}

	/**
	 * Boot the given kernel.
	 *
	 * @param  array|string  $kernel
	 * @param  bool  $force
	 *
	 * @return void
	 */
	public function bootKernel($kernel, $force = false)
	{
		$name = is_string($kernel) ? $kernel : get_class($kernel);

		if($force || !$this->kernelIsBooted($name)){
			$this->kernel($kernel)->boot();
		}

		$this->bootedKernels[$name] = true;

		if( $this->isRunning() )
			$this->register($kernel);

	}

	/**
	* Boot the application.
	*
	* @param  array  $kernels  Kernels to be booted together with the application.
	*
	* @return void
	*/
	public function boot(array $kernels = [])
	{
		if (!$this->booted){
			$this->fireAppCallbacks('booting');
		}

		$this->bootKernels($kernels);

		$this->booted = true;

		$this->fireAppCallbacks('booted');

	}

	/**
	* Determine if the application has booted.
	*
	* @return bool
	*/
	public function isBooted()
	{
		return $this->booted;
	}

	/**
	* Run the application. This will also run all booted kernels.
	*
	* @param  array  $kernels  Kernels to be booted together with the application.
	*
	* @return void
	*/
	public function run()
	{
		if($this->running) return;

		if(!$this->booted){
			throw new BadMethodCallException("Error Running Application. Application not booted.");
		}

		$this->fireAppCallbacks('before_running');

		$this->runKernels( array_keys( $this->registeredKernels ) );

		$this->running = true;

		$this->fireAppCallbacks('running');
	}


	/**
	 * Boot the given kernels.
	 *
	 * @param  array $kernels
	 *
	 * @return void
	 */
	protected function runKernels(array $kernels)
	{
		foreach ($kernels as $kernel) {
			$this->runKernel($kernel);
		}
	}


	/**
	 * Run the given kernel.
	 *
	 * @param  \TeaPress\Core\Kernel|string  $kernel
	 *
	 * @return bool
	 */
	protected function runKernel($kernel)
	{
		$name = is_string($kernel) ? $kernel : get_class($kernel);

		// if(!$this->kernelIsRegistered($name)){
		// 	$this->register($name);
		// }

		if($this->kernelIsRunning($name))
			return;

		$this->kernel($kernel)->run();

		return $this->runningKernels[$name] = true;
	}

	/**
	* Determine if the application is running.
	*
	* @return bool
	*/
	public function isRunning()
	{
		return $this->running;
	}


	/**
	* Register a new boot listener.
	*
	* @param  mixed  $callback
	* @param  int	$priority
	* @return void
	*/
	public function booting($callback, $priority = null)
	{
		if(!$this->isBooted())
			return $this->bindAppCallback('booting', $callback, $priority, true);

		trigger_error('Error binding application booting callback. Application already booted.');
	}

	/**
	 * Register a new "booted" listener.
	 *
	 * @param  mixed  $callback
	 * @return void
	 */
	public function booted($callback, $priority = null)
	{
		$this->bindAppCallback('booted', $callback, $priority, true);

		if ($this->isBooted())
			$this->fireAppCallbacks('booted');
	}


	/**
	* Register a new before_running listener.
	*
	* @param  mixed  $callback
	* @param  int	$priority
	*
	* @return void
	*/
	public function beforeRunning($callback, $priority = null)
	{
		if(!$this->isRunning())
			return $this->bindAppCallback('before_running', $callback, $priority, true);

		trigger_error('Error binding application "before_running" callback. Application already running.');
	}

	/**
	* Register a new "running" listener.
	*
	* @param  mixed  $callback
	* @param  int	$priority
	*
	* @return void
	*/
	public function running($callback, $priority = null)
	{
		$this->bindAppCallback('running', $callback, $priority, true);

		if ($this->isRunning())
			$this->fireAppCallbacks('running');
	}



	/**
	* Bind the given callback to the specified $event.
	*
	* @param  string						$event
	* @param  \Closure|array|string 		$callback
	* @param  int						$priority
	* @param  bool							$once
	*
	* @return void
	*/
	protected function bindAppCallback($event, $callback, $priority = null, $once = false)
	{
		$this->signals->bind([$this, $event], $priority, null, $once);
	}


	/**
	* Call the booting callbacks for the application.
	*
	* @param  array  $callbacks
	* @return void
	*/
	protected function fireAppCallbacks($event)
	{
		$this->signals->emit([$this, $event], $this);
	}

	/**
	* Alias a type to a different name.
	*
	* @param  string|null  $abstract
	* @param  string|array  $aliases
	*
	* @return void
	*/
	public function alias($abstract, $aliases)
	{
		if( !is_array($aliases) )
			return parent::alias($abstract, $aliases);

		if( !is_null($abstract) )
			$aliases = [ $abstract => $aliases ];

		foreach ($aliases as $abstract => $aliases) {
			foreach ((array) $aliases as $alias) {
				$this->alias($abstract, $alias);
			}
		}
	}

	/**
	 * Get the path to the cached services.json file.
	 *
	 * @return string
	 */
	public function getCachedKernelsKey()
	{
		return 'teapress_kernels';
	}


	/**
	 * Load and boot all of the remaining deferred sevices.
	 *
	 * @return void
	 */
	public function loadDeferredServices()
	{
		foreach ($this->deferredServices as $service => $kernel) {
			$this->loadDeferredService($service);
		}

		$this->deferredServices = [];
	}

	/**
	 * Load the kernel for a deferred service.
	 *
	 * @param  string  $service
	 * @return void
	 */
	public function loadDeferredService($service)
	{
		if (! isset($this->deferredServices[$service])) {
			return;
		}

		$kernel = $this->deferredServices[$service];

		if (!$this->kernelIsRegistered($kernel)) {
			$this->registerDeferredKernel($kernel);
		}

		unset($this->deferredServices[$service]);
	}

	/**
	 * Register a deferred provider and service.
	 *
	 * @param  string  $kernel
	 * @param  string  $service
	 * @return void
	 */
	public function registerDeferredKernel($kernel, $service = null)
	{
		if ($service){
			unset($this->deferredServices[$service]);
		}

		$this->register($kernel);
	}


	/**
	 * Get the application's deferred services.
	 *
	 * @return array
	 */
	public function getDeferredServices()
	{
		return $this->deferredServices;
	}

	/**
	 * Set the application's deferred services.
	 *
	 * @param  array  $services
	 * @return void
	 */
	public function setDeferredServices(array $services)
	{
		$this->deferredServices = $services;
	}

	/**
	 * Add an array of services to the application's deferred services.
	 *
	 * @param  array  $services
	 * @return void
	 */
	public function addDeferredServices(array $services)
	{
		$this->deferredServices = array_merge($this->deferredServices, $services);
	}

	/**
	 * Determine if the given service is a deferred service.
	 *
	 * @param  string  $service
	 * @return bool
	 */
	public function isDeferredService($service)
	{
		return isset($this->deferredServices[$service]);
	}


	/**
	 * Resolve the given type from the container.
	 *
	 * (Overriding Container::make)
	 *
	 * @param  string  $abstract
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function make($abstract, array $parameters = [])
	{
		$abstract = $this->getAlias($abstract);

		if (isset($this->deferredServices[$abstract])) {
			$this->loadDeferredService($abstract);
		}

		return parent::make($abstract, $parameters);
	}

	/**
	 * Determine if the given abstract type has been bound.
	 *
	 * (Overriding Container::bound)
	 *
	 * @param  string  $abstract
	 * @return bool
	 */
	public function bound($abstract)
	{
		return parent::bound($abstract) || isset($this->deferredServices[$abstract]);
	}
}