<?php
namespace TeaPress\Routing;

use Closure;
use TeaPress\Utils\Arr;
use InvalidArgumentException;
use TeaPress\Contracts\Http\Request;
use TeaPress\Contracts\Core\Container;
use Symfony\Component\HttpFoundation\Response;
use TeaPress\Contracts\Signals\Hub as Signals;

// use TeaPress\Http\Response\Factory as ResponseFactory;
// use TeaPress\Exceptions\HttpErrorException;

class Router
{

	/**
	 * Http verbs supported by the router.
	 *
	 * @var array
	 */
	public static $verbs = ['HEAD', 'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];

	/**
	 * The IOC container instance.
	 *
	 * @var \TeaPress\Contracts\Core\Container
	 */
	protected $app;

	/**
	 * The signals hub instance.
	 *
	 * @var \TeaPress\Contracts\Signals\Hub
	 */
	protected $signals;

	/**
	 * The request currently being dispatched.
	 *
	 * @var \TeaPress\Contracts\Http\Request
	 */
	protected $request;

	/**
	 * The currently dispatched route instance.
	 *
	 * @var \TeaPress\Routing\Route
	 */
	protected $current;

	/**
	 * All registered routes.
	 *
	 * @var array
	 */
	protected $routes = [];

	/**
	 * All the named routes.
	 *
	 * @var array
	 */
	protected $namedRoutes = [];

	/**
	 * Middleware for all routes.
	 *
	 * @var array
	 */
	protected $middleware = [];

	/**
	 * All the named routes.
	 *
	 * @var array
	 */
	protected $groupStack = [];

	/**
	 * Adds the action hooks for WordPress.
	 *
	 * @param \TeaPress\Contracts\Core\Container 	$app
	 * @param \TeaPress\Contracts\Signals\Hub 		$signals
	 */
	public function __construct(Container $app, Signals $signals)
	{
		$this->app = $app;
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
	 *
	 * @param  array     $attributes
	 * @param  callable  $callback
	 * @return void
	 */
	public function group(array $attributes, callable $callback)
	{
		$this->updateGroupStack($attributes);

		// Once we have updated the group stack, we will execute the user Closure and
		// merge in the groups attributes when the route is created. After we have
		// run the callback, we will pop the attributes off of this group stack.
		call_user_func($callback, $this);

		array_pop($this->groupStack);
	}

/** End **/


	/**
	 * Dispatch the current request to it's matching route.
	 *
	 * @param \TeaPress\Contracts\Http\Request $request
	 *
	 * @return \TeaPress\Http\Response
	 */
	public function dispatch(Request $request)
	{
		return $this->addRoute(static::$verbs, $uri, $handler);
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

		foreach ($route->getMethods() as $method) {
			$this->routes[$method][$route->getUri()] = $route;
		}

		$this->routes[$method.$route->getUri()] = $route;

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
			array_map('strtoupper', (array) $methods),
			$this->prefixRouteUri($uri),
			$this->getGroupName(),
			$this->getGroupNamespace()
		);

		if($handler)
			$route->handler($handler);
		elseif ($handler = $this->getGroupHandler()) {
			$route->handler($handler, false);
		}

		return $route;
	}

	/**
	 * Create a route instance.
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
		$route = new Route($this, $methods, $uri, $prefix, $namespace);
		$route->setContainer($this->app);
		return $route;
	}

	/**
	* Parse a route's action parameter.
	*
	* @param  mixed   $action
	* @return array
	*/
	protected function parseRouteAction($action)
	{
		//
	}

	/**
	 * Add a controller based route action to the action array.
	 *
	 * @param  array|string  $action
	 * @return array
	 */
	protected function convertToControllerAction($action)
	{
		if (is_string($action))
			$action = ['uses' => $action];


		// Here we'll merge any group "uses" statement if necessary so that the action
		// has the proper clause for this property. Then we can simply set the name
		// of the controller on the action and return the action array for usage.
		if ($this->hasGroupStack()) {
			$action['uses'] = $this->prependGroupUses($action['uses']);
		}

		// Here we will set this controller name on the action array just so we always
		// have a copy of it for reference if we need it. This can be used while we
		// search for a controller name or do some other type of fetch operation.
		$action['controller'] = $action['uses'];

		return $action;
	}

	/**
	 * Prepend the last group uses onto the use clause.
	 *
	 * @param  string  $uses
	 * @return string
	 */
	protected function prependGroupUses($uses)
	{
		$group = end($this->groupStack);
		return isset($group['namespace']) && strpos($uses, '\\') !== 0 ? $group['namespace'].'\\'.$uses : $uses;
	}

	/**
	 * Add the given route to the arrays of routes.
	 *
	 * @param  \TeaPress\Routing\Route  $route
	 * @return void
	 */
	protected function addToCollections($route, $add_to_lookups = true)
	{

		$this->routes[] = $route;

		// foreach ($route->getMethods() as $method) {
		// 	$this->routes[$method][$route->getUri()] = $route;
		// }

		if($add_to_lookups)
			$this->addLookups($route);
	}

	/**
	 * Add the route to any look-up tables if necessary.
	 *
	 * @param  \TeaPress\Routing\Route  $route
	 * @return void
	 */
	protected function addLookups($route)
	{
		// If the route has a name, we will add it to the name look-up table so that we
		// will quickly be able to find any route associate with a name and not have
		// to iterate through every route every time we need to perform a look-up.
		if ($name = $route->getName())
			$this->nameList[$name] = $route;


		// When the route is routing to a controller we will also store the action that
		// is used by the route. This will let us reverse route to controllers while
		// processing a request and easily generate URLs to the given controllers.
		if ($action = $route->getController())
			$this->actionList[$action] = $route;
	}

	/**
	 * Refresh the name look-up table.
	 *
	 * This is done in case any names are fluently defined.
	 *
	 * @return void
	 */
	public function refreshNameLookups()
	{
		$this->nameList = [];

		foreach ($this->allRoutes as $route) {
			if ($route->getName()) {
				$this->nameList[$route->getName()] = $route;
			}
		}
	}

	/**
	 * Update the group stack with the given attributes.
	 *
	 * @param  array  $attributes
	 * @return void
	 */
	protected function updateGroupStack(array $attributes)
	{
		if (! empty($this->groupStack)) {
			$attributes = $this->mergeGroup($attributes, end($this->groupStack));
		}

		$this->groupStack[] = $attributes;
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
		$new['namespace'] = static::formatUsesPrefix($new, $old);

		$new['prefix'] = static::formatGroupPrefix($new, $old);

		if (isset($old['as']))
			$new['as'] = $old['as'].(isset($new['as']) ? $new['as'] : '');

		if(isset($new['before']))
			$new['before'] = (array) $new['before'];

		if(isset($new['after']))
			$new['after'] = (array) $new['after'];

		if( isset($old['before']) )
			$new['before'] = isset($new['before'])
					? array_unique( array_merge($old['before'], $new['before']))
					: $old['before'];

		if( isset($old['after']) )
			$new['after'] = isset($new['after'])
					? array_unique( array_merge($old['after'], $new['after']))
					: $old['after'];


		return array_merge_recursive(Arr::except($old, ['namespace', 'prefix', 'as', 'after', 'before']), $new);
	}

	/**
	 * Format the uses prefix for the new group attributes.
	 *
	 * @param  array  $new
	 * @param  array  $old
	 * @return string|null
	 */
	public static function formatUsesPrefix($new, $old)
	{
		if (isset($new['namespace'])) {
			return isset($old['namespace'])
					? join_paths([$old['namespace'], $new['namespace']], '\\')
					: $new['namespace'];
		}

		return isset($old['namespace']) ? $old['namespace'] : null;
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
		return join_paths(Arr::get($old, 'prefix', ''), Arr::get($new, 'prefix', ''));
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
	 * @return string
	 */
	protected function getGroupHandler()
	{
		return Arr::get($this->currentGroup(), 'handler');
	}

	/**
	 * Get the URL to a route.
	 *
	 * @param  string $name
	 * @param  array  $parameters
	 * @return string
	 */
	public function url($name, $parameters = [])
	{
		$route = null;
		$routes = $this->routes['named'];
		foreach (self::$methods as $method)
		{
			if ( ! isset($routes[$method . '::' . $name]))
			{
				continue;
			}

			$route = $routes[$method . '::' . $name];
		}

		if ($route === null)
		{
			return null;
		}

		$matches = [];
		preg_match_all($this->parameterPattern, $uri = $route['uri'], $matches);
		foreach ($matches[0] as $id => $match)
		{
			$uri = preg_replace('/' . preg_quote($match) . '/', array_get($args, $matches[1][$id], $match), $uri, 1);
		}

		return home_url() . '/' . $uri;
	}

	/**
	 * Get all registered routes
	 *
	 * @return array
	 */
	public function getRoutes()
	{
		return Arr::get($this->routes, strtoupper($method), []);
	}

}
