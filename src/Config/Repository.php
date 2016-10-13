<?php

namespace TeaPress\Config;

use ArrayIterator;
use TeaPress\Utils\Arr;
use TeaPress\Contracts\Utils\Arrayable;
use TeaPress\Contracts\Config\Filterable;
use TeaPress\Contracts\Utils\ArrayBehavior;
use TeaPress\Contracts\Signals\Hub as Signals;
use TeaPress\Contracts\Config\Repository as Contract;

class Repository implements Contract, Filterable, ArrayBehavior, Arrayable
{
	/**
	 * The Signals Hub
	 *
	 * @var \TeaPress\Contracts\Signals\Hub
	 */
	protected $signals;

	/**
	 * The namespace for this repository.
	 *
	 * @var string
	 */
	protected $namespace;

	/**
	 * The unique identifire for this repository's filters.
	 *
	 * @var string
	 */
	protected $signalsDomain;

	/**
	 * All of the configuration items.
	 *
	 * @var array
	 */
	protected $items = [];

	/**
	 * All of the configuration items.
	 *
	 * @var array
	 */
	protected $filtered = [];

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

	protected function filterValue($key, $value)
	{
		if($this->signals){
			$value = $this->signals->applyFilters($this->getFilterTag($key), $value, $this);
		}
		return $value;
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
	public function filter($key, $callback, $priority = null, $force =false)
	{
		if($this->signals){
			$tag = $this->getFilterTag($key);
			$this->signals->addFilter($tag, $callback, $priority);
			$this->filtered[$key] = true;
		}
		return true;
	}

	/**
	* Determine if the given key has filters. If a key is not specified, returns an array of filtered keys
	*
	* @param  string|null  $key
	*
	* @return bool|array
	*/
	public function filtered($key=null)
	{
		if(is_null($key))
			return $this->filtered;

		return isset($this->filtered[$key]); //&& $this->filtered[$key];
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
		$domain = $this->getSignalsDomain();

		if($domain && $domain[0] !== '@')
			$domain = '@'.$domain;

		return [$this->getSignalsNamespace(), $key.$domain];
	}

	/**
	* Get this emitter's events namespace.
	*
	* @return string|null
	*/
	protected function getSignalsNamespace()
	{
		return Contract::class;
	}


	/**
	 * Determine if the given configuration value exists.
	 * If filters is true, returns true if the key has any filters.
	 *
	 * @param  string  $key
	 * @param  bool  $filters  whether or not to check on filters.
	 * @return bool
	 */
	public function has($key, $filters=false)
	{
		return Arr::has($this->items, $key) || ( $filters && $this->filtered($key) );
	}

	/**
	 * Get the specified configuration value.
	 *
	 * @param  string 	$key
	 * @param  mixed 	$default
	 * @param  bool 	$filter 	whether or not to apply filters.
	 * @return mixed
	 */
	public function get($key, $default = null, $filter=true)
	{
		$value = Arr::get($this->items, $key, $default);

		if( $filter && $this->filtered($key) ){
			$value = $this->filterValue($key, $value);
		}

		return $value;
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
	 * @param  bool 	$filter 	whether or not to apply filters.
	 *
	 * @return array
	 */
	public function getItems($filter=false)
	{
		$items = $this->items;

		if($filter){
			foreach ( (array) $this->filtered() as $key => $is_filtered) {

				if(!$is_filtered) continue;

				Arr::set( $items, $key, $this->filterValue( $key, Arr::get($items, $key) ) );

			}
		}
		return $items;
	}

	/**
	 * Get all of the configuration items for the application.
	 *
	 * @param  bool 	$filter 	whether or not to apply filters.
	 *
	 * @return array
	 */
	public function all($filter=true)
	{
		return $this->getItems($filter);
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
		if($items instanceof Filterable && $this->signals){
			foreach ( (array) $items->filtered() as $key => $is_filtered) {
				if(!$is_filtered) continue;

				$filters = $this->signals->getCallbacksMerged( $items->getFilterTag( $key ), false );

				foreach ($filters as $filter) {
					$this->filter( $key, $filter['callback'], $filter['priority'] );
				}
			}
		}

		if($items instanceof Contract)
			$items = $items->getItems();


		if($recursive)
			$this->set( Arr::dot( (array) $items ) );
		else
			$this->items = array_merge($this->items, (array) $items);
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
	 * @param  string  $namespace
	 *
	 * @return void
	 */
	public function setNamespace($namespace)
	{
		$this->namespace = $namespace;
	}



	/**
	 * Get the signals hub instance.
	 *
	 * @return \TeaPress\Contracts\Signals\Hub
	 */
	public function getSignals()
	{
		return $this->signals;
	}

	/**
	 * Set the signals hub instance.
	 *
	 * @param  \TeaPress\Contracts\Signals\Hub  $signals
	 *
	 * @return void
	 */
	public function setSignals(Signals $signals)
	{
		$this->signals = $signals;
	}


	/**
	* Get the signals domain.
	*
	* @return string|null
	*/
	protected function getSignalsDomain()
	{
		return $this->signalsDomain ?: ($this->namespace == '*' ? '': $this->namespace );
	}

	/**
	 * Set the signals domain.
	 *
	 * @param  string  $domain
	 *
	 * @return void
	 */
	public function setSignalsDomain($domain)
	{
		$this->signalsDomain = $domain;
	}


	/**
	 * Get the number of configuration items.
	 *
	 * @return int
	 */
	public function count()
	{
		return count($this->getItems());
	}

	/**
	 * Get an array of the configuration items.
	 *
	 * @return array
	 */
	public function toArray()
	{
		return $this->getItems(true);
	}


	public function getIterator()
	{
		return new ArrayIterator($this->getItems(true));
	}

	/**
	 * Determine if the given configuration option exists.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function offsetExists($key)
	{
		return $this->has($key, false);
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
