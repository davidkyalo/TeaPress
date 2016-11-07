<?php
namespace TeaPress\Routing;

use TeaPress\Contracts\Http\Request;

interface MatcherInterface
{

	public static function make();


	public function found();

	/**
	 * Matches the given http method and uri
	 */
	public function match($uriMap, $method, $uri);

}