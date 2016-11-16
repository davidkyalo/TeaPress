<?php
namespace TeaPress\Signals;

use InvalidArgumentException;

class Tag
{
	/**
	 * @var \TeaPress\Signals\TagResolver
	 */
	protected static $resolver;

	/**
	 * @var string
	 */
	protected $namespace;

	/**
	 * @var string
	 */
	protected $name;

	/**
	* Create a tag instance.
	*
	* @param  string  $name
	* @param  string|object|null  $namespace
	* @return static
	*/
	public static function create($name, $namespace = null)
	{
		new static($name, $namespace);
	}

	/**
	* Create the tag instance.
	*
	* @param  string  $name
	* @param  string|object|null  $namespace
	* @return void
	*/
	public function __construct($name, $namespace = null)
	{
		$this->name = $name;
		$this->namespace = is_object($namespace) ? get_class($namespace) : $namespace;
	}

	/**
	* Get the full tag name with namespace and interface combined.
	*
	* @return string
	*/
	public function value()
	{
		$namespace = $this->namespace ? static::$resolver->resolve($this->namespace).':' : '';
		return $namespace.$this->name;
	}

	/**
	* Get the full tag name with namespace and interface combined.
	*
	* @return string
	*/
	public function __toString()
	{
		return $this->value();
	}

	/**
	* Set the namespace resolver
	*
	* @param  \TeaPress\Signals\TagResolver  $resolver
	* @return void
	*/
	public static function setResolver(TagResolver $resolver)
	{
		static::$resolver = $resolver;
	}
}
