<?php

namespace TeaPress\Config;

use ArrayIterator;
use TeaPress\Utils\Arr;
use TeaPress\Contracts\Utils\Arrayable;
use TeaPress\Contracts\Utils\ArrayBehavior;
use TeaPress\Contracts\Config\Repository as Contract;

class Repository implements Contract, ArrayBehavior, Arrayable
{

	/**
	 * The namespace for this repository.
	 *
	 * @var string
	 */
	protected $namespace;

	/**
	 * All of the configuration items.
	 *
	 * @var array
	 */
	protected $items = [];

	/**
	 * Create a new configuration repository.
	 *
	 * @param  string  $namespace
	 * @param  array  $items
	 * @return void
	 */
	public function __construct(array $items = [], $namespace = null)
	{
		$this->items = $items;
		$this->namespace = $namespace;
	}

	/**
	 * Get the namespace for this repository
	 *
	 * @return string
	 */
	public function getNamespace()
	{
		return $this->namespace;
	}


	/**
	 * Set the namespace for this repository
	 *
	 * @param  string  $key
	 *
	 * @return string
	 */
	public function setNamespace($namespace)
	{
		$this->namespace = $namespace;
	}



	/**
	 * Determine if the given configuration value exists.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function has($key)
	{
		return Arr::has($this->items, $key);
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
		return Arr::get($this->items, $key, $default);
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
		if (is_array($key)) {
			foreach ($key as $innerKey => $innerValue) {
				Arr::set($this->items, $innerKey, $innerValue);
			}
		} else {
			Arr::set($this->items, $key, $value);
		}
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
		$array = $this->get($key, []);

		array_unshift($array, $value);

		$this->set($key, $array);
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
		$array = $this->get($key, []);

		$array[] = $value;

		$this->set($key, $array);
	}

	/**
	 * Get all of the configuration items for the application.
	 *
	 * @return array
	 */
	public function getItems()
	{
		return $this->items;
	}

	/**
	 * Get all of the configuration items for the application.
	 *
	 * @return array
	 */
	public function all()
	{
		return $this->items;
	}

	/**
	 * Merge the given configuration values with the current.
	 *
	 * @param  array|TeaPress\Contracts\Config\Repository $items
	 * @param  bool  $recursive
	 *
	 * @return void
	 */
	public function merge($items, $recursive = true)
	{
		if($items instanceof Contract)
			$items = $items->getItems();

		if($recursive)
			$this->set( Arr::dot( (array) $items ) );
		else
			$this->items = array_merge($this->items, (array) $items);
	}


	/**
	 * Get the number of configuration items.
	 *
	 * @return int
	 */
	public function count()
	{
		return count($this->items);
	}

	/**
	 * Get an array of the configuration items.
	 *
	 * @return array
	 */
	public function toArray()
	{
		return $this->items;
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
}
