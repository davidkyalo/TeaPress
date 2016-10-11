<?php
namespace TeaPress\Config;

use Countable;
use ArrayAccess;
use ArrayIterator;
use TeaPress\Utils\Arr;
use IteratorAggregate;
use TeaPress\Utils\Collection;
/**
*
*/
class Base implements  ArrayAccess, Countable, IteratorAggregate  {
	protected $_source;
	protected $paths;
	protected $db_key;
	protected $_is_extracted = false;
	protected $_source_path;

	protected static $app_service_name;

	public static function setAppServiceName($name){
		static::$app_service_name = $name;
	}

	public function __construct($source, $key = null, $extracted = false){
		$this->_is_extracted = is_bool($extracted) ? $extracted : false;
		if(!$this->_is_extracted){
			$this->paths = is_array($source) ? $source : [$source];
			$this->db_key = $key;
		}else{
			$this->_source_path = $source;
		}

		$this->__load();
	}

	public function __load(){
		if($this->isAnExract()){
			$this->setOptions( $this->source()->get( $this->_source_path ) );
		}
		else{
			$this->options = $this->loadOptionsFromPaths();
			$this->options->update( $this->loadOptionsFromDB() );
		}
		$this->hasLoaded();
		return true;
	}

	protected function hasLoaded(){}

	protected function setOptions( $options ){
		$this->options = Collection::make( $options );

	}

	protected function loadOptionsFromPaths(){
		$options = Collection::make();
		foreach ($this->paths as $path) {
			$options->update(  $this->loadOptionsFromPath($path)  );
		}
		return $options;
	}

	protected function loadOptionsFromPath($path){
		if(substr($path, -1) != '/')
			$path .= '/';
		$options =[];
		foreach (glob($path . '*.php') as $filepath) {
			$key = basename($filepath, '.php');
			$options[$key] = $this->readFileOptions($filepath);
		}
		return $options;
	}

	protected function readFileOptions($filepath){
		$options = require($filepath);
		return is_array($options) ? $options : (array) $options;
	}

	protected function loadOptionsFromDB(){
		return Collection::make( $this->db_key ? get_option($this->db_key, []) : [] );
	}

	protected function updateDbOptions($options){
		if(!$this->db_key)
			return;

		$value = !is_array( $options ) ? $options->toArray() : $options;
		update_option($this->db_key, $value, true);
	}

	public function isAnExract(){
		return $this->_is_extracted;
	}

	protected function options(){
		if($this->isAnExract()){
			return $this->_source->all();
		}else{
			return $this->_source;
		}
	}

	protected function source(){
		return $this->isAnExract() ? app( static::$app_service_name ) : null;
	}

	public function sorcePath($key){
		return $this->isAnExract() ? $this->_source_path . '.' . $key : $key;
	}

	public function extract($key){
		$key = $this->sorcePath($key);
		return new static($key, $key );
	}

	/* PROXY */
	public function all(){
		return $this->options;
	}

	/* PROXY */
	public function has($key){
		return array_has($this->options->all(), $key);
	}

	/* PROXY */
	public function get($key, $default = null, $default_is_key = false, $final_default = null){
		if($default_is_key && !$this->has($key)){
			$key = $default;
			$default = $final_default;
		}
		$value = array_get( $this->options->all(), $key, $default);
		return value($value); //is_callable($value) ? $value($this) : $value;
	}

	/* PROXY */
	public function set($key, $value){
		array_set($this->options, $key, $value);
	}

	/* PROXY */
	public function save($key, $value){
		if($this->isAnExract()){
			$this->source()->save( $this->sorcePath($key), $value );
			return $this->__load();
		}

		$options = $this->loadOptionsFromDB();
		array_set($options->all(), $key, $value);
		$this->updateDbOptions($options);
		$this->options->update( $this->loadOptionsFromDB() );
	}

	/* PROXY */
	public function forget($keys){
		if($this->isAnExract()){
			$keys = !is_array($keys) ? [$keys] : $keys;
			$keys = array_map( [ $this, 'sorcePath' ] , $keys );
			$this->source()->forget( $keys );
			return $this->__load();
		}

		array_forget($this->options->all(), $keys);
	}

	/* PROXY */
	public function delete($keys){
		if($this->isAnExract()){
			$keys = !is_array($keys) ? [$keys] : $keys;
			$keys = array_map( [ $this, 'sorcePath' ] , $keys );
			$this->source()->delete( $keys );
			return $this->__load();
		}

		$options = $this->loadOptionsFromDB();
		array_forget($options->all(), $keys);
		$this->updateDbOptions($options);
		$this->options->update( $this->loadOptionsFromDB() );
	}


	public function offsetExists($key){
		return $this->has($key);
	}

	public function offsetGet($key){
		return $this->get($key);
	}

	public function offsetSet($key, $value){
		return $this->set($key, $value);
	}

	public function offsetUnset($key){
		$this->forget($key);
	}


	/* PROXY */
	public function count(){
		return count($this->options);
	}

	/* PROXY */
	public function getIterator(){
		return $this->options->getIterator();//  new ArrayIterator( $this->options );
	}

}