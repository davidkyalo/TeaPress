<?php
namespace TeaPress\Contracts\Routing\Matching;

interface Factory
{
	/**
	 * Build a new route matcher for the given routes.
	 *
	 * @param array $routes
	 * @return \TeaPress\Contracts\Routing\Matching\Matcher
	 */
	public function make(array $routes);
}