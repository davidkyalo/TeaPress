<?php

namespace TeaPress\Arch;

class AliasLoader
{
	/**
	 * The array of class aliases.
	 *
	 * @var array
	 */
	protected $aliases;

	/**
	 * Indicates if a loader has been registered.
	 *
	 * @var bool
	 */
	protected $registered = false;

	/**
	 * The singleton instance of the loader.
	 *
	 * @var \Illuminate\Foundation\AliasLoader
	 */
	protected static $instance;

	/**
	 * Create a new AliasLoader instance.
	 *
	 * @param  array  $aliases
	 */
	private function __construct(array $aliases)
	{
		$this->aliases = $aliases;
	}

	/**
	 * Get or create the singleton alias loader instance.
	 *
	 * @param  array  $aliases
	 * @return \Illuminate\Foundation\AliasLoader
	 */
	public static function getInstance(array $aliases = [])
	{
		if (is_null(static::$instance))
			return static::$instance = new static($aliases);

		if($aliases)
			static::$instance->add($aliases);

		return static::$instance;
	}

	/**
	 * Load a class alias if it is registered.
	 *
	 * @param  string  $alias
	 * @return bool|null
	 */
	public function load($alias)
	{
		if ($original = $this->get($alias)) {
			return class_alias($original, $alias);
		}
	}

	protected function parseAlias($class)
	{
		return '\\' == $class[0] ? substr($class, 1) : $class;
	}

	/**
	 * Add an alias to the loader.
	 *
	 * @param  string  $alias
	 * @param  string  $class
	 * @return void
	 */
	public function alias($alias, $class, $force = false, $slient = true)
	{
		if(!$class)
			throw new InvalidArgumentException("Original class for alias {$alias} required.");

		if($force || !$this->has($alias)){
			$this->aliases[ $this->parseAlias($alias) ] = $class;
			return true;
		}

		if(!$slient)
			throw new InvalidArgumentException("Class Alias {$alias} already exists.");
		else
			trigger_error("Class Alias {$alias} already exists.");

		return false;
	}

	public function add(array $aliases, $force = false, $slient = true)
	{
		foreach ($aliases as $alias => $class) {
			$this->alias($alias, $class, $force, $slient);
		}
	}

	public function set($aliases, $class = null)
	{
		$aliases = is_array($aliases) ? $aliases : [$aliases => $class];
		return $this->add($aliases, true);
	}

	public function has($alias)
	{
		return isset($this->aliases[ $this->parseAlias($alias) ]);
	}

	public function get($alias, $default = null)
	{
		$alias = $this->parseAlias($alias);
		return isset($this->aliases[$alias]) ? $alias : value($default);
	}

	/**
	 * Register the loader on the auto-loader stack.
	 *
	 * @return void
	 */
	public function register()
	{
		if (! $this->registered) {
			$this->prependToLoaderStack();

			$this->registered = true;
		}
	}

	/**
	 * Prepend the load method to the auto-loader stack.
	 *
	 * @return void
	 */
	protected function prependToLoaderStack()
	{
		spl_autoload_register([$this, 'load'], true, true);
	}

	/**
	 * Get the registered aliases.
	 *
	 * @return array
	 */
	public function getAliases()
	{
		return $this->aliases;
	}

	/**
	 * Set the registered aliases.
	 *
	 * @param  array  $aliases
	 * @return void
	 */
	public function setAliases(array $aliases){
		$this->aliases = $aliases;
	}

	/**
	 * Indicates if the loader has been registered.
	 *
	 * @return bool
	 */
	public function isRegistered()
	{
		return $this->registered;
	}

	/**
	 * Set the "registered" state of the loader.
	 *
	 * @param  bool  $value
	 * @return void
	 */
	public function setRegistered($value)
	{
		$this->registered = $value;
	}

	/**
	 * Set the value of the singleton alias loader.
	 *
	 * @param  \Illuminate\Foundation\AliasLoader  $loader
	 * @return void
	 */
	public static function setInstance($loader)
	{
		static::$instance = $loader;
	}

	/**
	 * Clone method.
	 *
	 * @return void
	 */
	private function __clone()
	{
		//
	}
}