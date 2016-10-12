<?php
namespace TeaPress\Contracts\Core;


interface Manifest
{

	/**
	 * Execute the specified script
	 *
	 * @param string $file
	 *
	 * @return static
	 */
	public function compile();


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
	 * Set a given configuration value.
	 *
	 * @param  array|string  $key
	 * @param  mixed   $value
	 * @return static
	 */
	public function set($key, $value = null);

	/**
	 * Get all of the configuration items for the application.
	 *
	 * @return array
	 */
	public function getAttributes();

	/**
	 * Merge the given configuration values with the current.
	 *
	 * @param  array|TeaPress\Contracts\Core\Manifest $attributes
	 * @param  bool  $recursive
	 *
	 * @return static
	 */
	public function merge($attributes, $recursive = true);


	/**
	 * Set the application instance
	 *
	 * @param \TeaPress\Contracts\Core\Application $app
	 *
	 * @return static
	 */
	public function setApplication(AppContract $app);


	/**
	 * Get the application instance
	 *
	 * @return \TeaPress\Contracts\Core\Application
	 */
	public function getApplication();
}