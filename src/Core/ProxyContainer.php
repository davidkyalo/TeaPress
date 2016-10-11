<?php
namespace TeaPress\Core;

use Closure;
use ArrayAccess;
use TeaPress\Contracts\Core\Container;
use TeaPress\Contracts\Core\ProxyContainer as Contract;


abstract class ProxyContainer implements Contract, ArrayAccess
{
	protected $container;

	public function setContainer(Container $container)
	{
		$this->container = $container;
	}


	public function getContainer()
	{
		return $this->container;
	}

	/**
	 * Determine if the given abstract type has been bound.
	 *
	 * @param  string  $abstract
	 * @return bool
	 */
	public function bound($abstract)
	{
		return $this->container->bound($abstract);
	}

	/**
	 * Alias a type to a different name.
	 *
	 * @param  string  $abstract
	 * @param  string  $alias
	 * @return void
	 */
	public function alias($abstract, $alias)
	{
		return $this->container->alias($abstract, $alias);
	}

	/**
	 * Assign a set of tags to a given binding.
	 *
	 * @param  array|string  $abstracts
	 * @param  array|mixed   ...$tags
	 * @return void
	 */
	public function tag($abstracts, $tags)
	{
		return $this->container->tag($abstracts, $tags);
	}

	/**
	 * Resolve all of the bindings for a given tag.
	 *
	 * @param  array  $tag
	 * @return array
	 */
	public function tagged($tag)
	{
		return $this->container->tagged($tag);
	}

	/**
	 * Register a binding with the container.
	 *
	 * @param  string|array  $abstract
	 * @param  \Closure|string|null  $concrete
	 * @param  bool  $shared
	 * @return void
	 */
	public function bind($abstract, $concrete = null, $shared = false)
	{
		return $this->container->bind($abstract, $concrete, $shared );
	}

	/**
	 * Register a binding if it hasn't already been registered.
	 *
	 * @param  string  $abstract
	 * @param  \Closure|string|null  $concrete
	 * @param  bool  $shared
	 * @return void
	 */
	public function bindIf($abstract, $concrete = null, $shared = false)
	{
		return $this->container->bindIf($abstract, $concrete, $shared);
	}

	/**
	 * Register a shared binding in the container.
	 *
	 * @param  string|array  $abstract
	 * @param  \Closure|string|null  $concrete
	 * @return void
	 */
	public function singleton($abstract, $concrete = null)
	{
		return $this->container->singleton($abstract, $concrete);
	}

	/**
	 * "Extend" an abstract type in the container.
	 *
	 * @param  string    $abstract
	 * @param  \Closure  $closure
	 * @return void
	 *
	 * @throws \InvalidArgumentException
	 */
	public function extend($abstract, Closure $closure)
	{
		return $this->container->extend($abstract, $closure);
	}

	/**
	 * Register an existing instance as shared in the container.
	 *
	 * @param  string  $abstract
	 * @param  mixed   $instance
	 * @return void
	 */
	public function instance($abstract, $instance)
	{
		return $this->container->instance($abstract, $instance);
	}


	/**
	 * Resolve the given type from the container.
	 *
	 * @param  string  $abstract
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function make($abstract, array $parameters = [])
	{
		return $this->container->make($abstract, $parameters);
	}

	/**
	 * Call the given Closure / class@method and inject its dependencies.
	 *
	 * @param  callable|string  $callback
	 * @param  array  $parameters
	 * @param  string|null  $defaultMethod
	 * @return mixed
	 */
	public function call($callback, array $parameters = [], $defaultMethod = null)
	{
		return $this->container->call($callback, $parameters, $defaultMethod);
	}

	/**
	 * Determine if the given abstract type has been resolved.
	 *
	 * @param  string $abstract
	 * @return bool
	 */
	public function resolved($abstract)
	{
		return $this->container->resolved($abstract);
	}

	/**
	 * Register a new resolving callback.
	 *
	 * @param  string    $abstract
	 * @param  \Closure|null  $callback
	 * @return void
	 */
	public function resolving($abstract, Closure $callback = null)
	{
		return $this->container->resolving($abstract, $callback);
	}

	/**
	 * Register a new after resolving callback.
	 *
	 * @param  string    $abstract
	 * @param  \Closure|null  $callback
	 * @return void
	 */
	public function afterResolving($abstract, Closure $callback = null)
	{
		return $this->container->afterResolving($abstract, $callback);
	}


	/**
	 * Determine if a given offset exists.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function offsetExists($key)
	{
		return $this->container->bound($key);
	}

	/**
	 * Get the value at a given offset.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function offsetGet($key)
	{
		return $this->container->make($key);
	}

	/**
	 * Set the value at a given offset.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function offsetSet($key, $value)
	{
		$this->container[$key] = $value;
	}

	/**
	* Unset the value at a given offset.
	*
	* @param  string  $key
	* @return void
	*/
	public function offsetUnset($key)
	{
		unset($this->container[$key]);
	}

	/**
	 * Dynamically access container services.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->container[$key];
	}

	/**
	 * Dynamically set container services.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function __set($key, $value)
	{
		$this->container[$key] = $value;
	}

	public function __call($method, $parameters)
	{
		return call_user_func_array([ $this->container, $method ], $parameters);
	}


	// /**
	//  * Set the globally available instance of the container.
	//  *
	//  * @return static
	//  */
	// public static function getInstance()
	// {
	// 	if (is_null(static::$instance))
	// 		static::$instance = new static;

	// 	return static::$instance;
	// }

	// /**
	//  * Set the shared instance of the container.
	//  *
	//  * @param  \Illuminate\Contracts\Container\Container  $container
	//  * @return void
	//  */
	// public static function setInstance(Container $container)
	// {
	// 	static::$instance = $container;
	// }
}