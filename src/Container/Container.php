<?php
namespace TeaPress\Container;

use TeaPress\Contracts\Container\Container as Contract;
use Illuminate\Container\Container as IlluminateContainer;

class Container extends IlluminateContainer implements Contract
{

	protected static $instance;

	/**
	* Get the alias for an abstract if available.
	*
	* @param  string  $abstract
	* @return string
	*/
	public function getAlias($abstract)
	{
		return parent::getAlias($abstract);
	}

	/**
	* Determine if the given $item is a valid callable or it's a string in Class@method syntax.
	*
	* @param  mixed  $item
	*
	* @return bool
	*/
	public function isCallable($item)
	{
		return is_callable($item) || $this->isCallableWithAtSign($item);
	}

	/**
	 * Alias a type to a different name or names.
	 *
	 * @param  string  $abstract
	 * @param  string|array  $alias
	 * @return void
	 */
	public function alias($abstract, $alias)
	{
		if( !is_array($aliases) )
			return parent::alias($abstract, $aliases);

		if( !is_null($abstract) )
			$aliases = [ $abstract => $aliases ];

		foreach ($aliases as $abstract => $aliases) {
			foreach ((array) $aliases as $alias) {
				parent::alias($abstract, $alias);
			}
		}
	}

	/**
	 * Get the globally available instance of the container.
	 *
	 * @return static
	 */
	public static function getInstance()
	{
		if(is_null(static::$instance))
			static::$instance = new static;

		return static::$instance;
	}
}