<?php
namespace TeaPress\Routing;

use Closure;
use TeaPress\Utils\Str;
use TeaPress\Utils\Arr;
use InvalidArgumentException;
use Illuminate\Http\Response;
use Illuminate\Pipeline\Pipeline;
use TeaPress\Contracts\Http\Request;
use TeaPress\Contracts\Core\Container;
use TeaPress\Routing\Error\RoutingError;
use TeaPress\Contracts\Signals\Hub as Signals;
use TeaPress\Contracts\Routing\Router as Contract;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use TeaPress\Contracts\Routing\RouteCollector as RouteCollectorContract;

class Router implements Contract
{

	/**
	 * Http verbs supported by the router.
	 *
	 * @var array
	 */
	public static $verbs = ['HEAD', 'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];

	/**
	 * The route collector instance.
	 *
	 * @var \TeaPress\Contracts\Routing\RouteCollector
	 */
	protected $routes;

	/**
	 * The IOC container instance.
	 *
	 * @var \TeaPress\Contracts\Core\Container
	 */
	protected $container;

	/**
	 * The signals hub instance.
	 *
	 * @var \TeaPress\Contracts\Signals\Hub
	 */
	protected $signals;

	/**
	 * The UriParser instance.
	 *
	 * @var \TeaPress\Routing\UriParserInterface
	 */
	protected $parser;

	/**
	 * The request currently being dispatched.
	 *
	 * @var \TeaPress\Contracts\Http\Request
	 */
	protected $currentRequest;

	/**
	 * The currently dispatched route instance.
	 *
	 * @var \TeaPress\Routing\Route
	 */
	protected $current;

	/**
	 * Registered routes grouped by http method.
	 *
	 * @var array
	 */
	protected $routesByMethod = [];

	/**
	 * Middleware names.
	 *
	 * @var array
	 */
	protected $middlewareNames = [];

	/**
	 * Middleware for all routes.
	 *
	 * @var array
	 */
	protected $middleware = [];

	/**
	 * Where clauses for all routes.
	 *
	 * @var array
	 */
	protected $patterns = [];

	/**
	 * All the named routes.
	 *
	 * @var array
	 */
	protected $groupStack = [];

	/**
	 * Adds the action hooks for WordPress.
	 *
	 * @param \TeaPress\Contracts\Routing\RouteCollector 	$routes
	 * @param \TeaPress\Routing\UriParserInterface 			$parser
	 * @param \TeaPress\Contracts\Core\Container 			$container
	 * @param \TeaPress\Contracts\Signals\Hub 				$signals
	 */
	public function __construct(RouteCollectorContract $routes, UriParserInterface $parser, Container $container, Signals $signals)
	{
		$this->routes = $routes;
		$this->parser = $parser;
		$this->container = $container;
		$this->signals = $signals;
	}

/** Methods for adding routes. **/

	/**
	 * Register a new HEAD route with the router.
	 *
	 * @param  string  $uri
	 * @param  \Closure|array|string|null  $handler
	 *
	 * @return \TeaPress\Routing\Route
	 */
	public function head($uri, $handler = null)
	{
		return $this->addRoute('HEAD', $uri, $handler);
	}

	/**
	 * Register a new GET route with the router.
	 *
	 * @param  string  $uri
	 * @param  \Closure|array|string|null  $handler
	 *
	 * @return \TeaPress\Routing\Route
	 */
	public function get($uri, $handler = null)
	{
		return $this->addRoute('GET', $uri, $handler);
	}

	/**
	 * Register a new POST route with the router.
	 *
	 * @param  string  $uri
	 * @param  \Closure|array|string|null  $handler
	 *
	 * @return \TeaPress\Routing\Route
	 */
	public function post($uri, $handler = null)
	{
		return $this->addRoute('POST', $uri, $handler);
	}

	/**
	 * Register a new PUT route with the router.
	 *
	 * @param  string  $uri
	 * @param  \Closure|array|string|null  $handler
	 *
	 * @return \TeaPress\Routing\Route
	 */
	public function put($uri, $handler = null)
	{
		return $this->addRoute('PUT', $uri, $handler);
	}

	/**
	 * Register a new PATCH route with the router.
	 *
	 * @param  string  $uri
	 * @param  \Closure|array|string|null  $handler
	 *
	 * @return \TeaPress\Routing\Route
	 */
	public function patch($uri, $handler = null)
	{
		return $this->addRoute('PATCH', $uri, $handler);
	}

	/**
	 * Register a new DELETE route with the router.
	 *
	 * @param  string  $uri
	 * @param  \Closure|array|string|null  $handler
	 *
	 * @return \TeaPress\Routing\Route
	 */
	public function delete($uri, $handler = null)
	{
		return $this->addRoute('DELETE', $uri, $handler);
	}

	/**
	 * Register a new OPTIONS route with the router.
	 *
	 * @param  string  $uri
	 * @param  \Closure|array|string|null  $handler
	 *
	 * @return \TeaPress\Routing\Route
	 */
	public function options($uri, $handler = null)
	{
		return $this->addRoute('OPTIONS', $uri, $handler);
	}

	/**
	 * Register a new route responding to all verbs.
	 *
	 * @param string						$uri
	 * @param \Closure|array|string|null	$handler
	 *
	 * @return \TeaPress\Routing\Route
	 */
	public function any($uri, $handler = null)
	{
		return $this->addRoute(static::$verbs, $uri, $handler);
	}

	/**
	 * Register a new route with the given verbs.
	 *
	 * @param  array|string  $methods
	 * @param  string  $uri
	 * @param  \Closure|array|string|null  $handler
	 *
	 * @return \TeaPress\Routing\Route
	 */
	public function match($methods, $uri, $handler = null)
	{
		return $this->addRoute($methods, $uri, $handler);
	}

	/**
	 * Create a group of routes with shared attributes.
	 * If attributes is a string, it will be used as the group's uri prefix.
	 *
	 * @param  array|string     $attributes
	 * @param  callable  $callback
	 * @return void
	 */
	public function group($attributes, callable $callback)
	{
		if(is_string($attributes))
			$attributes = ['prefix' => $attributes];

		$this->updateGroupStack($attributes);

		// Once we have updated the group stack, we will execute the user Closure and
		// merge in the groups attributes when the route is created. After we have
		// run the callback, we will pop the attributes off of this group stack.
		call_user_func($callback, $this);

		array_pop($this->groupStack);
	}

/** End **/

	/**
	 * Add global middleware for all routes.
	 *
	 * @param  array|string(s)  ...$middleware
	 * @return static
	 */
	public function middleware(...$middleware)
	{
		if(count($middleware) === 1 && is_array($middleware[0]))
			$middleware = $middleware[0];

		$this->middleware = $this->mergeMiddleware($this->middleware, $middleware);

		return $this;
	}

	/**
	 * Register a middleware class with a name.
	 *
	 * @param  string|array  $class
	 * @param  string  $name
	 * @return $thid
	 */
	public function registerMiddleware($class, $name = null)
	{
		if(is_array($class)){
			foreach ($class as $name => $cls) {
				$name = is_string($name) ? $name : null;
				$this->registerMiddleware($cls, $name);
			}
		}
		else{
			$this->middlewareNames[($name ?: $class)] = $class;
		}

		return $this;
	}

	/**
	 * Set a global regex pattern on all routes.
	 *
	 * @param  string  $key
	 * @param  string  $pattern
	 * @return static
	 */
	public function pattern($key, $pattern)
	{
		$this->patterns[$key] = $pattern;

		return $this;
	}

	/**
	 * Set a group of global regex patterns on all routes.
	 *
	 * @param  array  $patterns
	 * @return static
	 */
	public function patterns(array $patterns)
	{
		foreach ($patterns as $key => $pattern) {
			$this->pattern($key, $pattern);
		}

		return $this;
	}


	/**
	 * Register a new route.
	 *
	 * @param  array|string 				$methods
	 * @param  string  						$uri
	 * @param  \Closure|array|string|null 	$handler
	 *
	 * @return \TeaPress\Routing\Route
	 */
	protected function addRoute($methods, $uri, $handler = null)
	{
		$route = $this->createRoute($methods, $uri, $handler);

		$this->routes->add($route);

		return $route;
	}

	/**
	 * Create a new route instance.
	 *
	 * @param  array|string 				$methods
	 * @param  string  						$uri
	 * @param  \Closure|array|string|null 	$handler
	 *
	 * @return \TeaPress\Routing\Route
	 */
	protected function createRoute($methods, $uri, $handler = null)
	{
		$route = $this->newRoute(
			$this->parseHttpMethods($methods),
			$this->prefixRouteUri($uri),
			$this->getGroupName(),
			$this->getGroupNamespace()
		);

		list($handler, $name) = $this->extractHandlerName($handler);

		if($handler)
			$route->handler($handler);
		elseif ($handler = $this->getGroupHandler())
			$route->handler($handler, false);

		if($middleware = $this->getGroupMiddleware())
			$route->middleware($middleware);

		$route->where($this->getInheritedWhereClauses());

		return $name ? $route->name($name) : $route;
	}

	/**
	 * Create a Route object.
	 *
	 * @param  array					$methods
	 * @param  string 					$uri
	 * @param  string|null 				$prefix
	 * @param  string|null 				$namespace
	 *
	 * @return \TeaPress\Routing\Route
	 */
	protected function newRoute($methods, $uri, $prefix = null, $namespace = null)
	{
		return (new Route($methods, $uri, $prefix, $namespace))
					->setContainer($this->container)
					->setParser($this->parser);
	}

	/**
	 * Parse the given HTTP verbs to uppercase.
	 *
	 * @param array|string $methods
	 * @return array
	 */
	protected function parseHttpMethods($methods)
	{
		return array_map('strtoupper', (array) $methods);
	}

	/**
	 * Extract a route's name from it's handler argument.
	 *
	 * @param  mixed $hander
	 * @return array
	 */
	protected function extractHandlerName($handler)
	{
		if(!is_array($handler) || is_callable($handler, true))
			return [$handler, null];

		$name = isset($handler['as']) ? $handler['as'] : null;

		if(isset($handler['to']))
			return [$handler['to'], $name];

		elseif(isset($handler['handler']))
			return [$handler['handler'], $name];

		unset($handler['as']);

		return [ array_pop($handler), $name ];
	}


	/**
	 * Get where clauses a route should inherit from the current global and group clauses.
	 *
	 * @return array
	 */
	protected function getInheritedWhereClauses()
	{
		return array_merge($this->patterns, $this->getGroupWhereClauses());
	}

	/**
	 * Update the group stack with the given attributes.
	 *
	 * @param  array  $attributes
	 * @return void
	 */
	protected function updateGroupStack(array $attributes)
	{
		$this->groupStack[] = $this->mergeGroup($attributes, $this->currentGroup());
	}

	/**
	 * Merge the given group attributes.
	 *
	 * @param  array  $new
	 * @param  array  $old
	 * @return array
	 */
	public static function mergeGroup($new, $old)
	{
		$new['handler'] = static::findGroupHandler($new, $old);

		unset($new['to']);

		$new['middleware'] = static::formatGroupMiddleware($new, $old);

		$new['where'] = static::formatGroupWheres($new, $old);

		if(empty($old))
			return $new;

		$new['namespace'] = static::formatGroupNamespace($new, $old);

		$new['prefix'] = static::formatGroupPrefix($new, $old);

		$new['as'] = static::formatGroupName($new, $old);

		$old = Arr::except($old, ['namespace', 'prefix', 'as', 'handler', 'where', 'middleware']);

		return array_merge_recursive($old, $new);
	}

	/**
	 * Format the namespace for the new group attributes.
	 *
	 * @param  array  $new
	 * @param  array  $old
	 * @return string|null
	 */
	public static function formatGroupNamespace($new, $old)
	{
		$newSpace = isset($new['namespace']) ? $new['namespace'] : '';
		$oldSpace = isset($old['namespace']) ? $old['namespace'] : '';

		if($newSpace)
			return $oldSpace ? trim($oldSpace, '\\').'\\'.trim($newSpace, '\\') : trim($newSpace, '\\');
		else
			return $oldSpace;
	}

	/**
	 * Format the prefix for the new group attributes.
	 *
	 * @param  array  $new
	 * @param  array  $old
	 * @return string|null
	 */
	public static function formatGroupPrefix($new, $old)
	{
		$newPrefix = isset($new['prefix']) ? $new['prefix'] : '';
		$oldPrefix = isset($old['prefix']) ? $old['prefix'] : '';

		return join_uris($oldPrefix, $newPrefix);
	}

	/**
	 * Format the name for the new group attributes.
	 *
	 * @param  array  $new
	 * @param  array  $old
	 * @return string|null
	 */
	public static function formatGroupName($new, $old)
	{
		$newName = isset($new['as']) ? $new['as'] : '';
		$oldName = isset($old['as']) ? $old['as'] : '';

		if($newName)
			return $oldName ? trim($oldName, '.').'.'.trim($newName, '.') : trim($newName, '.');
		else
			return $oldName;
	}

	/**
	 * Format the handler for the new group attributes.
	 *
	 * @param  array  $new
	 * @param  array  $old
	 * @return string|null
	 */
	public static function findGroupHandler($new, $old)
	{
		if(isset($new['to']))
			return $new['to'];
		elseif(isset($new['handler']))
			return $new['handler'];
		else
			return isset($old['handler'])
					? $old['handler']
					: null; //(isset($old['to']) ? $old['to'] : null);
	}

	/**
	 * Format the where clauses for the new group attributes.
	 *
	 * @param  array  $new
	 * @param  array  $old
	 * @return array
	 */
	public static function formatGroupWheres($new, $old)
	{
		$newClauses = isset($new['where']) ? $new['where'] : [];
		$oldClauses = isset($old['where']) ? $old['where'] : [];

		return array_merge($oldClauses, $newClauses);
	}

	/**
	 * Format the middlewares for the new group attributes.
	 *
	 * @param  array  $new
	 * @param  array  $old
	 * @return array
	 */
	public static function formatGroupMiddleware($new, $old)
	{
		$newWares = isset($new['middleware']) ? $new['middleware'] : [];
		$oldWares = isset($old['middleware']) ? $old['middleware'] : [];

		return static::mergeMiddleware( $oldWares, $newWares );
	}

	/**
	 * Format the middlewares for the new group attributes.
	 *
	 * @param  array  $old
	 * @param  array  $new
	 * @return array
	 */
	public static function mergeMiddleware($old, $new)
	{
		$merged = array_merge(static::explodeMiddleware($old, false), static::explodeMiddleware($new));

		return array_unique( $merged );
	}

	/**
	 * Parse the given middleware into an array.
	 *
	 * @param  string|array  $middleware
	 * @return array
	 */
	public static function explodeMiddleware($middleware, $explodeArray = true)
	{
		if(is_array($middleware)){
			return $explodeArray ? static::explodeArrayMiddleware($middleware) : $middleware;
		}

		return Arr::build(explode('|', $middleware), function($key, $value){
			$value = trim($value);
			return $value ? [$key, $value] : null;
		});

	}

	/**
	 * Parse the given middleware into an array.
	 *
	 * @param  array  $middlewares
	 * @return array
	 */
	public static function explodeArrayMiddleware(array $middlewares)
	{
		$results = [];

		foreach ($middlewares as $middleware) {
			$results = array_merge($results, static::explodeMiddleware($middleware, false));
		}

		return $results;
	}

	/**
	 * Determine if the given hander is a controller.
	 *
	 * @param  mixed  $handler
	 * @return bool
	 */
	public static function handlerReferencesController($handler)
	{
		return is_string($handler) && !is_callable($handler);
	}

	/**
	 * Determine if the given object is a RoutingError instance.
	 *
	 * @param  mixed  $object
	 * @return bool
	 */
	public static function isRoutingError($object)
	{
		return ($object instanceof RoutingError);
	}


	/**
	 * Determine if the router currently has a group stack.
	 *
	 * @return bool
	 */
	public function hasGroupStack()
	{
		return !empty($this->groupStack);
	}

	/**
	 * Get the group stack.
	 *
	 * @return array
	 */
	public function getGroupStack()
	{
		return $this->groupStack;
	}

	/**
	 * Get the current group's attributes.
	 *
	 * @return array
	 */
	public function currentGroup()
	{
		return $this->hasGroupStack() ? end($this->groupStack) : [];
	}

	/**
	 * Prefix the given uri with the current group's uri.
	 *
	 * @param  string $uri
	 * @return string
	 */
	protected function prefixRouteUri($uri)
	{
		return join_uris( $this->getGroupUri(), $uri);
	}

	/**
	 * Get the route group uri from the group stack.
	 *
	 * @return string
	 */
	protected function getGroupUri()
	{
		return Arr::get($this->currentGroup(), 'prefix', '');
	}

	/**
	 * Get the route group name from the group stack.
	 *
	 * @return string
	 */
	protected function getGroupName()
	{
		return Arr::get($this->currentGroup(), 'as', '');
	}

	/**
	 * Get the route group namespace from the group stack.
	 *
	 * @return string
	 */
	protected function getGroupNamespace()
	{
		return Arr::get($this->currentGroup(), 'namespace', '');
	}

	/**
	 * Get the route group handler.
	 *
	 * @return null|mixed
	 */
	protected function getGroupHandler()
	{
		return Arr::get($this->currentGroup(), 'handler');
	}

	/**
	 * Get the route group's where clauses.
	 *
	 * @return array
	 */
	protected function getGroupWhereClauses()
	{
		return Arr::get($this->currentGroup(), 'where', []);
	}

	/**
	 * Get the route group middleware.
	 *
	 * @return array
	 */
	protected function getGroupMiddleware()
	{
		return Arr::get($this->currentGroup(), 'middleware', []);
	}

	/**
	 * Get the routable URI from the given request.
	 * If request is not provided, the current request (if set) is used.
	 *
	 * @param  \TeaPress\Contracts\Http\Request|null $request
	 * @return string|null
	 */
	public function requestUri(Request $request = null)
	{
		$request = $request ?: $this->currentRequest;
		return $request
				? rawurldecode(Str::begin($request->fullPath(), '/'))
				: null;
	}

	/**
	 * Get the current request.
	 *
	 * @return \TeaPress\Contracts\Http\Request
	 */
	public function currentRequest()
	{
		return $this->currentRequest;
	}

	/**
	 * Get registered routes.
	 * If method is false (default), returns a single array with all routes.
	 * If true (bool) is given, returns a multidimensional array of routes grouped by HTTP method.
	 * If a string is given, returns an array of routes for that HTTP method.
	 *
	 * @param string|bool|null $method
	 * @return array
	 */
	public function getRoutes($method = false)
	{
		if($method === false)
			return $this->routes->getRoutes();
		else
			return $this->routes->get( ($method === true ? null : $method) );
	}


	/**
	 * Dispatch the request to the application.
	 *
	 * @param \TeaPress\Contracts\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function dispatch(Request $request)
	{
		$this->currentRequest = $request;

		$uri = $this->requestUri($request);

		$response = $this->fireEvent("dispatching {$uri}", [$request], true);

		if (is_null($response)){
			$response = $this->dispatchToRoute($request);
		}

		$response = $this->isRoutingError($response)
					? $response : $this->prepareResponse($response, $request);

		$this->fireEvent("dispatched {$uri}", [$request, $response]);

		return $response;
	}

	/**
	 * Dispatch the current request to it's matching route and return the response.
	 *
	 * @return mixed
	 */
	protected function dispatchToRoute(Request $request)
	{
		$uri = $this->requestUri($request);

		$route = $this->setCurrentRoute(
						$this->findRoute($request->getMethod(), $uri)
					);

		if( $this->isRoutingError($route) ){
			return $route;
		}

		$response = $this->callRouteBefore($route, $request);

		if( is_null($response) ){
			$response = $this->runRouteWithinStack($route, $request);
		}

		$response = $this->getResponse($response);

		$this->callRouteAfter($route, $request, $response);

		return $response;
	}

	/**
	 * Find the route matching a given request.
	 *
	 * @param  string  $httpVerb
	 * @param  string  $uri
	 * @return \TeaPress\Routing\Route|\TeaPress\Routing\Error\RoutingError
	 */
	protected function findRoute($httpVerb, $uri)
	{
		return $this->routes->match($httpVerb, $uri);
	}

	/**
	 * Sets the current route and binds it to the container and current request.
	 *
	 * @param  \TeaPress\Routing\Route|\TeaPress\Routing\Error\RoutingError $route
	 * @return \TeaPress\Routing\Route|\TeaPress\Routing\Error\RoutingError
	 */
	protected function setCurrentRoute($route)
	{
		$this->current = $route;

		$this->currentRequest->setRouteResolver(function() use ($route)
		{
			return $route;
		});

		$this->container->instance('TeaPress\Routing\Route', $route);

		return $route instanceof Route ? $route->setContainer($this->container) : $route;
	}

	/**
	 * Run the given route within a Stack "onion" instance.
	 *
	 * @param  \TeaPress\Routing\Route  $route
	 * @param  \TeaPress\Http\Request  $request
	 * @return mixed
	 */
	protected function runRouteWithinStack(Route $route, Request $request)
	{
		$middleware = $this->gatherRouteMiddlewares($route, $request);

		if(empty($middleware))
			$response = $route->run($request);
		else
			$response = (new Pipeline($this->container))
							->send($request)
							->through($middleware)
							->then(function ($request) use ($route) {
								return $route->run($request);
							});

		return $this->getResponse($response);
	}


	/**
	 * Gather the middleware for the given route.
	 *
	 * @param  \TeaPress\Routing\Route  $route
	 * @param  \TeaPress\Http\Request  $request
	 * @return array
	 */
	public function gatherRouteMiddlewares(Route $route, Request $request = null)
	{
		$names = array_merge($this->middleware, $route->getMiddleware());

		$middleware = [];
		foreach (array_unique($names) as $name) {
			$middleware[$name] = $this->resolveMiddlewareClassName($name);
		}

		return $this->applyMiddlewareFilters($middleware, $route, $request);
	}


	/**
	 * Get the class name for the given middleware.
	 *
	 * @param  string  $name
	 * @return string
	 */
	public function resolveMiddlewareClassName($name)
	{
		return isset($this->middlewareNames[$name])
					? $this->middlewareNames[$name]
					: $name;
	}

	/**
	 * Create a response instance from the given value.
	 *
	 * @param  mixed  $response
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function prepareResponse($response, $request)
	{
		return $this->getResponse($response)->prepare($request);
	}

	/**
	 * Get/Create a response instance from the given value.
	 * If the given value is already a valid response instance,
	 * it is returned as it is.
	 *
	 * @param  mixed  $response
	 * @param  bool  $force
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function getResponse($response)
	{
		if($response instanceof SymfonyResponse)
			return $response;

		if ($response instanceof PsrResponseInterface)
			return (new HttpFoundationFactory)->createResponse($response);

		return new Response($response);
	}


	/**
	* Bind the given callback to a dispatching event.
	*
	* @param  string					$uri
	* @param  \Closure|array|string 	$callback
	* @param  int						$priority
	*
	* @return static
	*/
	public function dispatching($uri, $callback, $priority = null)
	{
		// $uri = $uri !== '*' ? Str::begin($uri, '/'): $uri;
		$uri = Str::begin($uri, '/');

		$this->bindEventCallback("dispatching {$uri}", $callback, $priority);

		return $this;
	}

	/**
	* Bind the given callback to a dispatched event.
	*
	* @param  string					$uri
	* @param  \Closure|array|string 	$callback
	* @param  int						$priority
	*
	* @return static
	*/
	public function dispatched($uri, $callback, $priority = null)
	{
		$uri = Str::begin($uri, '/');

		$this->bindEventCallback("dispatching {$uri}", $callback, $priority);

		return $this;
	}


	/**
	* Bind the given callback to the before event.
	* This event fires before the route for the given URI is run.
	*
	* @param  string					$uri
	* @param  \Closure|array|string 	$callback
	* @param  int						$priority
	*
	* @return static
	*/
	public function before($uri, $callback, $priority = null)
	{
		$uri = Str::begin($uri, '/');

		$this->bindEventCallback("before.uri {$uri}", $callback, $priority);

		return $this;
	}

	/**
	* Bind the given callback to the after event.
	* This event fires after the route for the given URI is run.
	*
	* @param  string					$uri
	* @param  \Closure|array|string 	$callback
	* @param  int						$priority
	*
	* @return static
	*/
	public function after($uri, $callback, $priority = null)
	{
		$uri = Str::begin($uri, '/');

		$this->bindEventCallback("after.uri {$uri}", $callback, $priority);

		return $this;
	}


	/**
	* Bind the given callback to the routing event for the given route.
	*
	* @param  string					$routeName
	* @param  \Closure|array|string 	$callback
	* @param  int						$priority
	*
	* @return static
	*/
	public function routing($routeName, $callback, $priority = null)
	{
		$this->bindEventCallback("before.route {$routeName}", $callback, $priority);

		return $this;
	}

	/**
	* Bind the given callback to the routed event for the given route.
	*
	* @param  string					$routeName
	* @param  \Closure|array|string 	$callback
	* @param  int						$priority
	*
	* @return static
	*/
	public function routed($routeName, $callback, $priority = null)
	{
		$this->bindEventCallback("after.route {$routeName}", $callback, $priority);

		return $this;
	}

	/**
	* Add a middleware filter callback.
	*
	* @param  \Closure|array|string 	$callback
	* @param  int						$priority
	*
	* @return static
	*/
	public function middlewareFilter($callback, $priority = null)
	{
		$this->bindEventCallback("middleware", $callback, $priority);

		return $this;
	}

	/**
	* Apply middleware filters.
	*
	* @param  \Closure|array|string 			$callback
	* @param  \TeaPress\Routing\Route   		$route
	* @param  \TeaPress\Contracts\Http\Request 	$request
	* @return array
	*/
	protected function applyMiddlewareFilters(array $middleware, Route $route = null, Request $request = null)
	{
		$tag = $this->routerEventTag('middleware');

		$middleware = $this->signals->filter($tag, $middleware, $route, $request, $this);

		return is_array($middleware) ? $middleware : [];
	}


	/**
	 * Call the given route's before filters.
	 *
	 * @param  \TeaPress\Routing\Route  $route
	 * @param  \TeaPress\Http\Request   $request
	 * @return mixed
	 */
	public function callRouteBefore($route, $request)
	{
		$response = $this->callRouteUriBefore($route, $request);

		return $response ?: $this->callRouteNameBefore($route, $request);
	}

	/**
	 * Call the URI pattern based filters for the request.
	 *
	 * @param  \TeaPress\Routing\Route  $route
	 * @param  \TeaPress\Http\Request   $request
	 * @return mixed
	 */
	protected function callRouteUriBefore($route, $request)
	{
		$uri = $this->requestUri($request);
		return $this->fireEvent( "before.uri {$uri}", [$route, $request], true );
	}

	/**
	 * Call the route name based filters for the request.
	 *
	 * @param  \TeaPress\Routing\Route  $route
	 * @param  \TeaPress\Http\Request   $request
	 * @return mixed
	 */
	protected function callRouteNameBefore($route, $request)
	{
		if($name = $route->getName())
			return $this->fireEvent( "before.route {$name}", [$route, $request], true );
	}

	/**
	 * Call the given route's after listeners.
	 *
	 * @param  \TeaPress\Routing\Route 						$route
	 * @param  \TeaPress\Http\Request 						$request
	 * @param  \Symfony\Component\HttpFoundation\Response   $response
	 * @return void
	 */
	public function callRouteAfter($route, $request, $response)
	{
		$this->callRouteNameAfter($route, $request, $response);
		$this->callRouteUriAfter($route, $request, $response);
	}

	/**
	 * Call the given request's URI pattern based after listeners.
	 *
	 * @param  \TeaPress\Routing\Route 						$route
	 * @param  \TeaPress\Http\Request 						$request
	 * @param  \Symfony\Component\HttpFoundation\Response   $response
	 * @return void
	 */
	public function callRouteUriAfter($route, $request, $response)
	{
		$uri = $this->requestUri($request);
		$this->fireEvent( "after.uri {$uri}", [$route, $request, $response]);
	}

	/**
	 * Call the given request's route name based after listeners.
	 *
	 * @param  \TeaPress\Routing\Route 						$route
	 * @param  \TeaPress\Http\Request 						$request
	 * @param  \Symfony\Component\HttpFoundation\Response   $response
	 * @return void
	 */
	public function callRouteNameAfter($route, $request, $response)
	{
		if($name = $route->getName())
			$this->fireEvent( "after.route {$name}", [$route, $request, $response]);
	}


	/**
	* Get a complete router's event tag.
	*
	* @param string $event
	*
	* @return string|array
	*/
	public function routerEventTag($event)
	{
		return [ Contract::class, $event ];
	}

	/**
	* Bind the given callback to the specified $event.
	*
	* @param  string					$event
	* @param  \Closure|array|string 	$callback
	* @param  int						$priority
	*
	* @return bool
	*/
	protected function bindEventCallback($event, $callback, $priority = null)
	{
		return $this->signals->bind($this->routerEventTag($event), $callback, $priority);
	}


	/**
	* Call the a router's event.
	*
	* @param  string  $event
	* @param  array   $payload
	* @param  bool    $halt
	*
	* @return mixed
	*/
	protected function fireEvent($event, $payload = [], $halt = false)
	{
		$method = $halt ? 'until' : 'fire';

		return $this->signals->$method($this->routerEventTag($event), (array) $payload);
	}
}
