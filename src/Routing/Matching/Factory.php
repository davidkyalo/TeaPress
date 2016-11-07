<?php
namespace TeaPress\Routing\Matching;

use Closure;
use TeaPress\Contracts\Routing\Matching\Factory as Contract;

class Factory implements Contract
{
	/**
	 * @var \Closure
	*/
	protected $matcherResolver;

	/**
	 * @var \Closure
	*/
	protected $dataGeneratorResolver;

	/**
	 * Build a new route matcher for the given routes.
	 *
	 * @param array $routes
	 * @return \TeaPress\Routing\Matching\Matcher
	 */
	public function make(array $routes)
	{
		$generator = $this->createDataGenerator();

		foreach ($routes as $route) {
			$generator->add($route);
		}

		return $this->createMatcher($generator->getData());
	}

	/**
	 * Set the Matcher instance resolver.
	 *
	 * @param \Closure $resolver
	 * @return void
	 */
	public function setMatcherResolver(Closure $resolver)
	{
		$this->matcherResolver = $resolver;
	}

	/**
	 * Set the DataGenerator instance resolver.
	 *
	 * @param \Closure $resolver
	 * @return void
	 */
	public function setDataGeneratorResolver(Closure $resolver)
	{
		$this->dataGeneratorResolver = $resolver;
	}

	/**
	 * Create a Finder instance with the given route data.
	 *
	 * @param array $data
	 * @return \TeaPress\Routing\Matching\Matcher
	 */
	protected function createMatcher(array $data)
	{
		if(is_null($this->matcherResolver))
			return new Matcher($data);

		return call_user_func($this->matcherResolver, $data);
	}

	/**
	 * Create a Finder instance with the given route data.
	 *
	 * @param array $routes
	 * @return \TeaPress\Routing\Matching\DataGenerator
	 */
	protected function createDataGenerator(array $routes = [])
	{
		if(is_null($this->dataGeneratorResolver))
			return new DataGenerator($routes);

		return call_user_func($this->dataGeneratorResolver, $routes);
	}
}