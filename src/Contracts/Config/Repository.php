<?php
namespace TeaPress\Contracts\Config;

use TeaPress\Contracts\Signals\Hub;

interface Repository
{


	/**
	 * Determine if the given configuration value exists.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function has($key);

	/**
	 * Get the specified configuration value.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public function get($key, $default = null);

	/**
	 * Get all of the configuration items for the application.
	 *
	 * @return array
	 */
	public function all();

	/**
	 * Set a given configuration value.
	 *
	 * @param  array|string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function set($key, $value = null);

	/**
	 * Prepend a value onto an array configuration value.
	 *
	 * @param  string  $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function prepend($key, $value);

	/**
	 * Push a value onto an array configuration value.
	 *
	 * @param  string  $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function push($key, $value);


	/**
	 * Get all of the configuration items for the application.
	 *
	 * @return array
	 */
	public function getItems($filter=false);


	/**
	 * Merge the given configuration values with the current.
	 *
	 * @param  array|TeaPress\Contracts\Config\Repository $items
	 *
	 * @return void
	 */
	public function merge($items);


	/**
	 * Set the namespace for this repository
	 *
	 * @param  string  $namespace
	 *
	 * @return void
	 */
	public function setNamespace($namespace);


	/**
	 * Get the namespace for this repository
	 *
	 * @return string
	 */
	public function getNamespace();

}