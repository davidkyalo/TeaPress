<?php
namespace TeaPress\Signals;

use TeaPress\Contracts\Core\Container;

/**
 * Tag namespace resolver class.
*/
class TagResolver
{
	/**
	 * @var \TeaPress\Contracts\Core\Container
	 */
	protected $container;

	/**
	 * @var array
	 */
	protected $resolved = [];

	/**
	 * Create the tag resolver instance.
	 *
	 * @param  \TeaPress\Contracts\Core\Container  $container
	 * @return void
	*/
	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * Get the appropriate tag namespace for the given abstract.
	 * If the abstract is a class or contract/interface registered with the container,
	 * it's short service name (if any) is returned.
	 * This ensures that event tags are registered using the same namespace regardless of
	 * whether the class, interface or service name was used.
	 *
	 * @param  string  $abstract
	 * @return string
	*/
	public function resolve($abstract)
	{
		if(isset($this->resolved[$abstract]))
			return $this->resolved[$abstract];

		return $this->resolved[$abstract] = $this->container->getAlias($abstract);
	}
}
