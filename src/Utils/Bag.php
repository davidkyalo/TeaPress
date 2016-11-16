<?php
namespace TeaPress\Utils;

use ArrayIterator;
use IteratorAggregate;
use TeaPress\Utils\Arr;
use TeaPress\Contracts\Utils\ArrayBag;
use TeaPress\Contracts\Utils\Arrayable;
use TeaPress\Contracts\Utils\ArrayBehavior;

class Bag implements IteratorAggregate, ArrayBehavior, ArrayBag, Arrayable
{
	const UNIQUE_KEY = 0;
	const UNIQUE_VALUE = 1;

	/**
	 * @var array
	 */
	protected $items = [];

	/**
	 * Create a new bag instance.
	 *
	 * @param  mixed $items
	 * @return static
	 */
	public static function create($items = [])
	{
		return new static($items);
	}

	/**
	 * Create the bag instance.
	 *
	 * @param  mixed $items
	 * @return void
	 */
	public function __construct($items = [])
	{
		$this->items = $this->castToArray($items);
	}

	/**
	 * Returns all the items.
	 *
	 * @return array An array of items
	 */
	public function all()
	{
		return $this->items;
	}

	/**
	 * Returns the item keys.
	 *
	 * @return array An array of item keys
	 */
	public function keys()
	{
		return array_keys($this->items);
	}

	/**
	 * Set item(s) value(s).
	 * Multiple items can be passed as an array.
	 *
	 * @param  mixed  $key
	 * @param  mixed  $value
	 * @return $this
	 */
	public function set($key, $value = null)
	{
		$items = is_scalar($key) ? [$key => $value] : $this->castToArray($key);

		$this->items = array_replace($this->items, $items);

		return $this;
	}

	/**
	 * Append item(s) to the end of the bag.
	 *
	 * @param  mixed  ...$values
	 * @return $this
	 */
	public function append(...$values)
	{
		foreach ($values as $value) {
			$this->items[] = $value;
		}

		return $this;
	}

	/**
	 * Perpend item(s) to the beginning of the bag.
	 *
	 * @param  mixed  ...$values
	 * @return $this
	 */
	public function perpend(...$values)
	{
		$this->items = array_merge($values, $this->items);
		return $this;
	}

	/**
	 * Adds item(s) to the bag if it doesn't exist.
	 *
	 * @param  string|array  $key
	 * @param  mixed         $value
	 * @param  int          $unique
	 * @return $this
	 */
	public function add($key, $value = null, $unique = self::UNIQUE_KEY)
	{
		if(is_null($key)){
			return !$this->contains($value) ? $this->append($value) : $this;
		}

		if(is_scalar($key)){
			$items = [$key => $value];
		}
		else{
			$items = $this->castToArray($key);
			$unique = func_num_args() === 2 && is_int($value) ? $value : $unique;
		}

		foreach ($items as $key => $value) {
			switch ($unique) {
				case self::UNIQUE_VALUE:
					if(!$this->contains($value)){
						$this->items[$key] = $value;
					}
					break;

				case self::UNIQUE_KEY:
				default:
					if(!$this->has($key)){
						$this->items[$key] = $value;
					}
					break;
			}
		}
		return $this;
	}

	/**
	 * Get item value.
	 *
	 * @param  string  $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	public function get($key, $default = null)
	{
		return $this->has($key) ? $this->items[$key] : value($default);
	}

	/**
	 * Determine if the given item key is set.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function has($key)
	{
		return array_key_exists($key, $this->items);
	}

	/**
	 * Determine if the given item value exists.
	 *
	 * @param  string  $value
	 * @return bool
	 */
	public function contains($value)
	{
		return in_array($value, $this->items);
	}

	/**
	 * Remove the given items.
	 *
	 * @param  strings|array  ...$keys
	 * @return $this
	 */
	public function remove(...$keys)
	{
		if(count($keys) === 1 && is_array($keys[0])){
			$keys = $keys[0];
		}

		foreach ($keys as $key) {
			unset($this->items[$key]);
		}

		return $this;
	}

	/**
	 * Remove the last item and return it.
	 *
	 * @return mixed
	 */
	public function pop()
	{
		return array_pop($this->items);
	}

	/**
	 * Remove the first item and return it.
	 *
	 * @return mixed
	 */
	public function shift()
	{
		return array_shift($this->items);
	}

	/**
	 * Remove the first item and return it.
	 *
	 * @param  string  $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	public function pull($key, $default = null)
	{
		$value = $this->get($key, $default);
		$this->remove($key);
		return $value;
	}

	/**
	 * Replaces the current items with a new set.
	 *
	 * @param  array  $items
	 * @return $this
	 */
	public function replace($items)
	{
		$this->items = $this->castToArray($items);
		return $this;
	}

	/**
	 * Get the first item.
	 *
	 * @param  mixed  $default
	 * @return mixed
	 */
	public function first($default = null)
	{
		return Arr::first($this->items, null, $default);
	}

	/**
	 * Get the first item passing a given truth test.
	 *
	 * @param  callable  $callback
	 * @param  mixed  	 $default
	 * @return mixed
	 */
	public function ufirst(callable $callback, $default = null)
	{
		return Arr::first($this->items, $callback, $default);
	}

	/**
	 * Get the last item.
	 *
	 * @param  mixed  $default
	 * @return mixed
	 */
	public function last($default = null)
	{
		return Arr::last($this->items, null, $default);
	}

	/**
	 * Get the last item passing a given truth test.
	 *
	 * @param  callable  $callback
	 * @param  mixed  	 $default
	 * @return mixed
	 */
	public function ulast(callable $callback, $default = null)
	{
		return Arr::last($this->items, $callback, $default);
	}

	/**
	 * Merge the items with a new set.
	 *
	 * @param  array  $items
	 * @param  bool   $recursive
	 * @return $this
	 */
	public function merge($items, $recursive = false)
	{
		if(!is_null($items)){
			$func = $recursive ? 'array_merge_recursive' : 'array_merge';
			$this->items = $func($this->items, $this->castToArray($items));
		}
		return $this;
	}

	/**
	 * Returns an iterator for items.
	 *
	 * @return \ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->items);
	}

	/**
	 * Returns the number of items.
	 *
	 * @return int
	 */
	public function count()
	{
		return count($this->items);
	}

	/**
	 * Returns all the items as an array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		return $this->all();
	}

	/**
	 * Determine if the given item exists.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function offsetExists($key)
	{
		return $this->has($key);
	}

	/**
	 * Get an item's value.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function offsetGet($key)
	{
		return $this->get($key);
	}

	/**
	 * Set an item's value.
	 *
	 * @param  string  $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function offsetSet($key, $value)
	{
		if(is_null($key))
			$this->append($value);
		else
			$this->set($key, $value);
	}

	/**
	 * Unset an item.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function offsetUnset($key)
	{
		$this->remove($key);
	}

	/**
	 * Get all item keys.
	 *
	 * @return array
	 */
	public function offsets()
	{
		return $this->keys();
	}

	/**
	 * Fluently get an item's value.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->get($key);
	}

	/**
	 * Fluently set an item's value.
	 *
	 * @param  string  $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function __set($key, $value)
	{
		$this->set($key, $value);
	}

	/**
	 * Cast the given object to an array.
	 *
	 * @param  mixed  $object
	 * @return array
	 */
	protected function castToArray($object)
	{
		return Arr::cast($object);
	}

}