<?php

namespace TeaPress\Http\Routing;

use Closure;
use Exception;
use LogicException;
use TeaPress\Http\Request;
use TeaPress\Events\Dispatcher;
use InvalidArgumentException;
use UnexpectedValueException;
use TeaPress\Events\EmitterTrait;
use TeaPress\Exceptions\BadMethodCall;
use TeaPress\Exceptions\AttributeError;
use Illuminate\Container\Container;
use TeaPress\Http\Response\Factory as ResponseFactory;

/**
 *
 */
class Route implements EmitterInterface
{

	use EmitterTrait;

	/**
	 * @var array
	 */
	protected $actionDefaults = ['uses' => null, 'as' => null, 'before' => [], 'after' => []];

	/**
	 * @var \Illuminate\Container\Container
	 */
	protected $container;

	/**
	 * @var string
	 */
	protected $responses;

	/**
	 * @var array
	 */
	protected $parameters;

	/**
	 * @var array
	 */
	protected $action = ['uses' => null, 'as' => null];


	/**
	 * @var array
	 */
	protected $defaults = [];

	/**
	 * @var string
	 */
	protected $uri;

	/**
	 * @var bool
	 */
	protected $registered = false;

	/**
	 * @var array
	 */
	protected $dirty = [];

	/**
	 * @param	$methods
	 * @param	$uri
	 * @param	$action
	 */
	public function __construct($methods, $uri, $action)
	{
		$this->methods = (array) $methods;
		$this->setUri($uri);
		$this->setAction( $action );

		// if (in_array('GET', $this->methods) && ! in_array('HEAD', $this->methods))
		// 	$this->methods[] = 'HEAD';

	}

	/**
	 * Handles the route.
	 */
	public function run(Request $request, ResponseFactory $responses)
	{
		$response = $this->container->call(
			$this->uses,
			array_merge(['container' => $this->container], $this->parameters)
			);

		$response = $this->responses->cast($response);
		if(!$response)
			throw new Exception('Unknown response type!');

		return $response;
	}

	/**
	 * Get all route parameters.
	 *
	 * @param  string  $name
	 * @param  mixed   $default
	 * @return string|object
	 */
	public function parameters()
	{
		if(!is_null($this->parameters))
			return $this->parameters;

		throw new LogicException("Route parameters not registered.");

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
	 * Set a default value for the route.
	 *
	 * @param  string  $key
	 * @param  mixed  $value
	 * @return $this
	 */
	public function registerParameters(array $parameters)
	{
		return $this->parameters = $this->replaceDefaults($parameters);
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

	/**
	 * Set a default value for the route.
	 *
	 * @param  string  $key
	 * @param  mixed  $value
	 * @return $this
	 */
	public function defaults($key, $value)
	{
		$this->defaults[$key] = $value;
		return $this;
	}

	/**
	* Add a prefix to the route URI.
	*
	* @param  string  $prefix
	* @return $this
	*/
	public function prefix($prefix)
	{
		if($prefix)
			$this->setUri( join_paths($prefix, $this->uri) );
		return $this;
	}

	/**
	* Add a namespace to the route handler.
	*
	* @param  string  $namespace
	* @return $this
	*/
	public function namespace($namespace)
	{
		if($namespace && is_string($this->getHandler()))
			$this->setHandler( join_paths([ $namespace, $this->getHandler()], '\\') );
		return $this;
	}

	public function isDirty($key = null)
	{
		return (is_null($key) && !empty($this->dirty)) || in_array($key, $this->dirty);
	}

	public function getDirty($key = null, $default = null)
	{
		return Arr::get($this->dirty, $key, $default);
	}

	public function clearDirty($keys)
	{
		if($keys === true)
			$this->dirty = [];
		else
			Arr::forget($this->dirty, $keys);

		return $this;
	}


	/**
	* @param \Illuminate\Container\Container $container
	*
	*/
	public function setContainer(Container $container)
	{
		$this->container = $container;
		return $this;
	}

	public function setRegistered($registered  = true)
	{
		$this->registered = $registered;
		return $this;
	}

	public function setAction($action)
	{
		$this->action = $this->getActionDefaults();

		$action = $this->parseAction($action);

		$this->setHandler($action['uses']);
		$this->setName($action['as']);

		if(isset($action['prefix']))
			$this->prefix($action['prefix']);

		if(isset($action['namespace']))
			$this->prefix($action['namespace']);

		if(isset($action['before']))
			$this->before($action['before']);

		if(isset($action['after']))
			$this->after($action['after']);

		$this->action = array_merge_recursive($this->action, Arr::except($action, ['uses', 'as', 'before', 'after']));

		return $this;
	}

	public function setUri($uri)
	{
		if($this->registered)
			$this->dirty['uri'] = $this->uri;

		$this->uri = ltrim($uri, '/');
		return $this;
	}

	public function setMethods($methods)
	{
		if($this->registered)
			$this->dirty['methods'] = $this->methods;

		$this->methods = (array) $methods;

		return $this;
	}

	public function setName($name)
	{
		if($this->registered)
			$this->dirty['name'] = $this->action['as'];

		$this->action['as'] = $name;

		return $this;
	}

	public function setHandler($handler)
	{
		if(!$this->validateHandler($handler))
			return;

		if($this->registered)
			$this->dirty['controller'] = $this->getController();

		$this->action['uses'] = $handler;

		return $this;
	}

	public function getMethods()
	{
		return $this->methods;
	}

	public function getUri()
	{
		return $this->uri;
	}

	public function getHandler()
	{
		return $this->action['uses'];
	}

	public function getName()
	{
		return $this->action['as'];
	}

	public function getController()
	{
		return $this->handlerReferencesController() ? $this->getHandler() : null;
	}

	protected function handlerReferencesController()
	{
		$handler = func_num_args() === 0 ? $this->getHandler() : func_get_args()[0];
		return is_string($handler); //&& !function_exists($handler);
	}


	/**
	 * Parse the route action into a standard array.
	 *
	 * @param  callable|array  $action
	 * @return array
	 *
	 * @throws \UnexpectedValueException
	 */
	protected function parseAction($action)
	{
		// If the action is already a Closure instance, we will just set that instance
		// as the "uses" property, because there is nothing else we need to do when
		// it is available. Otherwise we will need to find it in the action list.
		if (is_callable($action)) {
			$action = ['uses' => $action];
		}

		// If no "uses" property has been set, we will dig through the array to find a
		// Closure instance within this list. We will set the first Closure we come
		// across into the "uses" property that will get fired off by this route.
		elseif (!isset($action['uses'])) {
			$action['uses'] = isset($action['controller'])
						? $action['controller']
						: $this->findCallable($action);
		}

		if( !isset($action['as']) )
			$action['as'] = null;

		return $action;
	}

	/**
	 * Check if route handler is valid
	 *
	 * @param  callable|array|string  $handler
	 * @return bool
	 *
	 * @throws \UnexpectedValueException
	 */
	protected function validateHandler($handler, $silent = false){
		if(is_string($handler) && !Str::contains($handler, '@') && !is_callable($handler)) {
			if($silent)
				return false;

			throw new UnexpectedValueException(sprintf('Invalid route action: [%s]', $handler));
		}
		return true;
	}

	/**
	 * Find the callable in an action array.
	 *
	 * @param  array  $action
	 * @return callable
	 */
	protected function findCallable(array $action)
	{
		return Arr::first($action, function ($key, $value) {
			return is_callable($value) && is_numeric($key);
		});
	}

	/**
	* Get default attributes for the action array.
	*
	* @return array
	*/
	public function getActionDefaults(){
		return $this->actionDefaults;
	}


/* Filter Methods */


	/**
	 * Add before filters to the route.
	 *
	 * @param  string  $filters
	 * @return $this
	 *
	 */
	public function before($filters)
	{
		return $this->addFilters('before', $filters);
	}

	/**
	 * Add after filters to the route.
	 *
	 * @param  string  $filters
	 * @return $this
	 *
	 */
	public function after($filters)
	{
		return $this->addFilters('after', $filters);
	}

	/**
	 * Add the given filters to the route by type.
	 *
	 * @param  string  $type
	 * @param  string  $filters
	 * @return $this
	 */
	protected function addFilters($type, $filters)
	{
		$filters = static::explodeFilters($filters);

		if (isset($this->action[$type])) {
			$existing = static::explodeFilters($this->action[$type]);

			$this->action[$type] = array_merge($existing, $filters);
		} else {
			$this->action[$type] = $filters;
		}

		return $this;
	}


	/**
	 * Get the "before" filters for the route.
	 *
	 * @return array
	 *
	 */
	public function beforeFilters()
	{
		if (! isset($this->action['before'])) {
			return [];
		}

		return $this->parseFilters($this->action['before']);
	}

	/**
	 * Get the "after" filters for the route.
	 *
	 * @return array
	 *
	 */
	public function afterFilters()
	{
		if (! isset($this->action['after'])) {
			return [];
		}

		return $this->parseFilters($this->action['after']);
	}

	/**
	 * Parse the given filter string.
	 *
	 * @param  string  $filters
	 * @return array
	 *
	 */
	public static function parseFilters($filters)
	{
		return Arr::build(static::explodeFilters($filters), function ($key, $value) {
			return Route::parseFilter($value);
		});
	}

	/**
	 * Turn the filters into an array if they aren't already.
	 *
	 * @param  array|string  $filters
	 * @return array
	 */
	protected static function explodeFilters($filters)
	{
		if (is_array($filters)) {
			return static::explodeArrayFilters($filters);
		}

		return array_map('trim', explode('|', $filters));
	}

	/**
	 * Flatten out an array of filter declarations.
	 *
	 * @param  array  $filters
	 * @return array
	 */
	protected static function explodeArrayFilters(array $filters)
	{
		$results = [];

		foreach ($filters as $filter) {
			$results = array_merge($results, array_map('trim', explode('|', $filter)));
		}

		return $results;
	}

	/**
	 * Parse the given filter into name and parameters.
	 *
	 * @param  string  $filter
	 * @return array
	 *
	 */
	public static function parseFilter($filter)
	{
		if (! Str::contains($filter, ':')) {
			return [$filter, []];
		}

		return static::parseParameterFilter($filter);
	}

	/**
	 * Parse a filter with parameters.
	 *
	 * @param  string  $filter
	 * @return array
	 */
	protected static function parseParameterFilter($filter)
	{
		list($name, $parameters) = explode(':', $filter, 2);

		return [$name, explode(',', $parameters)];
	}

/* End Filter Methods */

	public function __get($key){
		switch ($key) {
			case 'methods':
			case 'method':
				return $this->getMethods();
				break;

			case 'uri':
				return $this->getUri();
				break;

			case 'uses':
			case 'handler':
				return $this->getHandler();
				break;

			case 'name':
				return $this->getName();
				break;

			case 'controller':
				return $this->getController();
				break;

			case 'action':
				return $this->action;
				break;

			default:
				throw AttributeError::notFound($key, $this);
				break;
		}
	}

	public function __call($method, $args)
	{
		$arg = count($args) === 0 ? null : $args[0];

		switch ($method) {
			case 'methods':
			case 'method':
				$this->setMethods($arg);
				break;

			case 'uri':
				$this->setUri($arg);
				break;

			case 'uses':
			case 'handler':
			case 'controller':
				$this->setHandler($arg);
				break;

			case 'as':
			case 'name':
				$this->setName($arg);
				break;

			default:
				throw new BadMethodCall($method, $this);
				break;
		}

		return $this;
	}

}
