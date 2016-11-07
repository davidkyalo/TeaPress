<?php
namespace TeaPress\Contracts\Routing;

interface Registrar
{
	/**
	 * Register a new HEAD route with the router.
	 *
	 * @param  string  $uri
	 * @param  \Closure|array|string  $handler
	 *
	 * @return \TeaPress\Contracts\Routing\Route|null
	 */
	public function head($uri, $handler = null);

	/**
	 * Register a new GET route with the router.
	 *
	 * @param  string  $uri
	 * @param  \Closure|array|string  $handler
	 * @return \TeaPress\Contracts\Routing\Route|null
	 */
	public function get($uri, $handler = null);

	/**
	 * Register a new POST route with the router.
	 *
	 * @param  string  $uri
	 * @param  \Closure|array|string  $handler
	 * @return \TeaPress\Contracts\Routing\Route|null
	 */
	public function post($uri, $handler = null);

	/**
	 * Register a new PUT route with the router.
	 *
	 * @param  string  $uri
	 * @param  \Closure|array|string  $handler
	 * @return \TeaPress\Contracts\Routing\Route|null
	 */
	public function put($uri, $handler = null);

	/**
	 * Register a new DELETE route with the router.
	 *
	 * @param  string  $uri
	 * @param  \Closure|array|string  $handler
	 * @return \TeaPress\Contracts\Routing\Route|null
	 */
	public function delete($uri, $handler = null);

	/**
	 * Register a new PATCH route with the router.
	 *
	 * @param  string  $uri
	 * @param  \Closure|array|string  $handler
	 * @return \TeaPress\Contracts\Routing\Route|null
	 */
	public function patch($uri, $handler = null);

	/**
	 * Register a new OPTIONS route with the router.
	 *
	 * @param  string  $uri
	 * @param  \Closure|array|string  $handler
	 * @return \TeaPress\Contracts\Routing\Route|null
	 */
	public function options($uri, $handler = null);

	/**
	 * Register a new route with the given verbs.
	 *
	 * @param  array|string  $methods
	 * @param  string  $uri
	 * @param  \Closure|array|string  $handler
	 * @return \TeaPress\Contracts\Routing\Route|null
	 */
	public function match($methods, $uri, $handler = null);

	/**
	 * Create a route group with shared attributes.
	 *
	 * @param  array|string     $attributes
	 * @param  callable  $callback
	 * @return void
	 */
	public function group($attributes, callable $callback);


}