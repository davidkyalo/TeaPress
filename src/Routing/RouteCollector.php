<?php
namespace TeaPress\Routing;


use ArrayIterator;
use TeaPress\Utils\Arr;
use Illuminate\Http\Response;
use TeaPress\Contracts\Http\Request;
use TeaPress\Routing\Error\RouteNotFound;
use TeaPress\Routing\Error\MethodNotAllowed;
use TeaPress\Contracts\Routing\RouteCollector as Contract;
use TeaPress\Contracts\Routing\Matching\Factory as Matcher;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class RouteCollector implements Contract
{
	/**
	 * @var \TeaPress\Contracts\Routing\Matching\Factory
	 */
	protected $matcher;

	/**
	 * An array of the routes keyed by method.
	 *
	 * @var array
	 */
	protected $routes = [];

	/**
	 * An flattened array of all of the routes.
	 *
	 * @var array
	 */
	protected $allRoutes = [];

	/**
	 * A look-up table of routes by their names.
	 *
	 * @var array
	 */
	protected $nameList = [];

	/**
	 * A look-up table of routes by controller action.
	 *
	 * @var array
	 */
	protected $actionList = [];


	/**
	 * Create the route collector instance.
	 *
	 * @param \TeaPress\Contracts\Routing\Matching\Factory  $matcher
	 */
	public function __construct(Matcher $matcher)
	{
		$this->matcher = $matcher;
	}

	/**
	 * Add a Route instance to the directory.
	 *
	 * @param  \TeaPress\Routing\Route  $route
	 * @return \TeaPress\Routing\Route
	 */
	public function add($route)
	{
		$this->addToCollections($route);

		$this->addLookups($route);

		return $route;
	}

	/**
	 * Add the given route to the arrays of routes.
	 *
	 * @param  \TeaPress\Routing\Route $route
	 * @return void
	 */
	protected function addToCollections($route)
	{
		foreach ($route->getMethods() as $method) {
			$this->routes[$method][$route->getUri()] = $route;
		}

		$this->allRoutes[$method.$route->getUri()] = $route;
	}

	/**
	 * Add the route to any look-up tables if necessary.
	 *
	 * @param  \TeaPress\Routing\Route  $route
	 * @return void
	 */
	protected function addLookups($route)
	{
		if($name = $route->getName())
			$this->nameList[$name] = $route;

		if($controller = $route->getController())
			$this->actionList[trim($controller, '\\')] = $route;
	}

	/**
	 * Refresh the name and action look-up table.
	 *
	 * This is done in case any names or actions are fluently defined.
	 *
	 * @return void
	 */
	public function refreshLookups()
	{
		$this->nameList = [];
		$this->actionList = [];

		foreach ($this->allRoutes as $route) {
			$this->addLookups($route);
		}
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
			if ( $name = $route->getName()) {
				$this->nameList[$name] = $route;
			}
		}
	}


	/**
	 * Refresh the action look-up table.
	 *
	 * This is done in case any handlers are fluently defined.
	 *
	 * @return void
	 */
	public function refreshActionLookups()
	{
		$this->actionList = [];

		foreach ($this->allRoutes as $route) {
			if ( $controller = $route->getController()) {
				$this->actionList[trim($controller, '\\')] = $route;
			}
		}
	}


	/**
	 * Match the given HTTP verb and URI with one of the registered routes.
	 *
	 * @param  string  $verb
	 * @param  string  $uri
	 * @return \TeaPress\Routing\Route|\TeaPress\Routing\Error\RoutingError
	 */
	public function match($verb, $uri)
	{
		$match = $this->matcher()->match($verb, $uri);

		if(!$match->found()){
			if($match->notFound())
				return new RouteNotFound;
			if( $match->methodNotAllowed() && $verb !== 'OPTIONS' )
				return new MethodNotAllowed( $match->allowed() );
		}

		$route = $match->found()
					? $match->matched()
					: $this->createHttpOptionsRoute($uri, $match->allowed());

		return $route->bindParameters( $match->parameters() );

	}

	/**
	 * Get the matcher for the registered routes.
	 *
	 * @return \TeaPress\Contracts\Routing\Matching\Matcher
	 */
	protected function matcher()
	{
		return $this->matcher->make($this->allRoutes);
	}

	/**
	 * Create a fall-back OPTIONS route for the given URI and allowed methods.
	 *
	 * @param  string  $uri
	 * @param  array  $methods
	 * @return \TeaPress\Routing\Route
	 */
	protected function createHttpOptionsRoute($uri, array $methods)
	{
		return (new Route('OPTIONS', $uri))
				->handler(function () use ($methods) {
					return new Response('', 200, ['Allow' => implode(',', $methods)]);
				});
	}

	/**
	 * Throw a method not allowed HTTP exception.
	 *
	 * @param  array  $others
	 * @return void
	 *
	 * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
	 */
	protected function throwMethodNotAllowed(array $others)
	{
		throw new MethodNotAllowedHttpException($others);
	}


	/**
	 * Throw a not found HTTP exception.
	 *
	 * @return void
	 *
	 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 */
	protected function throwHttpNotFound()
	{
		throw new NotFoundHttpException;
	}


	/**
	 * Get all of the routes in the collection.
	 *
	 * @param  string|null  $method
	 * @return array
	 */
	public function get($method = null)
	{
		return Arr::get($this->routes, $method, []);
	}

	/**
	 * Determine if the route collection contains a given named route.
	 *
	 * @param  string  $name
	 * @return bool
	 */
	public function hasNamedRoute($name)
	{
		return ! is_null($this->getByName($name));
	}

	/**
	 * Get a route instance by its name.
	 *
	 * @param  string  $name
	 * @return \TeaPress\Routing\Route|null
	 */
	public function getByName($name)
	{
		return isset($this->nameList[$name]) ? $this->nameList[$name] : null;
	}

	/**
	 * Get a route instance by its controller action.
	 *
	 * @param  string  $action
	 * @return \TeaPress\Routing\Route|null
	 */
	public function getByAction($action)
	{
		return isset($this->actionList[$action]) ? $this->actionList[$action] : null;
	}

	/**
	 * Get all of the routes in the collection.
	 *
	 * @return array
	 */
	public function getRoutes()
	{
		return array_values($this->allRoutes);
	}

	/**
	 * Get an iterator for the items.
	 *
	 * @return \ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->getRoutes());
	}

	/**
	 * Count the number of items in the collection.
	 *
	 * @return int
	 */
	public function count()
	{
		return count($this->getRoutes());
	}

}