<?php

namespace TeaPress\Routing;

use Closure;
use Exception;
use LogicException;
use InvalidArgumentException;
use UnexpectedValueException;
use TeaPress\Contracts\Http\Request;
use TeaPress\Contracts\Core\Container;

class Route
{
	/**
	 * @var \TeaPress\Contracts\Core\Container
	 */
	protected $container;

	/**
	 * @var \TeaPress\Routing\Router
	 */
	protected $factory;

	/**
	 * @var string
	 */
	protected $uri;

	/**
	 * @var array
	 */
	protected $methods;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $prefix = '';

	/**
	 * @var string
	 */
	protected $namespace = '';

	/**
	 * @var \Closure|string|array
	 */
	protected $handler;

	/**
	 * @var array
	 */
	protected $parameters = [];

	/**
	 * @var array
	 */
	protected $defaults = [];

	/**
	 * @var array
	 */
	protected $middleware = [];

	/**
	 * Create a route instance
	 *
	 * @param  \TeaPress\Routing\Router	$factory
	 * @param  array					$methods
	 * @param  string 					$uri
	 * @param  string|null 				$prefix
	 * @param  string|null 				$namespace
	 *
	 * @return  void
	 */
	public function __construct(Router $factory, $methods, $uri, $prefix = null, $namespace = null)
	{
		$this->uri = $uri;
		$this->factory = $factory;
		$this->methods = (array) $methods;
		$this->prefix = (string) $prefix;
		$this->namespace = (string) $namespace;
	}

	/**
	 * Set the route's handler.
	 *
	 * @param  \Closure|string|array  $handler
	 * @param  bool  $namespace
	 * @return static
	 */
	public function handler($handler, $namespace = true)
	{
		if( $namespace && is_string($handler) && $this->namespace )
			$this->handler = trim($this->namespace, '\\').'\\'.trim($handler, '\\');
		else
			$this->handler = $handler;

		return $this;
	}

	/**
	 * Set the route's handler. Alias for handler()
	 *
	 * @param  \Closure|string|array  $handler
	 * @param  bool  $namespace
	 * @return static
	 */
	public function to($handler, $namespace = true)
	{
		return $this->handler($handler, $namespace);
	}

	/**
	 * Set the route's name.
	 *
	 * @param  string  $name
	 * @param  bool  $prefix
	 * @return static
	 */
	public function name($name, $prefix = true)
	{
		if($prefix && $this->prefix)
			$this->name = trim($this->prefix, '.').'.'.trim($name, '.');
		else
			$this->name = $name;

		$this->addNameToLookUps();

		return $this;
	}

	/**
	 * Set the route's name. Alias for name()
	 *
	 * @param  string  $name
	 * @param  bool  $prefix
	 * @return static
	 */
	public function as($name, $prefix = true)
	{
		return $this->name($name, $prefix);
	}

	/**
	 * Set a default value for the given route parameter.
	 *
	 * @param  string  $key
	 * @param  mixed  $value
	 * @return static
	 */
	public function defaults($key, $value)
	{
		$this->defaults[$key] = $value;
		return $this;
	}

	/**
	 * Get the route's HTTP verbs.
	 *
	 * @return array
	 */
	public function getMethods()
	{
		return $this->methods;
	}

	/**
	 * Get the route's URI.
	 *
	 * @return string
	 */
	public function getUri()
	{
		return $this->uri;
	}

	/**
	 * Get the route's handler.
	 *
	 * @return \Closure|string|array
	 */
	public function getHandler()
	{
		return $this->handler;
	}

	/**
	 * Get the route's name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

/** Handling Route Parameters **/

	/**
	 * Determine a given parameter exists from the route.
	 *
	 * @param  string $name
	 * @return bool
	 */
	public function hasParameter($name)
	{
		return array_key_exists($name, $this->parameters());
	}

	/**
	 * Get a given parameter from the route.
	 *
	 * @param  string  $name
	 * @param  mixed   $default
	 * @return string|object
	 */
	public function getParameter($name, $default = null)
	{
		return $this->parameter($name, $default);
	}

	/**
	 * Get a given parameter from the route.
	 *
	 * @param  string  $name
	 * @param  mixed   $default
	 * @return string|object
	 */
	public function parameter($name, $default = null)
	{
		return Arr::get($this->parameters(), $name, $default);
	}

	/**
	 * Set a parameter to the given value.
	 *
	 * @param  string  $name
	 * @param  mixed   $value
	 * @return void
	 */
	public function setParameter($name, $value)
	{
		$this->parameters();

		$this->parameters[$name] = $value;
	}

	/**
	 * Unset a parameter on the route if it is set.
	 *
	 * @param  string  $name
	 * @return void
	 */
	public function forgetParameter($name)
	{
		$this->parameters();

		unset($this->parameters[$name]);
	}

	/**
	 * Get the key / value list of parameters for the route.
	 *
	 * @return array
	 *
	 * @throws \LogicException
	 */
	public function parameters()
	{
		if (isset($this->parameters))
			return $this->parameters;

		throw new LogicException('Route is not bound.');
	}

	/**
	 * Set the IOC container instance.
	 *
	 * @param \TeaPress\Contracts\Core\Container $container
	 * @return void
	 */
	public function setContainer(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * Get the IOC container instance.
	 *
	 * @return \TeaPress\Contracts\Core\Container
	 */
	public function getContainer()
	{
		return $this->container;
	}


	/**
	 * Add the route's name to the lookup lists.
	 *
	 * @return void
	 */
	protected function addNameToLookUps()
	{

	}

	/**
	 * Replace null parameters with their defaults.
	 *
	 * @param  array  $parameters
	 * @return array
	 */
	protected function replaceDefaults(array $parameters)
	{
		foreach ($parameters as $key => &$value) {
			$value = isset($value) || !is_null($value)
						? $value : Arr::get($this->defaults, $key);
		}

		foreach ($this->defaults as $key => $value) {
			if (! isset($parameters[$key])) {
				$parameters[$key] = $value;
			}
		}

		return $parameters;
	}
/** END **/

}
