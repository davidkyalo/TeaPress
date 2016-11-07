<?php
namespace TeaPress\Contracts\Routing;


use Countable;
use IteratorAggregate;

interface RouteCollector extends IteratorAggregate, Countable
{
	/**
	 * Add a Route instance to the directory.
	 *
	 * @param  \TeaPress\Routing\Route  $route
	 * @return \TeaPress\Routing\Route
	 */
	public function add($route);


	/**
	 * Refresh the name and action look-up table.
	 *
	 * This is done in case any names or actions are fluently defined.
	 *
	 * @return void
	 */
	public function refreshLookups();

	/**
	 * Refresh the name look-up table.
	 *
	 * This is done in case any names are fluently defined.
	 *
	 * @return void
	 */
	public function refreshNameLookups();


	/**
	 * Refresh the action look-up table.
	 *
	 * This is done in case any handlers are fluently defined.
	 *
	 * @return void
	 */
	public function refreshActionLookups();

	/**
	 * Get all of the routes in the collection.
	 *
	 * @param  string|null  $method
	 * @return array
	 */
	public function get($method = null);


	/**
	 * Determine if the route collection contains a given named route.
	 *
	 * @param  string  $name
	 * @return bool
	 */
	public function hasNamedRoute($name);


	/**
	 * Get a route instance by its name.
	 *
	 * @param  string  $name
	 * @return \TeaPress\Routing\Route|null
	 */
	public function getByName($name);


	/**
	 * Get a route instance by its controller action.
	 *
	 * @param  string  $action
	 * @return \TeaPress\Routing\Route|null
	 */
	public function getByAction($action);


	/**
	 * Get all of the routes in the collection.
	 *
	 * @return array
	 */
	public function getRoutes();
}