<?php
namespace TeaPress\Core;

use TeaPress\Utils\Arr;
use TeaPress\Utils\Str;
use BadMethodCallException;
use TeaPress\Contracts\Utils\ArrayBehavior;
use TeaPress\Contracts\Core\Manifest as Contract;
use TeaPress\Contracts\Core\Application as AppContract;

class Manifest implements Contract, ArrayBehavior
{

	/**
	 * @var \TeaPress\Contracts\Core\Application
	 */
	protected $app;


	/**
	 * @var array
	 */
	protected $attributes = [];

	/**
	 * @var array
	 */
	protected $compiles = [];

	/**
	 * @var array
	 */
	protected $compiled = false;

	/**
	 * Create a new configuration repository.
	 *
	 * @param  \TeaPress\Contracts\Core\Application  $app
	 * @param  array  $attributes
	 * @return void
	 */
	public function __construct(array $attributes = [], AppContract $app = null)
	{
		$this->attributes = $attributes;

		if(!is_null($app))
			$this->setApplication($app);
	}

	/**
	 * Execute the specified script
	 *
	 * @return bool
	 */
	public function compile($force = false)
	{
		if($force || !$this->compiled){
			$manifest = $this;
			$app = $this->app;
			foreach ($this->compiles as $path) {
				$this->merge( (array) require($path), false );
			}
			$this->compiled = true;
		}
		return $this->compiled;
	}


	/**
	 * Add scripts to be compiled.
	 *
	 * @param  string  ...$paths
	 *
	 * @return static
	 */
	public function compiles(...$paths)
	{
		Arr::pushAll($this->compiles, null, $paths, true);
		return $this;
	}

	/**
	 * Determine if the given configuration value exists.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function has($key)
	{
		return Arr::has($this->attributes, $key);
	}

	/**
	 * Get the specified configuration value.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public function get($key, $default = null)
	{
		return Arr::get($this->attributes, $key, $default);
	}

	/**
	 * Set a given configuration value.
	 *
	 * @param  array|string  $key
	 * @param  mixed   $value
	 * @return static
	 */
	public function set($key, $value = null)
	{
		if (is_array($key)) {
			foreach ($key as $innerKey => $innerValue) {
				Arr::set($this->attributes, $innerKey, $innerValue);
			}
		} else {
			Arr::set($this->attributes, $key, $value);
		}

		return $this;
	}

	/**
	 * Prepend a value onto an array configuration value.
	 *
	 * @param  string  $key
	 * @param  mixed  $value
	 * @return static
	 */
	public function prepend($key, $value)
	{
		$array = $this->get($key, []);

		array_unshift($array, $value);

		return $this->set($key, $array);
	}

	/**
	 * Push a value onto an array configuration value.
	 *
	 * @param  string  $key
	 * @param  mixed  $value
	 * @return static
	 */
	public function push($key, $value)
	{
		$array = $this->get($key, []);

		$array[] = $value;

		return $this->set($key, $array);
	}

	/**
	 * Get all of the configuration items for the application.
	 *
	 * @return array
	 */
	public function getAttributes()
	{
		return $this->attributes;
	}

	/**
	 * Get all of the configuration items for the application.
	 *
	 * @return array
	 */
	public function all()
	{
		return $this->attributes;
	}

	/**
	 * Merge the given configuration values with the current.
	 *
	 * @param  array|TeaPress\Contracts\Core\Manifest $attributes
	 * @param  bool  $recursive
	 *
	 * @return static
	 */
	public function merge($attributes, $recursive = true)
	{
		if($attributes instanceof static)
			$attributes = $items->getAttributes();

		if($recursive)
			$this->set( Arr::dot( (array) $attributes ) );
		else
			$this->attributes = array_merge($this->attributes, (array) $attributes);

		return $this;
	}


	/**
	 * Get the application instance
	 *
	 * @return \TeaPress\Contracts\Core\Application
	 */
	public function getApplication()
	{
		return $this->app;
	}

	/**
	 * Set the application instance
	 *
	 * @param \TeaPress\Contracts\Core\Application $app
	 *
	 * @return static
	 */
	public function setApplication(AppContract $app)
	{
		$this->app = $app;
		return $this;
	}



	/**
	 * Get the number of configuration items.
	 *
	 * @return int
	 */
	public function count()
	{
		return count($this->getAttributes());
	}

	/**
	 * Get an array of the configuration items.
	 *
	 * @return array
	 */
	public function toArray()
	{
		return $this->getAttributes();
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

	public function __get($key)
	{
		return property_exists($this, $key) ? $this->{$key} : $this->get($key);
	}

	public function __set($key, $value)
	{
		if(roperty_exists($this, $key))
			$this->{$key} = $value;
		else
			$this->set($key, $value);
	}


	/**
	 * Dynamically bind parameters to the view.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return \Illuminate\View\View
	 *
	 * @throws \BadMethodCallException
	 */
	public function __call($method, $parameters)
	{
		if ( strpos($method, 'set') === 0 )
			return $this->set( Str::snake(substr($method, 3)), $parameters[0] );

		throw new BadMethodCallException("Method [$method] does not exist on manifest.");
	}
}
