<?php

namespace TeaPress\Utils;

throw new \Exception("Class \TeaPress\Utils\Traits\Singleton Not Implemented");


abstract class Singleton {

	private static $_instances = [];

	protected function __construct(){}

	private function __clone(){}

	private function __wakeup(){}

	private static function _getInstanceOf($cls){
		if(!array_key_exists($cls, self::$_instances))
			return null;
		return self::$_instances[$cls];
	}


	private static function _createInstanceOf($cls){
		$instance = new $cls;
		static::initialize($instance);
		self::$_instances[$cls] = $instance;
		return self::$_instances[$cls];
	}

	protected static function initialize($instance){
		//nothing;
	}

	public static function instance() {
		$cls = get_called_class();
		$instance = self::_getInstanceOf($cls);
		return $instance ? $instance : self::_createInstanceOf($cls);
	}

}