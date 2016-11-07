<?php

namespace TeaPress\Core;

use TeaPress\Utils\Str;
use TeaPress\Utils\Arr;
use BadMethodCallException;
use InvalidArgumentException;
use UnexpectedValueException;
use TeaPress\Contracts\Core\Application as Contract;
use TeaPress\Contracts\Core\Kernel as KernelContract;
use TeaPress\Core\Exception\ApplicationNotReadyException;


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
	protected $baseUrl;

	/**
	 * @var string
	 */
	protected $basePath;

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
	protected $ready = null;

	/**
	 * @var array
	 */
	protected $explicitPaths = [];

	/**
	 * @var bool
	 */
	protected $hasBeenBootstrapped = false;

	/**
	 * @var array
	 */
	protected $bootstrapped = [];

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
	protected $deferredServices = [];

	/**
	 * Creates the application instance.
	 *
	 * @return static
	 */
	public function __construct()
	{
		$this->registerBaseBindings();

		$this->registerCoreContainerAliases();

		$this->bindDefaultPathsInContainer();
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
	}


/* Path methods */

	/**
	* Bind the default application paths in the container.
	*
	* @return void
	*/
	protected function bindDefaultPathsInContainer()
	{
		$this->bindPathsInContainer(['base', 'config', 'lang', 'assets', 'storage']);
	}


	/**
	* Bind application path(s) in the container.
	*
	* @param array|string $keys
	*
	* @return void
	*/
	protected function bindPathsInContainer($keys)
	{
		foreach ((array) $keys as $key) {
			$this->bindIf("path.{$key}", function($app) use ($key){
				return $app->getPath($key, null);
			});
		}
	}


	/**
	* Set the current application's base URL.
	*
	* @param string $url
	* @return void
	*/
	public function setBaseUrl($url)
	{
		$this->baseUrl = rtrim($url, '/');
	}

	/**
	 * Get the application's base URL.
	 *
	 * @return string
	 */
	public function baseUrl()
	{
		return $this->baseUrl ? $this->baseUrl : rtrim(home_url(), '/');
	}


	/**
	* Set the current application's base path.
	*
	* @param string $path
	* @return void
	*/
	public function setBasePath($path)
	{
		$this->basePath = $path;
	}


	/**
	* Get explicitly path(s).
	*
	* @param string|null $key
	* @param mixed $default
	*
	* @return string|array|mixed
	*/
	protected function explicitPaths($key=null, $default = null)
	{
		return Arr::get($this->explicitPaths, $key, $default);
	}

	/**
	* Explicitly set a path's value.
	*
	* @param string $key
	* @param mixed $path
	*
	* @return void
	*/
	protected function setExplicitPath($key, $path)
	{
		Arr::set($this->explicitPaths, $key, $path);
	}

	/**
	 * Set the value  of a named path.
	 *
	 * @param string $key
	 * @param mixed $path
	 *
	 * @return void
	 */
	public function usePath($key, $path)
	{
		$setter = Str::camel("use{$key}Path");

		if( method_exists($this, $setter) ){
			$this->setter($path);
		}
		else{
			$this->setExplicitPath($key, $path);
		}

		$this->bindPathsInContainer($key);
	}

	/**
	 * Add a path to a group of named paths.
	 *
	 * @param string $key
	 * @param mixed $path
	 * @param string $name
	 *
	 * @throws \InvalidArgumentException
	 *
	 * @return void
	 */
	public function addPath($key, $path, $name = null)
	{
		$setter = Str::camel("add{$key}Path");

		if( method_exists($this, $setter) ){
			return $this->setter($path, $name);
		}

		if(!is_array( $this->getPath($key) )){
			throw new InvalidArgumentException("Error adding path. Path '{$key}' is not an array by default.");
		}

		$paths = $this->explicitPaths($key, []);

		if( is_null($name) )
			Arr::pushUnique($paths, null, $path );
		else
			Arr::set($paths, $name, $path);

		$this->usePath($key, $paths);
	}

	/**
	 * Get the value  of a named path.
	 *
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return string|mixed
	 */
	public function getPath($key, $default = null)
	{
		$getter = Str::camel("{$key}Path");

		return method_exists($this, $getter)
				? $this->$getter() : $this->explicitPaths($key, $default);
	}


	/**
	 * Get the full path of the given fragments relative to the base path.
	 *
	 * @param  string ...$fragments
	 *
	 * @return string
	 */
	public function pathTo(...$fragments)
	{
		return join_paths($this->basePath(), ...$fragments);
	}

	/**
	 * Get the application's base path of installation.
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
	 * @return string|array
	 */
	public function configPath()
	{
		return (array) $this->explicitPaths('config', $this->pathTo('config') );
	}

	/**
	 * Get the path to the language files.
	 *
	 * @return string|array
	 */
	public function langPath()
	{
		return $this->explicitPaths('lang', $this->pathTo('resources/lang') );
	}

	/**
	 * Get the path to the application's asset files.
	 *
	 * @return string|array
	 */
	public function assetsPath()
	{
		return $this->explicitPaths('assets', $this->pathTo('resources/assets') );
	}

	/**
	 * Get the path to the storage directory.
	 *
	 * @return string
	 */
	public function storagePath()
	{
		return $this->explicitPaths('storage', $this->pathTo('storage') );
	}

	/**
	 * Determine if the application is in debug mode.
	 *
	 * @return string
	 */
	public function isDebug()
	{
		$default = defined('WP_DEBUG') ? WP_DEBUG : false;
		return $this->bound('config') ? $this->make('config')->get('app.debug', $default) : $default;
	}


	/**
	* Determine if we are running in the console.
	*
	* @return bool
	*/
	public function isCli()
	{
		return php_sapi_name() == 'cli';
	}

	/**
	* Determine if we are running unit tests.
	*
	* @return bool
	*/
	public function isUnitTesting()
	{
		return $this->isCli() && defined('DOING_UNIT_TESTS') ? (bool) DOING_UNIT_TESTS : false;
	}

/********* Path methods ********/


	/**
	* Run the given array of bootstrap classes.
	*
	* @param  array  $bootstrappers
	* @param  bool  $force
	* @param  bool  $silent
	*
	* @return void
	*/
	public function bootstrapWith($bootstrappers, $force = false, $silent = false)
	{

		if(!$silent) $this->hasBeenBootstrapped = true;

		$fire = !$silent && $this->bound('signals');

		$ready = $this->isReady();

		foreach ((array) $bootstrappers as $bootstrapper) {

			if( !$force && $this->bootstrapped( $bootstrapper ) ){
				continue;
			}

			if( $ready ){
				trigger_error("It's a little bit too late to be bootstrapping the application (with: {$bootstrapper}).");
			}

			if($fire) $this->fireAppCallbacks("bootstrapping.{$bootstrapper}");

			$this->make($bootstrapper)->bootstrap($this);

			$this->bootstrapped[$bootstrapper] = true;

			if($fire) $this->fireAppCallbacks("bootstrapped.{$bootstrapper}");

		}
	}

	/**
	 * Determine if the given bootstrap class has been executed.
	 * Returns all executed bootstrap classes if none is specified.
	 *
	 * @param  string|null  $bootstrapper
	 *
	 * @return bool|array
	 */
	public function bootstrapped($bootstrapper = null)
	{
		if(is_null($bootstrapper))
			return $this->bootstrapped;

		return isset($this->bootstrapped[$bootstrapper]);
	}


	/**
	 * Determine if the application has been bootstrapped before.
	 *
	 * @return bool
	 */
	public function hasBeenBootstrapped()
	{
		return $this->hasBeenBootstrapped;
	}

	/**
	* Register a callback to run before a bootstrapper.
	*
	* @param  string  $bootstrapper
	* @param  mixed  $callback
	* @param  int  $priority
	*
	* @return void
	*/
	public function beforeBootstrapping($bootstrapper, $callback, $priority = null)
	{
		$this->bindAppCallback("bootstrapping.{$bootstrapper}", $callback, $priority );
	}

	/**
	* Register a callback to run after a bootstrapper.
	*
	* @param  string  $bootstrapper
	* @param  mixed  $callback
	* @param  int  $priority
	*
	* @return void
	*/
	public function afterBootstrapping($bootstrapper, $callback, $priority = null)
	{
		$this->bindAppCallback("bootstrapped.{$bootstrapper}", $callback, $priority );
	}

	/**
	 * Register all of the booted kernels.
	 *
	 * @return void
	 */
	public function registerBootedKernels()
	{
		$this->createKernelLoader()->load( array_keys($this->bootedKernels) );
	}


	/**
	 * Create a kernel loader instance.
	 *
	 * @return \TeaPress\Core\KernelLoader
	 */
	protected function createKernelLoader()
	{
		return new KernelLoader($this, $this->make('signals'), $this->getCachedKernelsKey());
	}

	/**
	* Register a kernel with the application.
	*
	* @param  string|\TeaPress\Contracts\Core\Kernel  $kernel
	* @param  array  $options
	* @param  bool   $force
	*
	* @return \TeaPress\Contracts\Core\Kernel
	*/
	public function register($kernel, array $options = [], $force = false)
	{
		$name = is_string($kernel) ? $kernel : get_class($kernel);

		$kernel = $this->kernel($kernel);

		if(!$this->kernelIsBooted($name)){
			$this->bootKernel($kernel);
		}

		if($force || !$this->kernelIsRegistered($name)){

			$this->fireKernelCallbacks($name, 'registering', $kernel);

			$kernel->register();

			foreach ($options as $key => $value) {
				$this[$key] = $value;
			}

			$this->registeredKernels[$name] = true;

			$this->fireKernelCallbacks($name, 'registered', $kernel);

		}

		if($this->isRunning()){
			$this->runKernel($kernel);
		}

		return $kernel;
	}

	/**
	* Get the kernel instance.
	* If create is true, a new instance of the kernel will be created if not available.
	*
	* @param string $kernel 	The kernel class name.
	*
	* @return \TeaPress\Contracts\Core\Kernel
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
	* @return array|null|\TeaPress\Contracts\Core\Kernel
	*/
	public function kernels($kernel = null)
	{
		if(is_null($kernel))
			return $this->kernels;

		$kernel = is_string($kernel) ? $kernel : get_class($kernel);

		return isset($this->kernels[$kernel]) ? $this->kernels[$kernel] : null;
	}

	/**
	 * Determine if the given kernel has booted.
	 *
	 * @param  \TeaPress\Contracts\Core\Kernel|string  $kernel
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
	 * @param  \TeaPress\Contracts\Core\Kernel|string  $kernel
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
	 * @param  \TeaPress\Contracts\Core\Kernel|string  $kernel
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
	 * @return \TeaPress\Contracts\Core\Kernel
	 */
	public function createKernel($kernel)
	{
		$args = [
			'app' => $this,
			'signals' => $this->bound('signals') ? $this->make('signals') : null
		];

		return $this->make($kernel, $args);
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
			$this->bootKernel( $this->kernel($kernel), $force);
		}
	}

	/**
	 * Boot the given kernel.
	 *
	 * @param  \TeaPress\Contracts\Core\Kernel  $kernel
	 * @param  bool  $force
	 *
	 * @return void
	 */
	protected function bootKernel(KernelContract $kernel, $force = false)
	{
		$name = get_class($kernel);

		if(!$force && $this->kernelIsBooted($name))
			return;

		$this->fireKernelCallbacks($name, 'booting', $kernel);

		$kernel->boot();

		if( method_exists($kernel, 'registerAliases') )
			$kernel->registerAliases();

		$this->bootedKernels[$name] = true;

		$this->fireKernelCallbacks($name, 'booted', $kernel);
	}

	/**
	* Boot the application.
	*
	* @param  array  $kernels  Kernels to be booted together with the application.
	*
	* @return void
	*/
	public function boot(array $kernels)
	{
		if($this->running) return;

		$this->fireAppCallbacks('booting');

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
	* Run the application. This will run all registered kernels.
	*
	* @throws \TeaPress\Core\Exception\ApplicationNotReadyException
	*
	* @return void
	*/
	public function run()
	{
		if($this->running) return;

		if(!$this->booted){
			throw new ApplicationNotReadyException("Error Running Application. Application has not yet been booted.");
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
			$this->runKernel($this->kernel($kernel));
		}
	}


	/**
	 * Run the given kernel.
	 *
	 * @param  \TeaPress\Contracts\Core\Kernel  $kernel
	 *
	 * @return void
	 */
	protected function runKernel(KernelContract $kernel)
	{
		$name = get_class($kernel);

		if($this->kernelIsRunning($name))
			return;

		$this->fireKernelCallbacks($name, 'before_running', $kernel);

		$kernel->run();

		$this->runningKernels[$name] = true;

		$this->fireKernelCallbacks($name, 'running', $kernel);
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
	* @param  mixed  		$callback
	* @param  int  			$priority
	*
	* @return bool
	*/
	public function booting($callback, $priority = null)
	{
		$this->bindAppCallback('booting', $callback, $priority);

		return !$this->isBooted();
	}

	/**
	* Register a new "booted" listener.
	*
	* @param  mixed  $callback
	* @param  int	$priority
	*
	* @return bool
	*/
	public function booted($callback, $priority = null)
	{
		$this->bindAppCallback('booted', $callback, $priority);

		return !$this->isBooted();
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
		$this->bindAppCallback('before_running', $callback, $priority);

		return !$this->isRunning();
	}

	/**
	* Register a new "running" listener.
	*
	* @param  mixed  $callback
	* @param  int	$priority
	*
	* @return bool
	*/
	public function afterRunning($callback, $priority = null)
	{
		$this->bindAppCallback('running', $callback, $priority);

		return !$this->isRunning();
	}


	/**
	* Register a callback to run before booting kernel.
	*
	* @param  string 	 	$kernel
	* @param  mixed  		$callback
	* @param  int  			$priority
	*
	* @return bool
	*/
	public function beforeBootingKernel($kernel, $callback, $priority = null)
	{
		$this->bindKernelCallback($kernel, 'booting', $callback, $priority);

		return !$this->kernelIsBooted($kernel);
	}


	/**
	* Register a callback to run after booting kernel.
	*
	* @param  string 	 	$kernel
	* @param  mixed  		$callback
	* @param  int  			$priority
	*
	* @return bool
	*/
	public function afterBootingKernel($kernel, $callback, $priority = null)
	{
		$this->bindKernelCallback($kernel, 'booted', $callback, $priority);

		return !$this->kernelIsBooted($kernel);
	}


	/**
	* Register a callback to run before registering a kernel.
	*
	* @param  string 	 	$kernel
	* @param  mixed  		$callback
	* @param  int  			$priority
	*
	* @return bool
	*/
	public function beforeRegisteringKernel($kernel, $callback, $priority = null)
	{
		$this->bindKernelCallback($kernel, 'registering', $callback, $priority);

		return !$this->kernelIsRegistered($kernel);
	}


	/**
	* Register a callback to run after registering a kernel.
	*
	* @param  string 	 	$kernel
	* @param  mixed  		$callback
	* @param  int  			$priority
	*
	* @return bool
	*/
	public function afterRegisteringKernel($kernel, $callback, $priority = null)
	{
		$this->bindKernelCallback($kernel, 'registered', $callback, $priority);

		return !$this->kernelIsRegistered($kernel);
	}


	/**
	* Register a callback to run before running a kernel.
	*
	* @param  string 	 	$kernel
	* @param  mixed  		$callback
	* @param  int  			$priority
	*
	* @return bool
	*/
	public function beforeRunningKernel($kernel, $callback, $priority = null)
	{
		$this->bindKernelCallback($kernel, 'before_running', $callback, $priority);

		return !$this->kernelIsRunning($kernel);
	}

	/**
	* Register a callback to execute after a kernel is run.
	*
	* @param  string 	 	$kernel
	* @param  mixed  		$callback
	* @param  int  			$priority
	*
	* @return bool
	*/
	public function afterRunningKernel($kernel, $callback, $priority = null)
	{
		$this->bindKernelCallback($kernel, 'running', $callback, $priority);

		return !$this->kernelIsRunning($kernel);
	}


	/**
	* Bind the given callback to the specified $event.
	*
	* @param  string					$event
	* @param  \Closure|array|string 	$callback
	* @param  int						$priority
	*
	* @return bool
	*/
	protected function bindAppCallback($event, $callback, $priority = null)
	{
		if(!$this->bound('signals'))
			return;

		return $this->signals->bind($this->appEventTag($event), $callback, $priority);
	}

	/**
	* Bind the given callback to the specified kernel event.
	*
	* @param  string						$kernel
	* @param  string						$event
	* @param  \Closure|array|string 		$callback
	* @param  int							$priority
	*
	* @return bool
	*/
	protected function bindKernelCallback($kernel, $event, $callback, $priority = null)
	{
		if(!$this->bound('signals'))
			return;

		$kernel = is_string($kernel) ? $kernel : get_class($kernel);

		return $this->signals->bind([$kernel, $event], $callback, $priority);
	}


	/**
	* Call the callbacks for an application event.
	*
	* @param  string  $event
	* @param  mixed  ...$payload
	*
	* @return mixed
	*/
	protected function fireAppCallbacks($event, ...$payload)
	{
		if(!$this->bound('signals'))
			return;

		if(!in_array($this, $payload))
			$payload[] = $this;

		return $this->signals->fire($this->appEventTag($event), $payload);
	}

	/**
	* Call the callbacks for a kernel's event.
	*
	* @param  string  $kernel
	* @param  string  $event
	* @param  mixed  ...$payload
	*
	* @return mixed
	*/
	protected function fireKernelCallbacks($kernel, $event, ...$payload)
	{
		if(!$this->bound('signals'))
			return;

		$kernel = is_string($kernel) ? $kernel : get_class($kernel);

		if(!in_array($this, $payload)){
			$payload[] = $this;
		}

		return $this->signals->fire([$kernel, $event], $payload);
	}

	/**
	* Get the complete application's event tag.
	*
	* @param string $vent
	*
	* @return string|array
	*/
	public function appEventTag($event)
	{
		return [ $this->getSignalsNamespace(), $event ];
	}

	/**
	* Get the namespace for application events.
	*
	* @return string
	*/
	public function getSignalsNamespace()
	{
		return get_class($this);
	}

	/**
	* Determine if the application has fully bootstrapped, running and ready.
	*
	* @return bool
	*/
	public function isReady()
	{
		return !is_null($this->ready) ? $this->ready : ( $this->isRunning() && $this->signals->fired('init') );
	}

	/**
	* Mark the application as fully bootstrapped, running and ready.
	*
	* @param bool $ready
	*
	* @return void
	*/
	public function setAppReady()
	{
		if($this->ready) return;

		$this->ready = true;
		$this->fireAppCallbacks('ready');
	}


	/**
	* Register a new "ready" listener.
	*
	* @param  mixed  $callback
	* @param  int	$priority
	*
	* @return void
	*/
	public function whenReady($callback, $priority = null)
	{
		$this->bindAppCallback('ready', $callback, $priority);

		if ($this->ready)
			$this->fireAppCallbacks('ready');
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

	/**
	 * Allow fluent and dynamic get/set calls of paths.
	 *
     * @param string $method
     * @param array  $args
     *
     * @throws \BadMethodCallException
     *
     * @return mixed
	 */
	public function __call($method, $args)
	{
		if(substr($method, -4) === 'Path'){

			$key = substr($method, 0, -4);

			if(strpos($key, 'use') === 0){
				$key = substr($key, 3);
				$method = 'usePath';
			}
			elseif(strpos($key, 'add') === 0){
				$key = substr($key, 3);
				$method = 'addPath';
			}
			else{
				$method = 'getPath';
			}

			return $this->{$method}( Str::snake($key), ...$args );
		}

		throw new BadMethodCallException("Call to undefined method '{$method}' not in application.");
	}


	/**
	 * Register the core class aliases in the container.
	 *
	 * @return void
	 */
	protected function registerCoreContainerAliases()
	{
		$aliases = [
			'app' => [
				'TeaPress\Core\Application',
				'TeaPress\Contracts\Core\Container',
				'TeaPress\Contracts\Core\Application',
				'Illuminate\Contracts\Container\Container',
			],
			'signals' => [
				'events',
				'TeaPress\Signals\Hub',
				'TeaPress\Contracts\Signals\Hub',
				'Illuminate\Contracts\Events\Dispatcher'
			],
			'config' => [
				'TeaPress\Config\Manager',
				'TeaPress\Contracts\Config\Manager'
			],
			'request' => [
				'TeaPress\Http\Request',
				'TeaPress\Contracts\Http\Request'
			],
		];

		foreach ($aliases as $abstract => $alias) {
			$this->alias($abstract, $alias);
		}
	}

}