<?php
namespace TeaPress\Filesystem;

use Closure;
use ArrayIterator;
use RuntimeException;
use IteratorAggregate;
use BadMethodCallException;
use InvalidArgumentException;
use TeaPress\Contracts\Utils\Arrayable;
use TeaPress\Contracts\Utils\ArrayBehavior;


class Compiler implements ArrayBehavior, Arrayable, IteratorAggregate
{

	/**
	 * @var \TeaPress\Filesystem\Filesystem
	 */
	protected $__filesystem;

	/**
	 * @var string
	 */
	protected $__path;

	/**
	 * @var mixed
	 */
	protected $__scope;

	/**
	 * @var array
	 */
	protected $__vars = [];

	/**
	 * @var array
	 */
	protected $__attributes = [];

	/**
	 * @var mixed
	 */
	protected $__returns;

	/**
	 * @var bool
	 */
	protected $__isComplied = false;

	/**
	 * Create the script instance.
	 *
	 * @param \TeaPress\Filesystem\Filesystem 	$filesystem
	 * @param string|array						$path
	 * @param mixed 							$scope
	 * @param array 							$vars
	 *
	 * @return void
	 */
	public function __construct(Filesystem $filesystem, $path, $scope = null, array $vars = [])
	{
		$this->__vars = $vars;
		$this->__path = $path;
		$this->__filesystem = $filesystem;

		if(!is_null($scope) && $this->isValidScopeObject($scope, false))
			$this->__scope = $scope;
	}

	/**
	 * Determine whether the given scope object is valid.
	 *
	 * @param mixed 	$scope
	 * @param bool 		$silent
	 *
	 * @throws \InvalidArgumentException
	 * @return bool
	 */
	protected function isValidScopeObject($scope, $silent = true)
	{
		$valid = ( is_object($scope) || (is_string($scope) && class_exists($scope)) );

		if(!$silent && !$valid){
			$stype = ucfirst(gettype($scope));
			throw new InvalidArgumentException("Script scope should be an object or valid class name. {$stype} given.");
		}

		return $valid;
	}

	/**
	 * Get the file-system instance.
	 *
	 * @return \TeaPress\Filesystem\Filesystem
	 */
	public function filesystem()
	{
		return $this->__filesystem;
	}

	/**
	 * Get the script's scope.
	 * The object to be used as $this variable within the script.
	 *
	 * @return mixed
	 */
	public function scope()
	{
		return $this->__scope;
	}

	/**
	 * Get the script's full path.
	 *
	 * @return string
	 */
	public function path()
	{
		return $this->__path;
	}

	/**
	 * Set the return value of the  current path.
	 *
	 * @param mixed 		$returns
	 *
	 * @return static
	 */
	public function setReturns($returns)
	{
		if($returns === 1) $returns = null;

		$this->__returns = $returns;

		return $this;
	}

	/**
	 * Get/Set the variables to be imported into the script's symbol table.
	 *
	 * @return array
	 */
	public function vars($vars = NOTHING)
	{
		if($vars !== NOTHING)
			$this->__vars = $vars;

		return $this->__vars;
	}

	/**
	 * Set/Get the variables to be imported into the script's symbol table.
	 *
	 * @param string 	$key
	 * @param mixed 	$value
	 *
	 * @return mixed
	 */
	public function var($key, $value = NOTHING)
	{
		if($value !== NOTHING)
			$this->__vars[$key] = $value;

		return $value !== NOTHING || isset($this->__vars[$key]) ? $this->__vars[$key] : null;
	}

	/**
	 * Determine if the path have been compiled.
	 *
	 * @return bool
	 */
	public function isCompiled()
	{
		return $this->__isComplied;
	}

	/**
	 * Get all the attributes.
	 *
	 * @return array
	 */
	public function getAttributes()
	{
		return $this->__attributes;
	}

	/**
	 * Get the returned value.
	 *
	 * @return mixed
	 */
	public function returns()
	{
		return $this->__returns;
	}

	/**
	 * Get the parsed attributes and return value.
	 *
	 * @return mixed
	 */
	public function response()
	{
		if(!$this->isCompiled())
			return false;

		$attributes = $this->getAttributes();
		$returns = $this->returns();

		if(count($attributes) === 0)
			return $returns;

		$returns = is_array($returns) && Arr::isAssoc($returns) ? $returns : ['return' => $returns];

		return Arr::extend($attributes, $returns);
	}

	/**
	 * Include and evaluate all the paths.
	 *
	 * @param bool $force
	 * @return mixed
	 */
	public function compile($force = false)
	{
		if( $force || !$this->__isComplied){

			$this->__attributes = [];

			$func = $this->getCompiler();

			$func();

			$this->__isComplied = true;
		}

		return $this->response();
	}

	/**
	 * Merge the return values with the attributes.
	 *
	 * @return \Closure
	 */
	protected function getCompiler()
	{
		$self = $this;

		$func = function() use ($self){
			extract( $self->vars(), EXTR_SKIP );
			$self->setReturns( require($self->path()) );
		};

		$scope = $this->scope();

		if(is_null($scope))
			return $func;

		return is_string($scope) ? $func->bindTo(null, $scope) : $func->bindTo($scope);
	}

	/**
	 * Get all the attributes.
	 *
	 * @return array
	 */
	public function all()
	{
		return $this->__attributes;
	}

	/**
	 * Determine if the given attribute exists.
	 *
	 * @param string 	$key
	 *
	 * @return bool
	 */
	public function has($key)
	{
		return Arr::has($this->__attributes, $key);
	}

	/**
	 * Set an attribute.
	 *
	 * @param string 	$key
	 * @param mixed 	$value
	 *
	 * @return static
	 */
	public function set($key, $value)
	{
		Arr::set($this->__attributes, $key, $value);

		return $this;
	}

	/**
	 * Sets the given attribute if it doesn't already exist.
	 *
	 * @param string 	$key
	 * @param mixed 	$value
	 *
	 * @return static
	 */
	public function add($key, $value)
	{
		Arr::add($this->__attributes, $key, $value);

		return $this;
	}

	/**
	 * Get an attribute.
	 *
	 * @param string 	$key
	 * @param mixed 	$default
	 *
	 * @return mixed
	 */
	public function get($key, $default = null)
	{
		return Arr::get($this->__attributes, $key, $default);
	}

	/**
	 * Get all attribute keys.
	 *
	 * @return array
	 */
	public function keys()
	{
		return array_keys($this->__attributes);
	}

	/**
	 * Remove the given attributes.
	 *
	 * @param strings 	...$keys
	 *
	 * @return static
	 */
	public function forget(...$keys)
	{
		Arr::forget($this->__attributes, $keys);

		return $this;
	}

	/**
	 * Merge the given attributes with the current.
	 *
	 * @param  arrays 			...$attributes
	 *
	 * @return static
	 */
	public function merge(array ...$attributes)
	{
		Arr::extend($this->__attributes, null, ...$attributes);

		return $this;
	}

	/**
	 * Recursively merge the given attributes with the current.
	 *
	 * @param  arrays 			...$attributes
	 *
	 * @return static
	 */
	public function mergeRecursive(array ...$attributes)
	{
		Arr::extendDeep($this->__attributes, null, ...$attributes);

		return $this;
	}

	/**
	 * Merge the given attributes with the current.
	 *
	 * @param  string 	 		$key
	 * @param  arrays 			...$attributes
	 *
	 * @return static
	 */
	public function extend($key, array ...$attributes)
	{
		Arr::extend($this->__attributes, $key, ...$attributes);

		return $this;
	}

	/**
	 * Recursively merge the given attributes with the current.
	 *
	 * @param  string 			$key
	 * @param  arrays 			...$attributes
	 *
	 * @return static
	 */
	public function extendDeep($key, array ...$attributes)
	{
		Arr::extendDeep($this->__attributes, $key, ...$attributes);

		return $this;
	}

	/**
	 * Appends values to the nested array '$key' in the attributes.
	 * If the target array is not set, an empty one is created.
	 *
	 * @param string|null 		$key
	 * @param mixed 			...$values
	 *
	 * @return static
	 */
	public static function push($key, ...$values)
	{
		Arr::push($this->__attributes, $key, ...$values);

		return $this;
	}

	/**
	 * Appends the non-existing values to the nested array '$key' in the attributes.
	 * If the target array is not set, an empty one is created.
	 *
	 * @param string|null 		$key
	 * @param mixed 			...$values
	 *
	 * @return static
	 */
	public static function pushUnique($key, ...$values)
	{
		Arr::pushUnique($this->__attributes, $key, ...$values);

		return $this;
	}

	/**
	 * Get the number of attributes.
	 *
	 * @return int
	 */
	public function count()
	{
		return count($this->getAttributes());
	}

	/**
	 * Get the attribute iterator.
	 *
	 * @return \ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->getAttributes());
	}

	/**
	 * Determine if the given attribute exists.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function offsetExists($key)
	{
		return $this->has($key);
	}

	/**
	 * Get an attribute.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function offsetGet($key)
	{
		return $this->get($key);
	}

	/**
	 * Set an attribute.
	 *
	 * @param  string  $key
	 * @param  mixed  $value
	 *
	 * @return void
	 */
	public function offsetSet($key, $value)
	{
		$this->set($key, $value);
	}

	/**
	 * Unset an attribute.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function offsetUnset($key)
	{
		$this->forget($key);
	}

	/**
	 * All attribute keys.
	 *
	 * @return array
	 */
	public function offsets()
	{
		return $this->keys();
	}

	/**
	 * The get magic method.
	 *
	 * @param string 	$key
	 *
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->get($key);
	}

	/**
	 * The set magic method.
	 *
	 * @param string 	$key
	 * @param mixed 	$value
	 *
	 * @return void
	 */
	public function __set($key, $value)
	{
		$this->set($key, $value);
	}

	/**
	 * Unset an attribute.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function __unset($key)
	{
		$this->forget($key);
	}


	/**
	 * The call magic method.
	 *
	 * @param string 	$method
	 * @param array 	$args
	 *
	 * @throws \RuntimeException
	 * @return mixed
	 */
	public function __call($method, $args)
	{
		$scope = $this->getScope();

		if(is_null($scope)){
			throw new RuntimeException('Method "'.$method.'" not defined. And the scope for script "'.$this->path().'" has not been set.');
		}

		return $scope->$method(...$args);
	}
}
