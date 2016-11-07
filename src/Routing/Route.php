<?php

namespace TeaPress\Routing;

use Closure;
use Exception;
use LogicException;
use ReflectionFunction;
use TeaPress\Utils\Str;
use TeaPress\Utils\Arr;
use TeaPress\Core\Container;
use InvalidArgumentException;
use UnexpectedValueException;
use TeaPress\Contracts\Http\Request;
use TeaPress\Routing\Matching\Matchable;
use TeaPress\Contracts\Utils\Actionable;
use TeaPress\Contracts\Routing\Route as Contract;
use Illuminate\Http\Exception\HttpResponseException;
use TeaPress\Contracts\Core\Container as ContainerContract;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Route implements Contract, Matchable
{
	use RouteDependencyResolverTrait;

	/**
	 * @var \TeaPress\Routing\UriParserInterface
	 */
	protected $parser;

	/**
	 * @var \TeaPress\Contracts\Http\Request
	 */
	protected $request;

	/**
	 * @var string
	 */
	protected $uri;

	/**
	 * @var string
	 */
	protected $matchedUri;

	/**
	 * @var array
	 */
	protected $parsed = null;

	/**
	 * @var array
	 */
	protected $possibleUris = [];

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
	protected $parameters;

	/**
	 * @var array
	 */
	protected $defaults = [];

	/**
	 * @var array
	 */
	protected $wheres = [];

	/**
	 * @var array
	 */
	protected $placeholders = [];

	/**
	 * @var array
	 */
	protected $middleware = [];

	/**
	 * Create a route instance
	 *
	 * @param  array					$methods
	 * @param  string 					$uri
	 * @param  string|null 				$prefix
	 * @param  string|null 				$namespace
	 *
	 * @return  void
	 */
	public function __construct($methods, $uri, $prefix = null, $namespace = null)
	{
		$this->uri = $uri;
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
		if( $namespace && $this->namespace && $this->handlerReferencesController($handler) )
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
	 * Set a regular expression requirement on the route.
	 *
	 * @param  array|string  $name
	 * @param  string  $expression
	 * @return static
	 */
	public function where($name, $expression = null)
	{
		$wheres = is_array($name) ? $name : [$name => $expression];

		foreach ($wheres as $name => $expression) {
			if(!$expression){
				throw new InvalidArgumentException("Route's where clause should be a valid regular expression pattern.");
			}

			$this->wheres[$name] = $expression;
		}

		$this->flushCompiled();

		return $this;
	}

	/**
	 * Set the route's middleware.
	 *
	 * @param  array|string(s)  ...$middleware
	 * @return static
	 */
	public function middleware(...$middleware)
	{
		if(count($middleware) === 1 && is_array($middleware[0]))
			$middleware = $middleware[0];

		$this->middleware = Router::mergeMiddleware( $this->middleware, $middleware );

		return $this;
	}

	/**
	 * Bind the route's parameters.
	 *
	 * @param  array  $parameters
	 * @return static
	 */
	public function bindParameters(array $parameters)
	{
		$parameters = $this->replaceDefaults($parameters);

		list($named, $anonymous) = $this->parseParameters( $parameters );

		$this->parameters = array_merge($named, $anonymous);

		return $this;
	}

	/**
	 * Run the route action and return the response.
	 *
	 * @param  \TeaPress\Http\Request  $request
	 * @return mixed
	 */
	public function run(Request $request)
	{
		$this->container = $this->container ?: new Container;

		try {

			if( $this->handlerReferencesController($this->handler) ){
				return $this->runController($request);
			}

			return $this->runCallable($request);

		} catch (HttpResponseException $e) {
			return $e->getResponse();
		}
	}

	/**
	 * Run the route action and return the response.
	 *
	 * @param  \TeaPress\Http\Request  $request
	 *
	 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 * @return mixed
	 */
	protected function runCallable(Request $request)
	{
		if(is_array($this->handler))
			$parameters = $this->resolveClassMethodDependencies(
					$this->parametersWithoutNulls(), $this->handler[0], $this->handler[1]
				);
		else
			$parameters = $this->resolveMethodDependencies(
					$this->parametersWithoutNulls(), new ReflectionFunction($this->handler)
				);

		if (is_array($this->handler) && !method_exists($this->handler[0], $this->handler[1]) ) {
			throw new NotFoundHttpException;
		}

		return call_user_func_array($this->handler, $parameters);
	}

	/**
	 * Run the route action and return the response.
	 *
	 * @param  \TeaPress\Http\Request  $request
	 * @return mixed
	 *
	 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 */
	protected function runController(Request $request)
	{
		list($class, $method) = array_pad(explode('@', $this->handler), 2, 'dispatch');

		$parameters = $this->resolveClassMethodDependencies(
				$this->parametersWithoutNulls(), $class, $method
			);

		$instance = $this->container->make($class);

		if (!method_exists($instance, $method)) {
			if($instance instanceof Actionable)
				return $instance->missingAction($request, $method, $parameters);
			else
				throw new NotFoundHttpException;
		}

		if($instance instanceof Actionable){
			$newParameters = $instance->beforeAction($request, $method, $parameters);
			if(!is_null($newParameters)){
				$parameters = $newParameters;
			}
		}

		$response = call_user_func_array([$instance, $method], $parameters);

		if($instance instanceof Actionable){
			$newResponse = $instance->afterAction($response, $request, $method, $parameters);
			if(!is_null($newResponse)){
				$response = $newResponse;
			}
		}

		return $response;
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
	 * Get the route's URI path.
	 *
	 * @return string
	 */
	public function getPath()
	{
		return Str::begin(Str::rstrip($this->uri, '/'), '/');
	}

	/**
	 * Get the route's parsed URI info.
	 *
	 * @return array
	 */
	public function getUriInfo()
	{
		return $this->parsed();
	}

	/**
	 * Get the route's possible URIs.
	 *
	 * @return array
	 */
	public function getPossible()
	{
		return array_keys($this->parsed());
	}

	/**
	 * Get placeholders (names) for all parameters defined on the URI.
	 *
	 * @return array
	 */
	public function getPlaceholders()
	{
		$this->parsed();

		return array_keys($this->placeholders);
	}

	/**
	 * Get regex patterns for parameters defined on the URI.
	 * If placeholder is given, the pattern for that placeholder or null is returned.
	 *
	 * @return array|string|null
	 */
	public function getPatterns($placeholder = null)
	{
		$this->parsed();

		if(is_null($placeholder))
			return $this->placeholders;

		return isset($this->placeholders[$placeholder]) ? $this->placeholders[$placeholder] : null;
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
	 * Get the route's controller or null if the route is not bound to a controller.
	 *
	 * @return string|null
	 */
	public function getController()
	{
		return $this->handlerReferencesController($this->handler) ? $this->handler : null;
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

	/**
	 * Get the route's middleware.
	 *
	 * @return array
	 */
	public function getMiddleware()
	{
		return $this->middleware;
	}

	/**
	 * Get the route's where clauses.
	 *
	 * @return array
	 */
	public function getWhereClauses()
	{
		return $this->wheres;
	}

	/**
	 * Get the matched URI for the route.
	 *
	 * @return string|null
	 */
	public function getMatchedUri()
	{
		return $this->matchedUri;
	}

	/**
	 * Get the route's possible URI segments.
	 *
	 * @return array
	 */
	public function parsed()
	{
		if(is_null($this->parsed)){
			$this->parseUri();
		}

		return $this->parsed;
	}

/** Handling Route Parameters **/

	/**
	 * Get the key / value list of parameters for the route.
	 *
	 * @return array
	 *
	 * @throws \LogicException
	 */
	public function parameters()
	{
		if (isset($this->parameters)){
			return $this->parameters;
		}

		throw new LogicException('Route is not bound.');
	}

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
	 * Get the key / value list of parameters without null values.
	 *
	 * @return array
	 */
	public function parametersWithoutNulls()
	{
		return array_filter($this->parameters(), function ($p) {
			return ! is_null($p);
		});
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
	 * Extract the named and anonymous parameters from the provided.
	 *
	 * @param  array  $parameters
	 * @return array
	 */
	protected function parseParameters(array $parameters = [])
	{
		$named = [];
		$anonymous = [];

		foreach ($parameters as $key => $value) {
			if(is_numeric($key) && (string) intval($key) === (string) $key)
				$anonymous[(int) $key] = $value;
			else
				$named[$key] = $value;
		}

		ksort($anonymous);

		return [$named, $anonymous];
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
			$value = isset($value) ? $value : Arr::get($this->defaults, $key);
		}

		foreach ($this->defaults as $key => $value) {
			if (! isset($parameters[$key])) {
				$parameters[$key] = $value;
			}
		}

		return $parameters;
	}


/** END **/


	/**
	 * Set the matched URI for the route.
	 *
	 * @param  string $uri
	 * @return void
	 */
	public function setMatchedUri($uri)
	{
		$this->matchedUri = $uri;

		return $this;
	}

	/**
	 * Set the uri parser instance.
	 *
	 * @param \TeaPress\Routing\UriParserInterface $parser
	 * @return static
	 */
	public function setParser(UriParserInterface $parser)
	{
		$this->parser = $parser;

		return $this;
	}

	/**
	 * Parse the route's uri into an array of possible uri segments.
	 *
	 * @return void
	 */
	protected function parseUri()
	{
		if(!$this->parser){
			throw new LogicException("Route has no URI parser.");
		}

		$parsed = $this->parser->parse( $this->getPath(), $this->wheres );

		$this->parsed = [];
		$this->placeholders = [];

		foreach ($parsed as $segments) {

			$paths = [];
			foreach ($segments as $segment) {

				if(is_array($segment))
					$this->placeholders[$segment[0]] = $segment[1];

				$paths[] = is_array($segment) ? '{'.$segment[0].'}' : $segment;

			}

			$this->parsed[join_uris($paths)] = $segments;
		}

	}

	/**
	 * Cleared the parsed URI(s).
	 *
	 * @return void
	 */
	protected function flushCompiled()
	{
		$this->parsed = null;
		$this->placeholders = [];
	}

	/**
	 * Determine if the given hander is a controller.
	 *
	 * @param  mixed  $handler
	 * @return bool
	 */
	protected function handlerReferencesController($handler)
	{
		// return Router::handlerReferencesController($handler);
		return $this->isCallableClassMethod($handler, false);
	}


}
