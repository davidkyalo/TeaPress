<?php

namespace TeaPress\Utils;

use Closure;
use Countable;
use ArrayAccess;
use ArrayIterator;
use CachingIterator;
use JsonSerializable;
use IteratorAggregate;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

use Illuminate\Support\Arr;

/**
*
*/
class AttributeBag implements ArrayAccess, Arrayable, Countable, IteratorAggregate  {

	protected $items;

	public function __construct($items = null){
		$items = is_null($items) ? [] : $this->getArrayableItems($items);
		$this->items = (array) $items;
	}

	public function all(){
		return $this->items;
	}

	public static function make($items = null){
		return new static($items);
	}


	public function add($key, $value)
	{
		if (is_null($this->get($key))){
			$this->set($key, $value);
		}

		return $this;
	}

	/**
	 * Build a new array using a callback.
	 *
	 * @param  array  $array
	 * @param  callable  $callback
	 * @return array
	 */
	public static function build($items, callable $callback)
	{
		$results = [];

		foreach ($items as $key => $value)
		{
			list($innerKey, $innerValue) = call_user_func($callback, $key, $value);

			$results[$innerKey] = $innerValue;
		}

		return static::make($results);
	}

	/**
	 * Collapse an array of arrays into a single array.
	 *
	 * @param  array|\ArrayAccess  $array
	 * @return array
	 */
	public function collapse(){
		$results = [];

		foreach ($this->items as $values){
			$results = array_merge($results, $this->getArrayableItems($values));
		}

		return static::make($results);
	}


	/**
	 * Flatten a multi-dimensional associative array with dots.
	 *
	 * @param  array   $array
	 * @param  string  $prepend
	 * @return array
	 */
	public function dot(){
		$results = Arr::dot($this->items);
		return  static::make($results);
	}

	/**
	 * Get all of the given array except for a specified array of items.
	 *
	 * @param  array  $array
	 * @param  array|string  $keys
	 * @return array
	 */
	public function except($keys){
		$items = $this->all();
		Arr::forget($items, $keys);
		return static::make($items);
	}

	/**
	 * Fetch a flattened array of a nested array element.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @return array
	 */
	public function fetch($key){
		return Arr::fetch($this->items, $key);
	}

	/**
	 * Return the first element in an array passing a given truth test.
	 *
	 * @param  array  $array
	 * @param  callable  $callback
	 * @param  mixed  $default
	 * @return mixed
	 */
	public function first(callable $callback, $default = null){
		return Arr::first($this->items, $callback, $default);
	}

	/**
	 * Return the last element in an array passing a given truth test.
	 *
	 * @param  array  $array
	 * @param  callable  $callback
	 * @param  mixed  $default
	 * @return mixed
	 */
	public function last(callable $callback, $default = null){
		return Arr::last($this->items, $callback, $default);
	}


	/**
	 * Remove one or many array items from a given array using "dot" notation.
	 *
	 * @param  array  $array
	 * @param  array|string  $keys
	 * @return void
	 */
	public function forget($keys){
		Arr::forget( $this->items, $keys );
		return $this;
	}

	/**
	 * Get an item from an array using "dot" notation.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public function get($key, $default = null)
	{
		return Arr::get($this->items, $key, $default );
	}

	/**
	 * Check if an item exists in an array using "dot" notation.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @return bool
	 */
	public function has($key){
		return Arr::has($this->items, $key );
	}


	/**
	 * Pluck an array of values from an array.
	 *
	 * @param  array   $array
	 * @param  string  $value
	 * @param  string  $key
	 * @return array
	 */
	public function pluck($value, $key = null)
	{
		return Arr::pluck($this->items, $value, $key );
	}

	/**
	 * Get a value from the array, and remove it.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public function pull($key, $default = null)
	{
		$value = $this->get($key, $default);

		$this->forget($key);

		return $value;
	}

	/**
	 * Set an array item to a given value using "dot" notation.
	 *
	 * If no key is given to the method, the entire array will be replaced.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return array
	 */
	public function set($key, $value)
	{
		Arr::set( $this->items, $key, $value );
		return $this;
	}

	/**
	 * Filter the array using the given callback.
	 *
	 * @param  array  $array
	 * @param  callable  $callback
	 * @return array
	 */
	public static function where(callable $callback){
		return static::make( Arr::where($this->items, $callback) );
	}


	public function offsetExists($key){
		return $this->has($key);
	}

	public function offsetGet($key){
		return $this->get($key);
	}

	public function offsetSet($key, $value){
		return $this->set($key, $value);
	}

	public function offsetUnset($key){
		$this->forget($key);
	}


	public function count(){
		return count($this->items);
	}

	public function getIterator(){
		return new ArrayIterator($this->items);
	}

	public function toArray(){
		return $this->all();
	}

	public function __get($key){
		return $this->get($key);
	}

	public function __set($key, $value){
		return $this->set($key, $value);
	}

	protected function getArrayableItems($items){
		if (($items instanceof AttributeBag) || ($items instanceof Collection)){
			$items = $items->all();
		}
		elseif ($items instanceof Arrayable)
		{
			$items = $items->toArray();
		}

		return $items;
	}

}