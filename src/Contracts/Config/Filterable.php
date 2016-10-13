<?php
namespace TeaPress\Contracts\Config;

use TeaPress\Contracts\Signals\Hub;

interface Filterable
{

	/**
	 * Get the signals hub instance.
	 *
	 * @return \TeaPress\Contracts\Signals\Hub
	 */
	public function getSignals();


	/**
	 * Set the signals hub instance.
	 *
	 * @param  \TeaPress\Contracts\Signals\Hub  $key
	 *
	 * @return void
	 */
	public function setSignals(Hub $signals);


	/**
	* Bind a config value filter. The provided callback will be executed every time the an attempt to get the value is made.
	*
	* @param  string  $key
	* @param  \Closure|array|string  $callback
	* @param  int|null  $priority
	*
	* @return bool
	*/
	public function filter($key, $callback, $priority = null);


	/**
	* Determine if the given key has filters. If a key is not specified, returns an array of filtered keys
	*
	* @param  string|null  $key
	*
	* @return bool|array
	*/
	public function filtered($key=null);


	/**
	 * Get the specified configuration value.
	 *
	 * @param  string 	$key
	 *
	 * @return string|array
	 */
	public function getFilterTag($key);
}