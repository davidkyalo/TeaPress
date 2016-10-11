<?php

namespace TeaPress\Utils\Traits;

throw new \Exception("Trait \TeaPress\Utils\Traits\Singleton Not Implemented");


trait Singleton {

	protected function __construct(){}

	protected function __clone(){}

	protected function __wakeup(){}


	protected static function __create($parameters = []){

	}

	protected static function __new($parameters = []){

		switch (func_num_args()){
			case 0:
				return new static();
				break;

			case 1:
				return new static($args[0]);
				break;

			case 2:
				return new static($args[0], $args[1]);
				break;

			case 3:
				return new static($args[0], $args[1], $args[2]);
				break;

			case 4:
				return new static($args[0], $args[1], $args[2], $args[3]);
				break;

			case 5:
				return new static($args[0], $args[1], $args[2], $args[3], $args[4]);
				break;

			case 6:
				return new static($args[0], $args[1], $args[2], $args[3], $args[4], $args[5]);
				break;

			default:
				return new static($args);
				break;
		}

	}

	public static function build(){

	}

	public static function getInstance() {
		if(is_null(static::$_instance)){
			$args = func_get_args();

		}
		return static::$_instance;
	}
}