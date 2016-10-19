<?php
namespace TeaPress\Tests;

use TeaPress\Utils\Arr;
use TeaPress\Tests\Base\TestKernel as Kernel;
use TeaPress\Core\Container as BaseContainer;

class Container extends BaseContainer
{
	protected static $instance;

	protected $basePath;

	protected $started = false;
	protected $booted = false;

	protected $manifest = [];

	protected $kernels = [];

	protected $loadedKernels = [];

	protected $serviceAliases = [];


	public function __construct($basePath)
	{
		$this->basePath = $basePath;
		$this->registerApplicationService();
		$this->start($this->basePath.'/manifest.php');
	}

	protected function loadManifest($manifest = null)
	{
		if(is_null($manifest))
			return;

		if(is_string($manifest))
			$manifest = @require($manifest);

		$this->manifest = (array) $manifest;
	}

	public function manifest($key=null, $default = null)
	{
		return Arr::get($this->manifest, $key, $default);
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
				'TeaPress\Core\Container',
				'TeaPress\Contracts\Core\Container',
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

	public function start($manifest = null)
	{
		if ($this->started) return;

		static::setInstance($this);

		$this->loadManifest($manifest);
		$this->registerKernels( (array) $this->manifest('kernels', []) );

		$this->started = true;

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
		Arr::pushUnique($this->serviceAliases, [$abstract, '>>'], ...(array) $alias);
	}

	public function serviceAliases($abstract)
	{
		$abstract = $this->getAlias($abstract);
		return Arr::get($this->serviceAliases, [$abstract, '>>'] , []);
	}
}
