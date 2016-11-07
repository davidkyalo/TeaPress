<?php
namespace TeaPress\Routing;

use FastRoute\RouteParser;

interface UriParserInterface extends RouteParser
{
	/**
	 * Parse a URI rule into an array of acceptable uri segments.
	 *
	 * If patterns are provided, they will be used in place of the default.
	 * Explicitly defined regex patterns (won the uri string)
	 * override the provided patterns.
	 *
	 * @param string 	$uri
	 * @param array 	$patterns
	 * @return array
	 */
	public function parse($uri, array $patterns = []);
}