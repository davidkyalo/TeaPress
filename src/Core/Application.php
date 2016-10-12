<?php

namespace TeaPress\Core;

use TeaPress\Utils\Arr;
use BadMethodCallException;
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

	// /**
	//  * @var string
	//  */
	// protected $basePath;

	// /**
	//  * @var string
	//  */
	// protected $storagePath;

	/**
	 * @var bool
	 */
	protected $booted = false;

	/**
	 * @var \TeaPress\Core\Manifest
	 */
	protected $manifest;

	/**
	 * @var array
	 */
	protected $kernels = [];

	/**
	 * @var array
	 */
	protected $loadedKernels = [];

	/**
	 * @var array
	 */
	protected $serviceAliases = [];

	/**
	 * Creates the application instance.
	 *
	 * @param  array|string|\TeaPress\Contracts\Core\Manifest  $manifest
	 *
	 * @return static
	 */
	public static function create($mainfest)
	{
		if($instance = static::getInstance()){
			throw new BadMethodCallException("Application instance already created.");
		}

		return new static($manifest);
	}

	/**
	 * Creates the application instance.
	 *
	 * @param  array|string|\TeaPress\Contracts\Core\Manifest  $manifest
	 *
	 * @return void
	 */
	protected function __construct($manifest)
	{
		static::setInstance($this);

		$this->setManifest( $this->loadManifest($manifest) );

		$this->registerApplicationService();
		$this->registerCoreServices();
	}

	// /**
	// * Get the current application's base path.
	// *
	// * @return void
	// */
	// public function basePath($path = '')
	// {
	// 	return join_paths($this->basePath, $path);
	// }

	/**
	 * Sets the manifest for the current application.
	 *
	 * @param  \TeaPress\Contracts\Core\Manifest   $manifest
	 *
	 * @return static
	 */
	public function setManifest(ManifestContract $manifest)
	{
		$this->manifest = $manifest;
		if(is_string($manifest))
			$manifest = @require($manifest);

		$this->manifest = (array) $manifest;

		$this->basePath = $this->manifest('base_path');

		return $this;
	}


	/**
	 * Loads the manifest for the current application.
	 *
	 * @param  array|string|\TeaPress\Contracts\Core\Manifest  $manifest
	 *
	 * @return void
	 */
	public function loadManifest($manifest)
	{
		if(!($manifest instanceof ManifestContract)){
			$manifest = $this->createManifest()->compiles(... (array) $manifest );
		}

		$manifest->setApplication($this);

		return $manifest;
	}

	/**
	 * Loads the manifest for the current application.
	 *
	 * @param  array $manifest
	 *
	 * @return \TeaPress\Contracts\Core\Manifest
	 */
	public function createManifest(array $attributes = [])
	{
		return new Manifest($attributes);
	}


	/**
	* Get the specified item from the manifest.
	*
	* @return void
	*/
	public function manifest($key=null, $default = null)
	{
		return is_null($key) ? $this->manifest : $this->manifest->get($key, $default);
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
				'TeaPress\Core\Application',
				'TeaPress\Contracts\Core\Container',
				'TeaPress\Contracts\Core\Application',
				'Illuminate\Contracts\Container\Container',
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

	public function start()
	{
		if (!$this->started){
			$this->registerKernels( (array) $this->manifest('kernels', []) );
			$this->started = true;

		}
		return $this;
	}

	public function registerKernels(array $kernels, $force = false)
	{
		foreach ($kernels as $kernel) {
			$this->register($kernel, $force);
		}
	}

	public function register($kernel, $force = false)
	{
		if ($registered = $this->getKernel($kernel) && ! $force)
			return $registered;

		if (is_string($kernel))
			$kernel = $this->resolveKernelClass($kernel);

		$kernel->register();

		$this->markAsRegistered($kernel);

		if ($this->booted)
		{
			$this->bootKernel($kernel);
		}

		return $kernel;
	}

	public function getKernel($kernel)
	{
		$name = is_string($kernel) ? $kernel : get_class($kernel);

		return array_first($this->kernels, function($key, $value) use ($name)
		{
			return $value instanceof $name;
		});
	}

	public function resolveKernelClass($kernel)
	{
		return $this->make($kernel, ['app' => $this]);
	}

	protected function markAsRegistered($kernel)
	{
		$this->kernels[] = $kernel;

		$this->loadedKernels[get_class($kernel)] = true;
	}

	protected function bootKernel($kernel)
	{
		$kernel->boot();
	}

	public function boot()
	{
		if ($this->booted) return;

		foreach ($this->kernels as $kernel) {
			$this->bootKernel($kernel);
		}

		$this->booted = true;
	}

	public function alias($abstract, $alias)
	{
		parent::alias($abstract, $alias);
		Arr::pushAll($this->serviceAliases,  $abstract, (array) $alias, true, '>>');
	}

	public function serviceAliases($abstract)
	{
		$abstract = $this->getAlias($abstract);
		return Arr::get($this->serviceAliases, $this->getAlias($abstract) , [], '>>');
	}

}