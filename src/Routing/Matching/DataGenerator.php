<?php
namespace TeaPress\Routing\Matching;

use FastRoute\DataGenerator\GroupCountBased;

class DataGenerator extends GroupCountBased implements DataGeneratorInterface
{
	/**
	 * Instantiate the data generator.
	 *
	 * @param array 	$routes
	 * @return void
	 */
	public function __construct(array $routes = [])
	{
		foreach ((array) $routes as $route) {
			$this->add($route);
		}
	}

	/**
	 * Add a route to the data generator.
	 *
	 * @param \TeaPress\Routing\Matching\Matchable $route
	 * @return static
	 */
	public function add(Matchable $route)
	{
		foreach ($route->getMethods() as $method) {
			foreach ($route->getUriInfo() as $uri) {
				$this->addRoute($method, $uri, $route);
			}
		}

		return $this;
	}

}