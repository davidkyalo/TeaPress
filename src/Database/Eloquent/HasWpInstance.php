<?php
namespace TeaPress\Database\Models;


trait HasWpInstance {


	// protected static $wp_getter = 'wpInstance';
	protected $_wp_instance = null;
	// protected static $wp_methods = [];


	// protected static $wp_class_meta = [];
	protected static $_wp_configured = [];

	protected function setMissingToWpInstance(){
		defined('static::SET_MISSING_TO_WP_INSTANCE') ? static::SET_MISSING_TO_WP_INSTANCE : false;
	}

	public static function bootHasWpInstance(){
		static::wpSetupClassConfig();
	}

	protected static function wpSetupClassConfig(){
		$cls = get_called_class();
		if(!isset(static::$_wp_configured[$cls]))
			return;

		$valueaskey = function(&$value, $key){
			return is_int($key) ? $value : $key;
		};

		$orig = function($key, $default = []){
			return array_get(static::$wp_class_config, $key, $default);
		};

		$config = [];
		$config['getter'] = $orig( 'getter', 'wp_instance' );
		$config['properties'] = array_transform( $orig( 'properties' ), $valueaskey );
		$config['readonly'] 	= $orig( 'readonly' );
		$config['methods'] = array_transform( $orig( 'methods' ), $valueaskey );
		$config['statics'] = array_transform( $orig( 'statics' ), $valueaskey );

		static::$wp_class_config = array_merge( static::$wp_class_config, $config );
		static::$_wp_configured[$cls] = true;

	}

	protected static function wpClassGet($key = null, $default = null){
		static::wpSetupClassConfig();
		return $key ? array_get( static::$wp_class_config, $key, $default ) : static::$wp_class_config;
	}

	protected function getWpInstance(){
		if(is_null($this->_wp_instance)){
			$instance = $this->createWpInstance();
			$this->setWpInstance( $instance );
			return $instance;
		}
		return $this->_wp_instance;
	}

	abstract protected function createWpInstance();

	public function setWpInstance($instance){
		$this->_wp_instance = $instance;
	}

	public function wpInstanceGetAttributeValue($key){
		$instance = $this->getWpInstance();
		return $instance->$key;
	}

	public function wpInstanceSetAttributeValue($key, $value){
		$instance = $this->getWpInstance();
		$instance->$key = $value;
	}

	public function __get($key){
		if($key ==  static::wpClassGet('getter') )
			return $this->getWpInstance();

		$value = parent::__get($key);

		if(is_null($value) && $this->attributes['ID'] && !$this->checkIfattributeDefined( $key )
			&& $this->getWpInstance()->__isset($key)){
			$value = $this->getWpInstance()->{$key};
		}
		return $value;
	}

	public function __set($key, $value){
		if( !$this->setMissingToWpInstance() || $this->hasSetMutator( $key )
			|| $this->checkIfattributeDefined( $key, false ) ){
				return parent::__set($key, $value);
			}
		return $this->wpSetAttr($key, $value);
	}

	public function wpSetAttr($key, $value){
		$this->getWpInstance()->{$key} = $value;
	}


    public function __call($method, $args){
        if(!in_array($method, ['increment', 'decrement']) && $this->ID
        		&& method_exists( $this->getWpInstance(), snake_case( $method ) )){
            return call_user_method_array(snake_case( $method ), $this->getWpInstance(), $args );
        }
        return parent::__call($method, $args);
    }
}