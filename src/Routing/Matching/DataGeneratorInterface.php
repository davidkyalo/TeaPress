<?php
namespace TeaPress\Routing\Matching;

use FastRoute\DataGenerator as BaseDataGenerator;

interface DataGeneratorInterface extends BaseDataGenerator
{

	/**
	 * Add a route to the data generator.
	 *
	 * @param \TeaPress\Routing\Matching\Matchable $route
	 * @return static
	 */
	public function add(Matchable $route);
}