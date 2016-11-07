<?php
namespace TeaPress\Contracts\Routing;

use TeaPress\Contracts\Core\Container;

interface Route
{

	/**
	 * Set the route's handler.
	 *
	 * @param  \Closure|string|array  $handler
	 * @param  bool  $namespace
	 * @return static
	 */
	public function handler($handler, $namespace = true);


	/**
	 * Set the route's name.
	 *
	 * @param  string  $name
	 * @param  bool  $prefix
	 * @return static
	 */
	public function name($name, $prefix = true);

	/**
	 * Set a default value for the given route parameter.
	 *
	 * @param  string  $key
	 * @param  mixed  $value
	 * @return static
	 */
	public function defaults($key, $value);


	/**
	 * Get the route's HTTP verbs.
	 *
	 * @return array
	 */
	public function getMethods();

	/**
	 * Get the route's URI.
	 *
	 * @return string
	 */
	public function getUri();

	/**
	 * Get the route's handler.
	 *
	 * @return \Closure|string|array
	 */
	public function getHandler();

	/**
	 * Get the route's name.
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Get a given parameter from the route.
	 *
	 * @param  string  $name
	 * @param  mixed   $default
	 * @return string|object
	 */
	public function getParameter($name, $default = null);

	/**
	 * Set a parameter to the given value.
	 *
	 * @param  string  $name
	 * @param  mixed   $value
	 * @return void
	 */
	public function setParameter($name, $value);

	/**
	 * Get the key / value list of parameters for the route.
	 *
	 * @return array
	 *
	 * @throws \LogicException
	 */
	public function parameters();

}