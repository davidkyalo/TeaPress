<?php
namespace TeaPress\Contracts\Routing\Matching;

use FastRoute\Dispatcher as DispatcherInterface;

interface Matcher extends DispatcherInterface
{
	/**
	 * Get status
	 *
	 * @return int
	 */
	public function status();

	/**
	 * Get matched route
	 *
	 * @return mixed
	 */
	public function matched();

	/**
	 * Get matched route parameters
	 *
	 * @return array
	 */
	public function parameters();

	/**
	 * Get the allowed http methods.
	 *
	 * @return array
	 */
	public function allowed();


	/**
	 * Determine whether any route was matched.
	 * If status is given, checks whether it's eq to self::FOUND.
	 *
	 * @param null|int $status
	 * @return bool
	 */
	public function found($status = null);

	/**
	 * Determine whether non of the given routes was matched.
	 * If status is given, checks whether it's eq to self::NOT_FOUND.
	 *
	 * @param null|int $status
	 * @return bool
	 */
	public function notFound($status = null);

	/**
	 * Determine whether the dispatched method is not allowed for the matched route.
	 * If status is given, checks whether it's eq to self::METHOD_NOT_ALLOWED.
	 *
	 * @param null|int $status
	 * @return bool
	 */
	public function methodNotAllowed($status = null);


	/**
	 * Matches the routes against the provided HTTP method verb and URI.
	 *
	 * @param string $method
	 * @param string $uri
	 * @return static
	 */
	public function match($method, $uri);

}