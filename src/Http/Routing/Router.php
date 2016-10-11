<?php
namespace TeaPress\Http\Routing;

use Closure;
use TeaPress\Utils\Arr;
use TeaPress\Http\Request;
use TeaPress\Events\Dispatcher;
// use TeaPress\Events\EmitterTrait;
// use TeaPress\Events\EmitterInterface;
use TeaPress\Http\Response\Factory as ResponseFactory;
use InvalidArgumentException;
use Illuminate\Container\Container;
use TeaPress\Exceptions\HttpErrorException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @see http://getherbert.com
 *
 * @method void get()    get(array $parameters)    Adds a get route.
 * @method void post()   post(array $parameters)   Adds a post route.
 * @method void put()    put(array $parameters)    Adds a put route.
 * @method void patch()  patch(array $parameters)  Adds a patch route.
 * @method void delete() delete(array $parameters) Adds a delete route.
 */
class Router {


	// /**
	//  * @var array
	//  */
	// protected static $methods = [
	// 	'GET',
	// 	'POST',
	// 	'PUT',
	// 	'PATCH',
	// 	'DELETE'
	// ];


	/**
	 * All of the verbs supported by the router.
	 *
	 * @var array
	 */
	public static $verbs = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];

	/**
	 * @var \Illuminate\Container\Container
	 */
	protected $app;

	/**
	 * @var \TeaPress\Http\Request
	 */
	protected $http;

	protected $responses;

	// /**
	//  * @var array
	//  */
	// protected $routes = [
	// 	'GET' => [],
	// 	'POST' => [],
	// 	'PUT' => [],
	// 	'PATCH' => [],
	// 	'DELETE' => [],
	// 	'named' => []
	// ];

	// /**
	//  * @var array
	//  */
	// protected $rawRoutes = [];

	// /**
	//  * @var array
	//  */
	// protected $rawRoutes = [];

	/**
	 * @var array
	 */
	protected $routes = [];

	/**
	 * @var array
	 */
	protected $nameList = [];

	/**
	 * If the router has booted.
	 *
	 * @var bool
	 */
	protected $booted = false;

	/**
	 * If the routes have been registered.
	 *
	 * @var bool
	 */
	protected $registered = false;

	/**
	 * The current namespace.
	 *
	 * @var string|null
	 */
	protected $namespace = null;

	/**
	 * @var string
	 */
	protected $parameterPattern = '/{([\w\d]+)}/';

	/**
	 * @var string
	 */
	protected $valuePattern = '(?P<$1>[^\/]+)';

	/**
	 * @var string
	 */
	protected $valuePatternReplace = '([^\/]+)';

	/**
	 * @var array
	 */
	protected $groupStack = [];

	/**
	 * Adds the action hooks for WordPress.
	 *
	 * @param \Illuminate\Container\Container $app
	 * @param \TeaPress\Http\Request                 $http
	 */
	public function __construct(Container $app, Request $http, ResponseFactory $responses, Dispatcher $events)
	{
		$this->app = $app;
		$this->http = $http;
		$this->responses = $responses;
		$this->setRouteGroupParams();


		$events->listen('wp_loaded', [$this, 'flush']);
		$events->listen('after_setup_theme', [$this, 'boot'], -99);
		$events->listen('parse_request', [$this, 'parseRequest'], 0);
	}


	/**
	 * Register a new GET route with the router.
	 *
	 * @param  string  $uri
	 * @param  \Closure|array|string  $action
	 *
	 * @return \TeaPress\Http\Routing\Route
	 */
	public function get($uri, $action)
	{
		return $this->addRoute(['GET', 'HEAD'], $uri, $action);
	}

	/**
	 * Register a new POST route with the router.
	 *
	 * @param  string  $uri
	 * @param  \Closure|array|string  $action
	 *
	 * @return \TeaPress\Http\Routing\Route
	 */
	public function post($uri, $action)
	{
		return $this->addRoute('POST', $uri, $action);
	}

	/**
	 * Register a new PUT route with the router.
	 *
	 * @param  string  $uri
	 * @param  \Closure|array|string  $action
	 *
	 * @return \TeaPress\Http\Routing\Route
	 */
	public function put($uri, $action)
	{
		return $this->addRoute('PUT', $uri, $action);
	}

	/**
	 * Register a new PATCH route with the router.
	 *
	 * @param  string  $uri
	 * @param  \Closure|array|string  $action
	 *
	 * @return \TeaPress\Http\Routing\Route
	 */
	public function patch($uri, $action)
	{
		return $this->addRoute('PATCH', $uri, $action);
	}

	/**
	 * Register a new DELETE route with the router.
	 *
	 * @param  string  $uri
	 * @param  \Closure|array|string  $action
	 *
	 * @return \TeaPress\Http\Routing\Route
	 */
	public function delete($uri, $action)
	{
		return $this->addRoute('DELETE', $uri, $action);
	}

	/**
	 * Register a new OPTIONS route with the router.
	 *
	 * @param  string  $uri
	 * @param  \Closure|array|string  $action
	 *
	 * @return \TeaPress\Http\Routing\Route
	 */
	public function options($uri, $action)
	{
		return $this->addRoute('OPTIONS', $uri, $action);
	}

	/**
	 * Register a new route responding to all verbs.
	 *
	 * @param string					$uri
	 * @param \Closure|array|string		$action
	 * @param string|null				$as
	 *
	 * @return \TeaPress\Http\Routing\Route
	 */
	public function any($uri, $action)
	{
		return $this->addRoute(static::$verbs, $uri, $action, $as );
	}

	/**
	 * Register a new route with the given verbs.
	 *
	 * @param  array|string  $methods
	 * @param  string  $uri
	 * @param  \Closure|array|string  $action
	 *
	 * @return \TeaPress\Http\Routing\Route
	 */
	public function match($methods, $uri, $action)
	{
		return $this->addRoute(array_map('strtoupper', (array) $methods), $uri, $action);
	}

	/**
	 * Register a new route.
	 *
	 * @param  array|string  $methods
	 * @param  string  $uri
	 * @param  \Closure|array|string  $action
	 *
	 * @return \TeaPress\Http\Routing\Route
	 */
	protected function addRoute($methods, $uri, $action)
	{
		$route = $this->createRoute( $methods, $uri, $action);

		$this->addToCollections($route);

		return $route;
	}

	/**
	 * Create a new route instance.
	 *
	 * @param  array|string  $methods
	 * @param  string  $uri
	 * @param  mixed   $action
	 *
	 * @return \TeaPress\Http\Routing\Route
	 */
	protected function createRoute($methods, $uri, $action)
	{
		if( is_array($action) && Arr::isAssoc($action) ){
			$handler = Arr::get($action, 'uses');
			$as = Arr::get( $action, 'as');
		}
		else{
			$handler = $action;
		}

		$route = $this->newRoute($methods, $uri, $action);
		$route->setContainer($this->app);

		if($this->hasGroupStack()){
			$this->mergeGroupAttributesIntoRoute( $route );

			$route->prefix($this->groupStack['prefix']);
			$route->namespace($this->groupStack['namespace']);
			$route->before(Arr::get( $this->groupStack, 'before', []));
			$route->after(Arr::get( $this->groupStack, 'after', []));
		}

		return $route;
	}


	/**
	* Create a new Route object.
	*
	* @param  array|string  $methods
	* @param  string  $uri
	* @param  mixed   $action
	* @return  \TeaPress\Http\Routing\Route
	*/
	protected function newRoute($methods, $uri, $action)
	{
		return (new Route($methods, $uri, $action))->setContainer($this->container);
	}

	/**
	 * Merge the group stack with the controller action.
	 *
	 * @param  \TeaPress\Http\Routing\Route  $route
	 * @return void
	 */
	protected function mergeGroupAttributesIntoRoute($route)
	{
		$action = $this->mergeGroup($route->getAction(), end($this->groupStack));
		$route->setAction($action);
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
	 * @param  \TeaPress\Http\Routing\Route  $route
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
	 * @param  \TeaPress\Http\Routing\Route  $route
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
	 * Create a route group with shared attributes.
	 *
	 * @param  array     $attributes
	 * @param  \Closure  $callback
	 * @return void
	 */
	public function group(array $attributes, Closure $callback)
	{
		$this->updateGroupStack($attributes);

		// Once we have updated the group stack, we will execute the user Closure and
		// merge in the groups attributes when the route is created. After we have
		// run the callback, we will pop the attributes off of this group stack.
		call_user_func($callback, $this);

		array_pop($this->groupStack);
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
	protected static function formatUsesPrefix($new, $old)
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
	protected static function formatGroupPrefix($new, $old)
	{
		return join_paths(Arr::get($old, 'prefix', ''), Arr::get($new, 'prefix', ''));
	}


	/**
	 * Parse route filters into an array.
	 *
	 * @param  array  $new
	 * @param  array  $old
	 * @return string|null
	 */
	protected static function mergeRouteFilters($new, $old)
	{
		$result = [];
		$is_assoc = Arr::isAssoc((array) $filters);

		foreach ((array) $filters as $key => $args) {
			if( $is_assoc ){
				$result[$key] = is_string($args) ? explode(',', $args) : (array) $args;
			}
			else{
				list($name, $args) = array_pad(explode(':', $args, 2), 2, []);
				$result[$name] = is_string($args) ? explode(',', $args) : (array) $args;
			}

		}

		return $result;
	}




	/**
	 * Determine if the router currently has a group stack.
	 *
	 * @return bool
	 */
	public function hasGroupStack()
	{
		return ! empty($this->groupStack);
	}

	/**
	 * Get the current group stack for the router.
	 *
	 * @return array
	 */
	public function getGroupStack()
	{
		return $this->groupStack;
	}

	public function before($route, $callback, $priority = null){
		Route::on($route, $callback );
	}


	public function after(){

	}

	/**
	 * Flushes WordPress's rewrite rules.
	 *
	 * @return void
	 */
	public function flush()
	{
		flush_rewrite_rules();
	}

	/**
	 * Boot the router.
	 *
	 * @return void
	 */
	public function boot()
	{
		if($this->booted)
			return;

		add_rewrite_tag('%teapress_route%', '(.+)');

		if(is_array($this->routes[$this->http->method()]))
		{
			foreach ($this->routes[$this->http->method()] as $id => $route)
			{
				$this->addRoute($route, $id, $this->http->method());
			}
		}

		$this->booted = true;
	}

	/**
	 * Register rewrite rules.
	 *
	 * @return void
	 */
	public function registerRewriteRules()
	{
		$this->boot();

		add_rewrite_tag('%teapress_route%', '(.+)');

		if(is_array($this->routes[$this->http->method()]))
		{
			foreach ($this->routes[$this->http->method()] as $id => $route)
			{
				$this->addRouteRewriteRules($route, $id, $this->http->method());
			}
		}

		$this->registered = true;
	}

	/**
	 * Adds the route to WordPress.
	 *
	 * @param $route
	 * @param $id
	 * @param $method
	 */
	protected function addRouteRewriteRules($route, $id, $method)
	{
		$params = [
		'id' => $id,
		'parameters' => []
		];

		$uri = '^' . preg_replace(
			$this->parameterPattern,
			$this->valuePatternReplace,
			str_replace('/', '\\/', $route['uri'])
			);

		$url = 'index.php?';

		$matches = [];
		if (preg_match_all($this->parameterPattern, $route['uri'], $matches))
		{
			foreach ($matches[1] as $id => $param)
			{
				add_rewrite_tag('%teapress_param_' . $param . '%', '(.+)');
				$url .= 'teapress_param_' . $param . '=$matches[' . ($id + 1) . ']&';
				$params['parameters'][$param] = null;
			}
		}

		add_rewrite_rule($uri . '$', $url . 'teapress_route=' . urlencode(json_encode($params)), 'top');
	}
	/**
	 * Parses a WordPress request.
	 *
	 * @param $wp
	 * @return void
	 */
	public function parseRequest($wp)
	{
		if ( ! array_key_exists('teapress_route', $wp->query_vars))
		{
			return;
		}

		$data = @json_decode($wp->query_vars['teapress_route'], true);
		$route = null;


		if (isset($data['id']) && isset($this->routes[$this->http->method()][$data['id']]))
		{
			$route = $this->routes[$this->http->method()][$data['id']];
		}
		elseif (isset($data['name']) && isset($this->routes['named'][$data['name']]))
		{
			$route = $this->routes['named'][$data['name']];
		}

		if ( ! isset($route))
		{
			return;
		}

		if ( ! isset($data['parameters']))
		{
			$data['parameters'] = [];
		}

		foreach ($data['parameters'] as $key => $val)
		{
			if ( ! isset($wp->query_vars['teapress_param_' . $key]))
			{
				return;
			}

			$data['parameters'][$key] = $wp->query_vars['teapress_param_' . $key];
		}

		try {
			$this->processRequest(
				$this->buildRoute(
					$route,
					$data['parameters']
					)
				);
		} catch (HttpErrorException $e) {
			if ($e->getStatus() === 301 || $e->getStatus() === 302)
			{
				$this->processResponse($e->getResponse());

				die;
			}

			if ($e->getStatus() === 404)
			{
				global $wp_query;
				$wp_query->set_404();
			}

			status_header($e->getStatus());

			define('TEAPRESS_HTTP_ERROR_CODE', $e->getStatus());
			define('TEAPRESS_HTTP_ERROR_MESSAGE', $e->getMessage());

			if ($e->getStatus() === 404)
			{
				@include get_404_template();
			}
			else
			{
				echo $e->getMessage();
			}
		}

		die;
	}

	/**
	 * Build a route instance.
	 *
	 * @param $data
	 * @param $params
	 * @return \TeaPress\Routing\Route
	 */
	protected function buildRoute($data, $params)
	{
		return new Route($this->app, $this->responses, $data, $params);
	}

	/**
	 * Processes a request.
	 *
	 * @param \TeaPress\Routing\Route $route
	 * @return void
	 */
	protected function processRequest(Route $route)
	{
		$this->processResponse($route->run());
	}

	/**
	 * Processes a response.
	 *
	 * @param  \TeaPress\Http\Response\Response $response
	 * @return void
	 */
	protected function processResponse(Response $response)
	{
		$response->send();
	}

	/**
	 * Get the URL to a route.
	 *
	 * @param  string $name
	 * @param  array  $args
	 * @return string
	 */
	public function url($name, $args = [])
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
	 * Sets the current namespace.
	 *
	 * @param  string $namespace
	 * @return void
	 */
	public function setNamespace($namespace)
	{
		$this->namespace = $namespace;
	}

	/**
	 * Unsets the current namespace.
	 *
	 * @return void
	 */
	public function unsetNamespace()
	{
		$this->namespace = null;
	}

	/**
	 * Namespaces a name.
	 *
	 * @param  string $as
	 * @return string
	 */
	protected function namespaceAs($as)
	{
		if ($this->namespace === null)
		{
			return $as;
		}

		return $this->namespace . '::' . $as;
	}

	public function getAllRoutes($method = null)
	{
		return Arr::get($this->routes, strtoupper($method), []);
	}

	/**
	 * Magic method calling.
	 *
	 * @param       $method
	 * @param array $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters = [])
	{
		if (method_exists($this, $method))
		{
			return call_user_func_array([$this, $method], $parameters);
		}

		if (in_array(strtoupper($method), static::$methods))
		{
			return call_user_func_array([$this, 'add'], array_merge([strtoupper($method)], $parameters));
		}

		throw new InvalidArgumentException("Method {$method} not defined");
	}

}
