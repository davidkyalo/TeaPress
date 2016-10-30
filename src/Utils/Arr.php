<?php

namespace TeaPress\Utils;

use IteratorAggregate;
use Illuminate\Support\Arr as BaseArr;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use TeaPress\Contracts\Utils\ArrayBehavior;
use Illuminate\Support\Collection as IlluminateCollection;


class Arr extends BaseArr
{

	/**
	 * Determine if the given array contains any of the given keys.
	 *
	 * @param  array   $array
	 * @param  string|array  $keys
	 * @param  string  $notation='.'
	 *
	 * @return bool
	 */
	public static function any($array, $keys, $notation = NOTHING)
	{
		foreach ( (array) $keys as $key) {
			if(static::has($array, $key, $notation))
				return true;
		}
		return false;
	}

	/**
	 * Add an element to an array using the given notation if it doesn't exist.
	 * Uses the "dot" notation by default.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $value
	 * @param  string  $notation='.'
	 * @return array
	 */
	public static function add($array, $key, $value, $notation = NOTHING)
	{
		if (is_null(static::get($array, $key, null, $notation))) {
			static::set($array, $key, $value, $notation);
		}

		return $array;
	}

	/**
	 * Flatten a multi-dimensional associative array with dots or the given notation.
	 * If assoc_only=true, none associative arrays will be treated as values and won't be flattened.
	 *
	 * @param  array    $array
	 * @param  bool 	$assoc_only = true
	 * @param  int 		$depth = 0
	 * @param  string  	$notation='.'
	 *
	 * @return array
	 */
	public static function dot($array, $assoc_only = true, $depth = 0, $notation = '.', $prepend = '', $level = 0)
	{
		if($assoc_only && is_array($array) && !static::isAssoc($array))
			return $array;

		$results = [];

		foreach ($array as $key => $value) {
			if ( is_array($value) && (!$depth || $depth > $level)  && (!$assoc_only || static::isAssoc($value)) )
				$results = array_merge(
						$results,
						static::dot($value, $assoc_only, $depth, $notation, $prepend.$key.$notation, $level+1)
					);
			else
				$results[$prepend.$key] = $value;

		}

		return $results;
	}

	/**
	 * Get an item from a multi dimensional array using the given notation and pass it through the value() function.
	 * If the item is a closure, it's return value will be returned.
	 * Uses the "dot" notation by default.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $default
	 * @param  string  $notation='.'
	 *
	 * @return mixed
	 */
	public static function eval($array, $key=null, $default = null, $notation = '.')
	{
		return value(static::get($array, $key, $default, $notation));
	}

	/**
	 * Merge the given items into the nested child of the given multi-dimensional array.
	 *
	 * @param  array   $array
	 * @param  string|array  $key 	A string key using "dot" notation eg. root.target
	 * 								Or an array of ['key', 'notation'] for different notation eg. [ 'root->target', '->' ]
	 * @param  array  ...$items
	 *
	 * @return array
	 */
	public static function extendDeep(&$array, $key, array ...$items)
	{
		$target = (array) static::get($array, $key, []);

		foreach ($items as &$item) {
			$item = static::cast($item);
		}

		return static::set($array, $key, array_merge_recursive( $target, ...$items) );
	}

	/**
	 * Merge the given items into the nested child of the given multi-dimensional array.
	 *
	 * @param  array   $array
	 * @param  string|array  $key 	A string key using "dot" notation eg. root.target
	 * 								Or an array of ['key', 'notation'] for different notation eg. [ 'root->target', '->' ]
	 * @param  arrays  ...$items
	 *
	 * @return array
	 */
	public static function extend(&$array, $key, array ...$items)
	{
		$target = (array) static::get($array, $key, []);

		foreach ($items as &$item) {
			$item = static::cast($item);
		}

		return static::set($array, $key, array_merge( $target, ...$items) );
	}

	/**
	 * Return the first element in an array passing a given truth test.
	 * If a callback is not given, the first element of the array is returned.
	 *
	 * @param  array  $array
	 * @param  callable|null  $callback
	 * @param  mixed  $default
	 * @return mixed
	 */
	public static function first($array, callable $callback = null, $default = null)
	{
		if (is_null($callback)) {
			if (count($array) === 0) {
				return value($default);
			}

			foreach ($array as $item) {
				return $item;
			}
		}

		foreach ($array as $key => $value) {
			if (call_user_func($callback, $key, $value)) {
				return $value;
			}
		}

		return value($default);
	}

	/**
	 * Return the last element in an array passing a given truth test.
	 * If a callback is not given, the last element of the array is returned.
	 *
	 * @param  array  $array
	 * @param  callable|null  $callback
	 * @param  mixed  $default
	 * @return mixed
	 */
	public static function last($array, callable $callback = null, $default = null)
	{
		if(is_null($callback)){
			return count($array) > 0 ? end($array) : value($default);
		}

		return static::first(array_reverse($array), $callback, $default);
	}


	/**
	 * Flatten a multi-dimensional array into a single level.
	 *
	 * @param  array  $array
	 * @param  int 	  $depth = 0
	 *
	 * @return array
	 */
	public static function flatten($array, $depth = 0, $level = 0)
	{
		$results = [];

		foreach ($array as $key => $value) {
			if ( (is_array($value) || ($value instanceof IteratorAggregate) ) && (!$depth || $depth > $level))
				$results = array_merge( $results, static::flatten($value, $depth, $level+1) );
			else
				$results[] = $value;

		}
		return $results;
	}


	/**
	 * Remove one or many array items from a given array using using the given notation.
	 * Uses the "dot" notation by default.
	 *
	 * @param  array  $array
	 * @param  array|string  $keys
	 * @param  string  $notation
	 * @return void
	 */
	public static function forget(&$array, $keys, $notation = '.')
	{
		$original = &$array;

		$keys = (array) $keys;

		if (count($keys) === 0) {
			return;
		}

		foreach ($keys as $key) {
			$parts = explode($notation, $key);

			while (count($parts) > 1) {
				$part = array_shift($parts);

				if (isset($array[$part]) && is_array($array[$part])) {
					$array = &$array[$part];
				} else {
					$parts = [];
				}
			}

			unset($array[array_shift($parts)]);

			// clean up after each pass
			$array = &$original;
		}
	}

	/**
	 * Get an item from a multi dimentional array using the given notation.
	 * Uses the "dot" notation by default.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $default
	 * @param  string  $notation='.'
	 * @return mixed
	 */
	public static function get($array, $key, $default = null, $notation = NOTHING)
	{
		static::warnDepreciatedNotationArg(__METHOD__, $notation, $key);

		if(empty($array))
			return value($default);

		list($key, $notation) = static::parseKey($key, $notation);

		if (is_null($key))
			return $array;

		if (isset($array[$key]))
			return $array[$key];

		foreach (explode($notation, $key) as $segment) {
			if (! is_array($array) || !array_key_exists($segment, $array))
				return value($default);
			$array = $array[$segment];
		}

		return $array;
	}

	/**
	 * ALIAS of eval()
	 * Get an item from a multi dimentional array using the given notation and pass it through the value() function.
	 * If the item is a closure, it's return value will be returned.
	 * Uses the "dot" notation by default.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $default
	 * @param  string  $notation='.'
	 *
	 * @return mixed
	 */
	public static function getValue($array, $key, $default = null, $notation = '.')
	{
		return static::eval($array, $key, $default, $notation);
	}

	/**
	 * Check if an item exists in an array using the given notation.
	 * Uses the "dot" notation by default.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  string  $notation='.'
	 * @return bool
	 */
	public static function has($array, $key, $notation = NOTHING)
	{
		// static::warnDepreciatedNotationArg(__METHOD__, $notation, $key);
		list($key, $notation) = static::parseKey($key, $notation);

		if (empty($array) || is_null($key))
			return false;

		if (isset($array[$key]))
			return true;

		foreach (explode($notation, $key) as $segment) {
			if (! is_array($array) || ! array_key_exists($segment, $array))
				return false;

			$array = $array[$segment];
		}

		return true;
	}


	/**
	 * Get a value from the array using the given notation, and remove it.
	 * Uses the "dot" notation by default.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $default
	 * @param  string  $notation='.'
	 * @return mixed
	 */
	public static function pull(&$array, $key, $default = null, $notation = NOTHING)
	{
		static::warnDepreciatedNotationArg(__METHOD__, $notation, $key);

		list($key, $notation) = $notated = static::parseKey($key, $notation);

		$value = static::get($array, $notated, $default);

		static::forget($array, $key, $notation);

		return $value;
	}


	/**
	 * Add the given items onto the a nested array.
	 * If an index is not specified, the item will be added into the beginning of the array.
	 *
	 * @param  array  $array
	 * @param  mixed  $key
	 * @param  int|null    $index
	 * @param  mixed  $items
	 *
	 * @return array
	 */
	public static function put(&$array, $key, $index=null, ...$items)
	{
		$index = is_null($index) ? 0 : (int) $index;

		$target = (array) static::get($array, $key, []);

		$target = array_merge( array_slice($target, 0, $index), $items, array_slice($target, $index) );

		return static::set($array, $key, $target);
	}


	/**
	 * Add the non-existing items onto the a nested array.
	 * If an index is not specified, the item will be added into the beginning of the array.
	 *
	 * @param  array  $array
	 * @param  mixed  $key
	 * @param  int|null    $index
	 * @param  mixed  $items
	 *
	 * @return array
	 */
	public static function putUnique(&$array, $key, $index=null, ...$items)
	{
		$index = is_null($index) ? 0 : (int) $index;

		$target = (array) static::get($array, $key, []);

		$filter = function($value) use ($target){
			return !in_array($value, $target);
		};

		// $target = array_merge( array_slice($target, 0, $index), array_diff($items, $target), array_slice($target, $index) );
		$target = array_merge( array_slice($target, 0, $index), array_filter($items, $filter), array_slice($target, $index) );

		return static::set($array, $key, $target);
	}

	/**
	 * Appends only the non existing items to the end nested $array[$key] array in the multi-dimensional $array.
	 * If the target array is not set, an empty one is created.
	 *
	 * @param  array   $array 			The root array
	 * @param  string  $key 			Key to the nested array in "dot" or given notation
	 * @param  mixed   $items 			The items to append
	 *
	 * @return array
	 */
	public static function pushUnique(&$array, $key, ...$items)
	{
		$target = (array) static::get($array, $key, []);

		$compare = function($a, $b){
			if( $a === $b )
				return 0;
			elseif( $a > $b )
				return 1;
			elseif ($a < $b)
				return -1;
		};

		$filter = function($value) use ($target){
			return !in_array($value, $target);
		};

		// return static::set($array, $key, array_merge( $target, array_udiff($items, $target, $compare) ) );

		return static::set($array, $key, array_merge( $target, array_filter($items, $filter) ) );
	}


	/**
	 * Appends a value to the nested $array[$key] array in the multi-dimensional $array.
	 * If the target array is not set, an empty one is created.
	 *
	 * @param  array   $array 			The root array
	 * @param  string  $key 			Key to the nested array in "dot" or given notation
	 * @param  mixed   $items 			The items to append
	 *
	 * @return array
	 */
	public static function push(&$array, $key, ...$items)
	{
		return static::extend($array, $key, $items);
	}


	/**
	 * Set an array item to a given value using the given notation.
	 * Uses the "dot" notation by default.
	 * If no key is given to the method, the entire array will be replaced.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $value
	 * @param  string  $notation='.'
	 * @return array
	 */
	public static function set(&$array, $key, $value, $notation = NOTHING)
	{
		static::warnDepreciatedNotationArg(__METHOD__, $notation, $key);
		list($key, $notation) = static::parseKey($key, $notation);

		if (is_null($key)) {
			return $array = $value;
		}

		$keys = explode($notation, $key);

		while (count($keys) > 1) {
			$key = array_shift($keys);

			// If the key doesn't exist at this depth, we will just create an empty array
			// to hold the next value, allowing us to create the arrays to hold final
			// values at the correct depth. Then we'll keep digging into the array.
			if (! isset($array[$key]) || ! is_array($array[$key])) {
				$array[$key] = [];
			}

			$array = &$array[$key];
		}

		$array[array_shift($keys)] = $value;

		return $array;
	}


	/**
	 * Try casting the given object to an array
	 *
	 * @param  mixed  $object
	 * @param  bool   $force
	 *
	 * @return array|false
	 */
	public static function cast($object, $force = true)
	{
		if(is_array($object)){
			return $object;
		}
		elseif ($object instanceof IlluminateCollection){
			return $object->all();
		}
		elseif ($object instanceof IteratorAggregate){
			$array = [];
			foreach ($object as $key => $value)
				$array[$key] = $value;

			return $array;
		}
		elseif($object instanceof ArrayBehavior){
			$array = [];
			foreach ($object->offsets() as $offset)
				$array[$offset] = $object[$offset];

			return $array;
		}
		elseif ($object instanceof Arrayable){
			return $object->toArray();
		}
		elseif ($force && ($object instanceof Jsonable)){
			return json_decode($object->toJson(), true);
		}

		return $force ? (array) $object : false;
	}

	protected static function parseKey($key, $notation = '.')
	{
		if($notation === NOTHING)
			$notation = '.';

		return is_array($key) && count($key) === 2 ? $key : [$key, $notation];
	}

	protected static function warnDepreciatedNotationArg($method, $notation, $key, $default = NOTHING)
	{
		if($notation === $default)
			return;

		$msg ='Key ['.$key.']. Pass the notation with the key as an array ["key","notation"] instead. Eg. ["root.array->child->target", "->"] to use "->" as the notation.';
		_deprecated_argument($method, '0.1.0', $msg);
	}

}

