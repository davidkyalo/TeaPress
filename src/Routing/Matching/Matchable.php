<?php
namespace TeaPress\Routing\Matching;

interface Matchable
{

	/**
	 * Get all matchable http methods.
	 *
	 * @return array
	 */
	public function getMethods();

	/**
	 * Get the route's possible URI segments.
	 *
	 * @return array
	 */
	public function getUriInfo();

	/**
	 * Get the matched URI for the route.
	 *
	 * @return string|null
	 */
	public function getMatchedUri();

	/**
	 * Set the matched URI for the route.
	 *
	 * @param  string $uri
	 * @return void
	 */
	public function setMatchedUri($uri);
}