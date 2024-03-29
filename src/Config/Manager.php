<?php

namespace TeaPress\Config;

use ArrayIterator;
use IteratorAggregate;
use TeaPress\Utils\Arr;
use InvalidArgumentException;
use TeaPress\Contracts\Core\Container;
use TeaPress\Contracts\Signals\Signals;
use TeaPress\Contracts\Utils\Arrayable;
use TeaPress\Contracts\Config\Filterable;
use TeaPress\Contracts\Utils\ArrayBehavior;
use Illuminate\Support\NamespacedItemResolver;
use TeaPress\Contracts\Config\Manager as Contract;
use TeaPress\Contracts\Config\Repository as RepositoryContract;

class Manager extends NamespacedItemResolver implements Contract, Filterable, ArrayBehavior, Arrayable, IteratorAggregate
{
	/**
	 * The Container instance
	 *
	 * @var \TeaPress\Contracts\Core\Container
	 */
	protected $container;

	/**
	 * The Loader Interface.
	 *
	 * @var \TeaPress\Config\LoaderInterface
	 */
	protected $loader;

	/**
	 * The Signals instance
	 *
	 * @var \TeaPress\Contracts\Signals\Signals
	 */
	protected $signals;

	/**
	 * All of the configuration items.
	 *
	 * @var array
	 */
	protected $repositories = [];

	/**
	 * Repository resolvers
	 *
	 * @var array
	 */
	protected $resolvers = [];

	/**
	 * All of the configuration items.
	 *
	 * @var array
	 */
	protected $parsed = [];

	/**
	 * Create a new configuration repository.
	 *
	 * @param  \TeaPress\Contracts\Core\Container 	$container
	 * @param  \TeaPress\Contracts\Signals\Signals	$signals
	 * @param  \TeaPress\Config\LoaderInterface 	$loader
	 * @param  string|array|null  $paths
	 * @return void
	 */
	public function __construct(Container $container, Signals $signals, LoaderInterface $loader, $paths = null)
	{
		$this->loader = $loader;
		$this->signals = $signals;
		$this->container = $container;
		$this->bindToContainer();
		$this->addPath($paths);
	}

	/**
	 * Bind the manager to the service container.
	 *
	 * @return void
	 */
	protected function bindToContainer()
	{
		$this->container->instance('config', $this);
		$this->container->alias('config', [
			'TeaPress\Config\Manager',
			'TeaPress\Contracts\Config\Manager',
			'TeaPress\Contracts\Config\Repository',
		]);
	}

	/**
	 * Determine if the given configuration value exists.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function has($key, $filters=false)
	{
		list($namespace, $item) = $this->parseKey($key);

		$config = $this->getRepository($namespace);

		return $config ? $config->has($item, $filters) : false;
	}

	/**
	 * Get the specified configuration value.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public function get($key, $default = null, $filter =true)
	{
		list($namespace, $item) = $this->parseKey($key);

		$config = $this->getRepository($namespace);

		return $config ? $config->get($item, $default, $filter) : value($default);
	}

	/**
	 * Set a given configuration value.
	 *
	 * @param  array|string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function set($key, $value = null)
	{
		list($namespace, $item) = $this->parseKey($key);

		$config = $this->getOrCreateRepository($namespace);

		if(is_null($item) && is_array($value))
			return $config->set($value);
		else
			return $config->set($item, $value);
	}

	/**
	 * Prepend a value onto an array configuration value.
	 *
	 * @param  string  $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function prepend($key, $value)
	{
		list($namespace, $item) = $this->parseKey($key);

		$config = $this->getOrCreateRepository($namespace);

		return $config->prepend($item, $value);
	}

	/**
	 * Push a value onto an array configuration value.
	 *
	 * @param  string  $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function push($key, $value)
	{
		list($namespace, $item) = $this->parseKey($key);

		$config = $this->getOrCreateRepository($namespace);

		return $config->push($item, $value);
	}

	/**
	 * Get all of the configuration items for the application.
	 *
	 * @return array
	 */
	public function all()
	{
		return $this->repositories;
	}

	/**
	 * Get the repository instance for the given namespace
	 *
	 * @param  string|null  $namespace
	 * @param  bool  $or_create_empty
	 *
	 * @return \TeaPress\Config\Repository|null
	 */
	public function repository($namespace = null)
	{
		return $this->getRepository($namespace);
	}

	/**
	 * Get the repository instance for the given namespace
	 *
	 * @param  string|null  $namespace
	 * @param  bool  $or_create_empty
	 *
	 * @return \TeaPress\Config\Repository|null
	 */
	public function getRepository($namespace = null, $or_create_empty = false)
	{
		$namespace = $this->getNamespace($namespace);

		if( $this->isLoaded($namespace) ){
			return $this->repositories[$namespace];
		}

		if($or_create_empty || $this->hasNamespace($namespace)){
			return $this->load($namespace);
		}
	}


	/**
	 * Get the repository instance for the given namespace
	 *
	 * @param  string|null  $namespace
	 * @param  bool  $create
	 *
	 * @return \TeaPress\Config\Repository
	 */
	public function getOrCreateRepository($namespace = null)
	{
		return $this->getRepository($namespace, true);
	}

	/**
	 * Load the specified config group.
	 *
	 * @param  \TeaPress\Config\Repository|string  $repository
	 * @param  string|array|null  $path
	 *
	 * @return \TeaPress\Config\Repository|null
	 */
	public function load($repository, $path = null)
	{
		list($repository, $namespace) = $this->parseRepository($repository);

		if( $this->isLoaded($namespace) ){
			if(is_null($repository)){
				$repository = $this->repositories[$namespace];
			}
			elseif($repository instanceof RepositoryContract){
				$repository->merge($this->repositories[$namespace]);
				$this->repositories[$namespace] = $repository;
			}
			else{
				$msg = "Config repository should be an instance of '".RepositoryContract::class."''.";
				throw new InvalidArgumentException($msg);
			}
		}

		if(is_string($repository)){
			$repository = $this->makeRepository()
		}

		if( !($repository instanceof RepositoryContract) ){

		}

		if(!is_null($path)){
			$this->addNamespace($namespace, $path);
		}

		if( is_null($repository) ){
			$config = $this->loader->loadNamespace($namespace);
			$repository = $this->repositories[$namespace] = $this->makeRepository($config, $namespace);
		}

		$repository->setSignals($this->signals);

		return $repository;
	}

	/**
	 * Set the repository instance for the given namespace
	 *
	 * @param  \TeaPress\Config\Repository|string  $repository
	 *
	 * @return arrays
	 */
	protected function parseRepository($repository)
	{
		if(is_string($repository))
			return [ null, $repository ];
		else
			return [ $repository, $repository->getNamespace()];
	}


	/**
	 * Determine if the given namespace is loaded.
	 *
	 * @param  string  $namespace
	 * @return bool
	 */
	public function isLoaded($namespace)
	{
		return isset($this->repositories[$namespace]) && !is_null($this->repositories[$namespace]);
	}

	/**
	 * Create a new repository instance from the given class.
	 *
	 * @param  string  $repository
	 * @param  array   $config
	 * @param  string  $namespace
	 *
	 * @return \TeaPress\Config\Repository
	 */
	protected function makeRepository($repository, array $config = [], $namespace = null, $signalsNamespace = null)
	{
		$repository = $this->container->make($repository, ['items' => $config]);
		$repository->setNamespace($namespace);
		$repository->setSignalsNamespace($signalsNamespace);
		return new Repository($config, $namespace);
	}

	/**
	 * Create a new repository instance
	 *
	 * @param  array  $config
	 * @param  string  $namespace
	 *
	 * @return \TeaPress\Config\Repository
	 */
	protected function newRepository(array $config = [], $namespace = null)
	{
		return new Repository($config, $namespace);
	}

	/**
	 * Determine if the given namespace is registered.
	 *
	 * @param  string  $namespace
	 *
	 * @return bool
	 */
	public function hasNamespace($namespace)
	{
		return $this->loader->hasNamespace($namespace);
	}

	/**
	 * Add a new namespace to the loader.
	 *
	 * @param  string  $namespace
	 * @param  string|array  $paths
	 * @return void
	 */
	public function addNamespace($namespace, $paths = null)
	{
		$this->loader->addNamespace($namespace, $paths);

		if($paths && $this->isLoaded($namespace)){
			$config = $this->loader->loadPath( (array) $paths);
			$this->getRepository($namespace)->merge($config);
		}

	}

	/**
	 * Add a path  new namespace to the loader.
	 *
	 * @param  array|string  $path
	 * @param  string  $name
	 * @param  string  $namespace
	 * @return void
	 */
	public function addPath($path, $name = null, $namespace = null)
	{
		$namespace = $this->getNamespace($namespace);
		$path = is_null($name) ? $path : [$name => $path];
		return $this->addNamespace($namespace, $path);
	}



	/**
	 * Parse a key into namespace, group, and item.
	 *
	 * @param  string  $key
	 * @return array
	 */
	public function parseKey($key)
	{
		if(is_null($key))
			return [$this->getNamespace(), null];

		// If we've already parsed the given key, we'll return the cached version we
		// already have, as this will save us some processing. We cache off every
		// key we parse so we can quickly return it on all subsequent requests.
		if (isset($this->parsed[$key])) {
			return $this->parsed[$key];
		}

		// If the key does not contain a double colon, it means the key is not in a
		// namespace, and is just a regular configuration item. Namespaces are a
		// tool for organizing configuration items for things such as modules.
		if (strpos($key, '::') === false)
			$parsed = [ $this->getNamespace(), $key];
		else
			$parsed = $this->parseNamespacedSegments($key);

		// Once we have the parsed array of this key's elements, such as its groups
		// and namespace, we will cache each array inside a simple list that has
		// the key and the parsed array for quick look-ups for later requests.
		return $this->parsed[$key] = $parsed;
	}


	/**
	 * Parse an array of namespaced segments.
	 *
	 * @param  string  $key
	 * @return array
	 */
	protected function parseNamespacedSegments($key)
	{
		return array_pad( explode('::', rtrim($key, ':'), 2), 2, null);
	}

	/**
	 * Parse an array of basic segments.
	 *
	 * @param  array  $segments
	 * @return array
	 */
	protected function parseBasicSegments(array $segments)
	{
		return implode('.', $segments);
	}

	/**
	 * Get the appropriate namespace depending on the the provided.
	 *
	 * @param  string  $namespace
	 * @return string
	 */
	public function getNamespace($namespace = null)
	{
		return $namespace ?: 'main';
	}

	/**
	 * Set the appropriate namespace depending on the the provided.
	 *
	 * @param  string  $namespace
	 * @return string
	 */
	public function setNamespace($namespace)
	{
		//
	}

	/**
	 * Get the signals hub instance.
	 *
	 * @return \TeaPress\Contracts\Signals\Signals
	 */
	public function getSignals()
	{
		return $this->signals;
	}


	/**
	 * Set the signals hub instance.
	 *
	 * @param  \TeaPress\Contracts\Signals\Signals  $key
	 *
	 * @return void
	 */
	public function setSignals(Signals $signals)
	{
		$this->signals = $signals;
	}


	/**
	* Bind a config value filter. The provided callback will be executed every time the an attempt to get the value is made.
	*
	* @param  string  $key
	* @param  \Closure|array|string  $callback
	* @param  int|null  $priority
	*
	* @return bool
	*/
	public function filter($key, $callback, $priority = null)
	{
		list($namespace, $item) = $this->parseKey($key);

		$repository = $this->getOrCreateRepository($namespace);

		if($repository instanceof Filterable)
			return $repository->filter($item, $callback, $priority);
	}

	/**
	* Determine if the given key has filters. If a key is not specified, returns an array of filtered keys
	*
	* @param  string  $key
	*
	* @return bool|array
	*/
	public function filtered($key=null)
	{
		list($namespace, $item) = $this->parseKey($key);

		$repository = $this->getRepository($namespace);

		return $repository && ($repository instanceof Filterable)
					? $repository->filtered($item) : (is_null($item) ? [] : false);
	}

	/**
	 * Get the specified configuration value.
	 *
	 * @param  string 	$key
	 *
	 * @return string|array
	 */
	public function getFilterTag($key)
	{
		list($namespace, $item) = $this->parseKey($key);

		$repository = $this->getOrCreateRepository($namespace);

		if($repository instanceof Filterable)
			return $repository->getFilterTag($item);
	}


	/**
	 * Get all of the configuration items for the application.
	 *
	 * @return array
	 */
	public function getItems($filter=false)
	{
		$config = [];
		foreach ($this->repositories as $key => $repository) {
			$config[$key] = $repository->getItems($filter);
		}
		return $config;
	}

	/**
	 * Merge the given configuration values with the current.
	 *
	 * @param  array|TeaPress\Contracts\Config\Repository $items
	 * @param  string  $namespaces
	 *
	 * @return void
	 */
	public function merge($items, $namespace = null)
	{
		$config = $this->getOrCreateRepository( $this->getNamespace($namespace) );
		$config->merge($items);
	}

	/**
	 * Get the configuration files loader.
	 *
	 * @return \TeaPress\Config\LoaderInterface
	 */
	public function getLoader()
	{
		return $this->loader;
	}

	/**
	 * Get the number of configuration items.
	 *
	 * @return int
	 */
	public function count()
	{
		return count($this->repositories);
	}

	/**
	 * Get an array of the configuration items.
	 *
	 * @return array
	 */
	public function toArray()
	{
		$config = [];
		foreach ($this->repositories as $key => $repository) {
			$config[$key] = $repository->toArray();
		}

		return $config;
	}


	public function getIterator()
	{
		return new ArrayIterator($this->toArray());
	}

	/**
	 * Determine if the given configuration option exists.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function offsetExists($key)
	{
		return $this->has($key);
	}

	/**
	 * Get a configuration option.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function offsetGet($key)
	{
		return $this->get($key);
	}

	/**
	 * Set a configuration option.
	 *
	 * @param  string  $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function offsetSet($key, $value)
	{
		$this->set($key, $value);
	}

	/**
	 * Unset a configuration option.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function offsetUnset($key)
	{
		$this->set($key, null);
	}

	/**
	 * Get all the registered repository names.
	 *
	 * @return array
	 */
	public function offsets()
	{
		return array_keys($this->loader->namespaces());
	}

	/**
	 * Get a configuration repository.
	 *
	 * @param  string  $key
	 * @return \TeaPress\Config\Repository|null
	 */
	public function __get($key)
	{
		return $this->getRepository($key);
	}

}
