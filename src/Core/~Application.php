<?php

namespace TeaPress\Core;

use TeaPress\Utils\Arr;
use BadMethodCallException;
use TeaPress\Contracts\Core\ServiceProvider as ProviderContract;
use TeaPress\Contracts\Core\Application as Contract;

class Application extends Container implements Contract
{

	const VERSION = '0.1.0';

	/**
	 * @var \TeaPress\Core\Application
	 */
	protected static $instance;

	/**
	 * Indicates if the application has "booted".
	 *
	 * @var bool
	 */
	protected $booted = false;

	/**
	 * @var string
	 */
	protected $environment;


	/**
	 * @var bool
	 */
	protected $debug;


	/**
	 * @var string
	 */
	protected $basePath;

	/**
	 * All of the registered service providers.
	 *
	 * @var array
	 */
	protected $serviceProviders = [];

	/**
	 * The names of the loaded service providers.
	 *
	 * @var array
	 */
	protected $loadedProviders = [];

	/**
	 * The deferred services and their providers.
	 *
	 * @var array
	 */
	protected $deferredServices = [];

	/**
	 * @var array
	 */
	protected $bootingCallbacks = [];

	/**
	 * @var array
	 */
	protected $bootedCallbacks = [];


	/**
	 * Constructs the application and ensures it's correctly setup.
	 */
	public function __construct()
	{
		if(!is_null(static::$instance))
			trigger_error("Another instance of ".get_called_class()." already exists.");

		$this->registerApplicationService();
		$this->registerBaseProviders();
	}

	/**
	 * Register the this instance as a service.
	 *
	 * @return void
	 */
	protected function registerApplicationService()
	{

		$this->instance('app', $this);
		$aliases = [
			'app' => [
				ltrim(self::class, '\\'),
				ltrim(static::class, '\\'),
				'TeaPress\Core\Container',
				'Illuminate\Container\Container',
				'TeaPress\Contracts\Core\Container',
				'TeaPress\Contracts\Core\Application',
				'Illuminate\Contracts\Container\Container',
				'Illuminate\Contracts\Foundation\Application'
			]
		];

		foreach ($aliases as $key => $aliases)
		{
			foreach ($aliases as $alias)
			{
				$this->alias($key, $alias);
			}
		}
	}


	/**
	 * Register the base providers.
	 *
	 * @return void
	 */
	protected function registerBaseProviders()
	{
		$this->instance('class_alias', )
	}

	/**
	 * Add provider classes to be registered.
	 *
	 * @param  array|string $providers
	 * @return void
	 */
	public function loadProviders($providers)
	{
		$providers = is_array($providers) ? $providers : func_get_args();
		foreach ($providers as $provider) {
			$this->setupProvider($provider);
		}
	}

	/**
	 * Load and register a service provider with the application.
	 *
	 * @param  \TeaPress\Contracts\Core\ServiceProvider|string  		$provider
	 *
	 * @return void
	 */
	protected function setupProvider($provider)
	{

		if (is_string($provider))
			$provider = $this->resolveProviderClass($provider);

		if($provider->isDeferred()){

			$this->addProviderDeferredServices($provider);

			// if($hooks = (array) $provider->when() && !empty($hooks)){

			// 	$fired = Arr::first($hooks, function($key, $hook)
			// 	{
			// 		return did_action($hook) > 0;
			// 	});

			// 	if( ! $fired ){
			// 		$callback = $this->reigisterProviderCallback($provider);
			// 		foreach ( (array) $hooks as $hook){
			// 			add_action($hook, $callback);
			// 		}
			// 	}
			// 	else{
			// 		$this->register( $provider );
			// 	}
			// }
		}
		else{
			$this->register( $provider );
		}

	}

	protected function reigisterProviderCallback($provider)
	{
		$provider = is_object($provider) ? get_class($provider) : $provider;

		return function() use ($provider)
		{
			$this->register($provider);
		};
	}

	protected function addProviderDeferredServices(ProviderContract $provider)
	{
		$name = get_class($provider);
		$services = [];
		foreach ((array) $provider->provides() as $service) {
			$services[$service] = $name;
		}

		$this->addDeferredServices($services);
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
	 * Register a service provider with the application.
	 *
	 * @param  \TeaPress\Contracts\Core\ServiceProvider|string $provider
	 * @param  array                                      $options
	 * @param  bool                                       $force
	 * @return \TeaPress\Contracts\Core\ServiceProvider
	 */
	public function register($provider, $options = array(), $force = false)
	{
		if ($registered = $this->getProvider($provider) && ! $force)
			return $registered;

	    // If the given "provider" is a string, we will resolve it, passing in the
	    // application instance automatically for the developer. This is simply
	    // a more convenient way of specifying your service provider classes.
		if (is_string($provider))
			$provider = $this->resolveProviderClass($provider);


		foreach ( (array) $provider->provides() as $service) {
			if(isset($this->deferredServices[$service]))
				unset($this->deferredServices[$service]);
		}

		$provider->register();

	    // Once we have registered the service we will iterate through the options
	    // and set each of them on the application so they will be available on
	    // the actual loading of the service objects and for developer usage.
		foreach ($options as $key => $value)
		{
			$this[$key] = $value;
		}

		$this->markAsRegistered($provider);

	    // If the application has already booted, we will call this boot method on
	    // the provider class so it has an opportunity to do its boot logic and
	    // will be ready for any usage by the developer's application logics.
		if ($this->booted)
		{
			$this->bootProvider($provider);
		}

		return $provider;
	}


	/**
	 * Get the registered service provider instance if it exists.
	 *
	 * @param  \TeaPress\Contracts\Core\ServiceProvider|string  $provider
	 * @return \TeaPress\Contracts\Core\ServiceProvider|null
	 */
	public function getProvider($provider)
	{
		$name = is_string($provider) ? $provider : get_class($provider);

		return array_first($this->serviceProviders, function($key, $value) use ($name)
		{
			return $value instanceof $name;
		});
	}

	/**
	 * Resolve a service provider instance from the class name.
	 *
	 * @param  string  $provider
	 * @return \TeaPress\Contracts\Core\ServiceProvider
	 */
	public function resolveProviderClass($provider)
	{
		return $this->make($provider, ['app' => $this]);
	}



	/**
	 * Mark the given provider as registered.
	 *
	 * @param  \TeaPress\Contracts\Core\ServiceProvider
	 * @return void
	 */
	protected function markAsRegistered($provider)
	{
		$this->serviceProviders[] = $provider;

		$this->loadedProviders[get_class($provider)] = true;
	}

	public function registerDeferredProvider($provider, $service = null)
	{
		if (!isset($this->loadedProviders[$provider]))
		{
			$this->register($provider);
		}
	}

	/**
	 * Load the provider for a deferred service.
	 *
	 * @param  string  $service
	 * @return void
	 */
	public function loadDeferredService($service)
	{
		if (!isset($this->deferredServices[$service]))
			return;

		$provider = $this->deferredServices[$service];

		// If the service provider has not already been loaded and registered we can
		// register it with the application and remove the service from this list
		// of deferred services, since it will already be loaded on subsequent.
		if ( ! isset($this->loadedProviders[$provider]))
		{
			$this->register($provider);
		}
	}


	/**
	 * Boot the given service provider.
	 *
	 * @param  \TeaPress\Contracts\Core\ServiceProvider  $provider
	 * @return mixed
	 */
	protected function bootProvider(ProviderContract $provider)
	{
		if (method_exists($provider, 'boot'))
		{
			return $this->call([$provider, 'boot']);
		}

		return null;
	}


	/**
	 * Boot the application's service providers.
	 *
	 * @return void
	 */
	public function boot()
	{

	}

	/**
	 * Register a new boot listener.
	 *
	 * @param  mixed  $callback
	 * @return void
	 */
	public function booting($callback)
	{
		$this->bootingCallbacks[] = $callback;
	}

	/**
	 * Register a new "booted" listener.
	 *
	 * @param  mixed  $callback
	 * @return void
	 */
	public function booted($callback)
	{
		$this->bootedCallbacks[] = $callback;

		if ($this->isBooted()) $this->fireAppCallbacks([$callback]);
	}


	/**
	 * Call the booting callbacks for the application.
	 *
	 * @param  array  $callbacks
	 * @return void
	 */
	protected function fireAppCallbacks(array $callbacks)
	{
		foreach ($callbacks as $callback)
		{
			call_user_func($callback, $this);
		}
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
	public function make($abstract, Array $parameters = array())
	{
		$abstract = $this->getAlias($abstract);

		if( $this->isDeferredService($abstract) )
			$this->loadDeferredService($abstract);

		return parent::make($abstract, $parameters);
	}


/** Misc Methods **/

	/**
	 * Get the version number of the application.
	 *
	 * @return string
	 */
	public function version()
	{
		return static::VERSION;
	}

	/**
	 * Get the base path of the application installation.
	 *
	 * @return string
	 */
	public function basePath()
	{
		return !is_null($this->basePath) ? $this->basePath : (defined('ABSPATH') ? ABSPATH : null);
	}

	public function setBasePath($path)
	{
		$this->basePath = $path;
	}

	/**
	 * Get or check the current application environment.
	 *
	 * @param  mixed
	 * @return string
	 */
	public function environment()
	{
		return !is_null($this->environment) ? $this->environment : ($this->debug() ? 'development' : 'production');
	}

	/**
	 * Set the current application environment.
	 *
	 * @param  string
	 * @return static
	 */
	public function setEnvironment($environment)
	{
		$this->environment = $environment;

		return $this;
	}


	public function debug()
	{
		return !is_null($this->debug) ? $this->debug : (defined('WP_DEBUG') ? WP_DEBUG : false);
	}

	/**
	 * Set the current application environment.
	 *
	 * @param  bool
	 * @return static
	 */
	public function setDebug($debug)
	{
		$this->debug = $debug;

		return $this;
	}


	// public function hooks()
	// {
	// 	return $this->make('hooks');
	// }

	/**
	 * Determine if the application is currently down for maintenance.
	 *
	 * @return bool
	 */
	public function isDownForMaintenance()
	{
		return $this->debug();
	}

	/**
	 * Register all of the configured providers.
	 *
	 * @return void
	 */
	public function registerConfiguredProviders()
	{
		//Do nothing
	}


	/**
	 * Get the path to the cached "compiled.php" file.
	 *
	 * @return string
	 */
	public function getCachedCompilePath()
	{

	}

	/**
	 * Get the path to the cached services.json file.
	 *
	 * @return string
	 */
	public function getCachedServicesPath()
	{

	}

	/**
	 * Flush the container of all bindings and resolved instances.
	 *
	 * @return void
	 */
	public function flush()
	{
		parent::flush();

		$this->loadedProviders = [];
	}

/** End Misc Methods **/


}