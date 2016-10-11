<?php
namespace TeaPress\Utils\Traits;

use ArrayIterator;
use TeaPress\Utils\Arr;
use BadFunctionCallException;
use TeaPress\Exceptions\AttributeError;

trait Fluent {

	// const PROPERTY_CONTAINER = 'props';
	// const DYNAMIC_SCHEMA = true;
	// const LOCK_CLASS_VARS = false;
	// const MASS_ASSIGN_PROPERTIES = true;
	// protected $private_properties = [];
	// protected $readonly_properties = [];
	// protected $fillable_properties = [];
	// protected $array_append = [];

	protected $_readonly_properties_ = null;

	protected function _specialClassProperties(){
		$properties = ['private_properties', 'readonly_properties', 'fillable_properties', 'array_append'];
		if( $container = $this->_getPropertyContainer() )
			$properties[] = $container;

		return $properties;
	}

	protected function _canMassAssignProperties(){
		return defined('static::MASS_ASSIGN_PROPERTIES') ? static::MASS_ASSIGN_PROPERTIES : true;
	}

	protected function _getFillableProperties(){
		return isset($this->fillable_properties) ? $this->fillable_properties : [];
	}

	protected function _dymanicSchema(){
		return defined('static::DYNAMIC_SCHEMA') ? static::DYNAMIC_SCHEMA : true;
	}

	protected function _getPrivateProperties(){
		return isset($this->private_properties) ? $this->private_properties : [];
	}

	protected function _getPropertyContainer(){
		return  defined('static::PROPERTY_CONTAINER') ? static::PROPERTY_CONTAINER : null;
	}

	protected function _lockClassVars(){
		return  defined('static::LOCK_CLASS_VARS') ? static::LOCK_CLASS_VARS : false;
	}

	public function propertyExists($key){
		return !$this->_inPropertyContainer($key) ? property_exists($this, $key) : true;
	}

	protected function _inPropertyContainer($key){
		$container = $this->_getPropertyContainer();
		return $container && ( ( is_array($this->{$container}) && isset($this->{$container}[$key]) )
			|| isset( $this->{$container}->{$key} ) );
	}

	protected function _getPropertyFromContainer($property, $or_error = true){
		$container = $this->_getPropertyContainer();
		if(!$container)
			return $or_error ? AttributeError::notFound( $property, $this) : null;

		return $or_error && !$this->_inPropertyContainer($property)
					? AttributeError::notFound( $property, $this)
					: data_get($this->{$container}, $property);
	}

	protected function _setPropertyValueInContainer($property, $value){
		$container = $this->_getPropertyContainer();
		if(!$container || ( !$this->_dymanicSchema() && !$this->_inPropertyContainer($property) ) )
			return false;

		if(is_array($this->{$container}))
			$this->{$container}[$property] = $value;
		else
			$this->{$container}->{$property} = $value;

		return true;
	}

	protected function _unsetPropertyFromContainer($property){
		if(!$this->_inPropertyContainer($property))
			return false;

		$container = $this->_getPropertyContainer();

		if(is_array($this->{$container}))
			unset($this->{$container}[$property]);
		else
			unset($this->{$container}->{$property});
	}

	protected function _getReadOnlyProperties(){
		if(is_null($this->_readonly_properties_)){
			$this->_readonly_properties_ = isset($this->readonly_properties)
			? array_merge( $this->readonly_properties, $this->_getPrivateProperties() )
			:  $this->_getPrivateProperties();

		}
		return $this->_readonly_properties_;
	}

	protected function _getPropertyGetter($property){
		$method = camel_case($property).'Getter';
		return method_exists($this, $method) ? $method : null;
	}

	protected function _getPropertySetter($property){
		$method = camel_case($property).'Setter';
		return method_exists($this, $method) ? $method : null;
	}

	public function getProperty($key, $default = null){
		return $this->_getProperty($key, $default);
	}

	protected function _getProperty($key, $default = null){
		if($method = $this->_getPropertyGetter($key))
			return $this->{$method}();

		if(!in_array( $key, $this->_getPrivateProperties() ) ) {
			if( !$this->_lockClassVars() && property_exists($this, $key) )
				return $this->{$key};

			$value = $this->_getPropertyFromContainer($key);
			if( !is_exception( $value ) )
				return $value;

		}else{
			throw AttributeError::accessDenied($key, $this);
			return null;
		}

		if(!$this->_dymanicSchema())
			throw AttributeError::illegal( $key, $this );

		return $default;
	}

	public function setProperties(array $properties){
		if(!$this->_canMassAssignProperties()){
			throw new BadFunctionCallException("Mass Assigning Properties Not Allowed for CLASS: '". get_class() ."'");
			return;
		}
		$fillable = $this->_getFillableProperties();
		$fillall = !empty($fillable) && $fillable[0] == '*' ? true : false;
		foreach ($properties as $name => $value) {
			if($fillall || in_array($name, $fillable)){
				$this->_setProperty($name, $value);
			}else{
				throw new AttributeError("Property '{$name}' cannot be mass assigned. CLASS: '". get_class() ."'");
			}
		}
	}

	public function setProperty($key, $value){
		return $this->_setProperty($key, $value);
	}

	protected function _setProperty($key, $value, $strict = true){
		if($method = $this->_getPropertySetter($key))
			return $this->{$method}($value);

		if(!in_array($key, $this->_getReadOnlyProperties() )) {
			if( !$this->_lockClassVars() &&  property_exists($this, $key)){
				$this->{$key} = $value;
				return;
			}

			if( $this->_setPropertyValueInContainer($key, $value) )
				return;
		}
		else{
			throw AttributeError::readOnly($key, $this);
			return;
		}

		if($strict)
			throw AttributeError::illegal($key, $this);
		return;
	}

	protected function unsetProperty($key, $force = false){
		$this->_unsetPropertyFromContainer($key);
		if( property_exists($this, $key) && ($force || !$this->_lockClassVars()) )
			unset($this->{$key});
	}

	public function get($key, $default = null){
		return $this->_getProperty($key, $default);
	}

	public function offsetExists($key){
		return $this->propertyExists($key);
	}

	public function offsetGet($key){
		return $this->getProperty($key);
	}

	public function offsetSet($key, $value){
		return $this->setProperty($key, $value);
	}

	public function offsetUnset($key){
		$this->unsetProperty($key);
	}

	public function toArray(){
		$container = $this->_getPropertyContainer();
		$data =  $container && is_array($this->{$container})
					? $this->{$container}
					: ( is_object($this->{$container}) ? get_object_vars($this->{$container}) : []);

		if( isset($this->array_append) ){
			$append = [];
			foreach ($this->array_append as $key) {
				if($key === '*' ){
					$special = $this->_specialClassProperties();
					foreach (get_object_vars($this) as $name => $value) {
						if( !in_array($name, $special) )
							$data[$name] = $this->{$key};
					}
				}
				else{
					$data[$key] = $this->{$key};
				}
			}
		}
		return $data;
	}

	public function count(){
		return count( $this->toArray() );
	}

	public function getIterator(){
		return new ArrayIterator( $this->toArray() );
	}


	public function __get($key){
		return $this->_getProperty($key);
	}

	public function __set($key, $value){
		return $this->_setProperty($key, $value);
	}

	public function __isset($key){
		return $this->propertyExists($key);
	}

	public function __unset($key){
		$this->unsetProperty($key, true);
	}

}